<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Utils\Paginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    /**
     * Request-level in-memory cache.
     * Avoids repeating the same SELECT within one HTTP request.
     */
    private ?EloquentCollection $categoriesCache = null;

    public function __construct(
        private CategoryRepositoryInterface $repository
    ) {
    }

    // -------------------------------------------------------------------------
    // Cache
    // -------------------------------------------------------------------------

    /**
     * Return all categories, loading them once from the database
     * and caching them in memory for the lifetime of this request.
     */
    private function allCategories(): EloquentCollection
    {
        return $this->categoriesCache ??= $this->repository->getAll();
    }

    /**
     * Clear the in-memory cache after any mutation so subsequent
     * calls receive fresh data.
     */
    private function clearCache(): void
    {
        $this->categoriesCache = null;
    }

    // -------------------------------------------------------------------------
    // Read operations
    // -------------------------------------------------------------------------

    /**
     * Return a paginated list of categories.
     *
     * Applies optional name search and descendant filtering on top of
     * the base query provided by the repository.
     */
    public function getPaginatedCategories(
        ?string $search = null,
        ?int $categoryId = null,
        int $perPage = 10
    ): LengthAwarePaginator {
        $query = $this->repository->paginatedQuery();

        if ($categoryId !== null) {
            $root = $this->allCategories()->firstWhere('id', $categoryId);

            if ($root) {
                $query->whereIn('id', $this->getDescendantIds($root));
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if (filled($search)) {
            $query->where('name', 'like', '%' . trim($search) . '%');
        }

        return Paginator::paginate($query, $perPage);
    }

    /**
     * Return all categories for dropdown lists (uses cache).
     */
    public function getAllCategories(): EloquentCollection
    {
        return $this->allCategories();
    }

    /**
     * Return categories that are valid parent choices.
     *
     * When editing a category, it and all its descendants are excluded
     * to prevent circular relationships.
     */
    public function getAvailableParents(?Category $category = null): EloquentCollection
    {
        $all = $this->allCategories();

        if ($category === null) {
            return $all;
        }

        $excluded = $this->getDescendantIds($category);

        return $all->whereNotIn('id', $excluded)->values();
    }

    /**
     * Build and return the full category tree.
     *
     * Uses a single in-memory pass — no N+1 queries.
     */
    public function getTree(): Collection
    {
        $grouped = $this->allCategories()->groupBy('parent_id');

        return $this->buildTree($grouped, null);
    }

    // -------------------------------------------------------------------------
    // Write operations
    // -------------------------------------------------------------------------

    /**
     * Create a new category.
     */
    public function create(array $data): Category
    {
        $name     = trim($data['name']);
        $parentId = $this->resolveParentId($data);

        $this->validateParent($parentId);
        $this->ensureUniqueName($name, $parentId);

        try {
            $category = DB::transaction(
                fn () => $this->repository->create(['name' => $name, 'parent_id' => $parentId])
            );

            $this->clearCache();

            return $category;
        } catch (QueryException $e) {
            $this->handleDuplicateKey($e);
        }
    }

    /**
     * Update an existing category.
     */
    public function update(Category $category, array $data): Category
    {
        $name     = trim($data['name']);
        $parentId = $this->resolveParentId($data);

        $this->validateParent($parentId, $category);
        $this->ensureUniqueName($name, $parentId, $category);

        try {
            $updated = DB::transaction(
                fn () => $this->repository->update($category, ['name' => $name, 'parent_id' => $parentId])
            );

            $this->clearCache();

            return $updated;
        } catch (QueryException $e) {
            $this->handleDuplicateKey($e);
        }
    }

    /**
     * Delete a single leaf category.
     *
     * Business rule: categories with children cannot be deleted.
     */
    public function delete(Category $category): void
    {
        if ($this->repository->hasChildren($category)) {
            throw ValidationException::withMessages([
                'category' => ["Cannot delete '{$category->name}' because it has child categories."],
            ]);
        }

        DB::transaction(fn () => $this->repository->delete($category));

        $this->clearCache();
    }

    /**
     * Atomically delete multiple leaf categories.
     *
     * Business rules:
     * - All IDs must exist.
     * - None of them may have children.
     */
    public function bulkDelete(array $ids): void
    {
        $ids = collect($ids)->map(fn ($id) => (int) $id)->unique()->values();

        if ($ids->isEmpty()) {
            throw ValidationException::withMessages([
                'ids' => ['Please select at least one category.'],
            ]);
        }

        DB::transaction(function () use ($ids) {
            $rows = $this->repository->lockForDelete($ids->all());

            if ($rows->count() !== $ids->count()) {
                throw ValidationException::withMessages([
                    'ids' => ['One or more selected categories do not exist.'],
                ]);
            }

            if ($this->repository->hasAnyChildren($ids->all())) {
                throw ValidationException::withMessages([
                    'ids' => ['Cannot delete categories that have child categories.'],
                ]);
            }

            $this->repository->deleteMany($ids->all());
        });

        $this->clearCache();
    }

    // -------------------------------------------------------------------------
    // Hierarchy helpers
    // -------------------------------------------------------------------------

    /**
     * Return the given category's ID plus all descendant IDs.
     *
     * Uses a BFS (breadth-first search) over the in-memory cache.
     * No recursive database queries — one query for all levels.
     *
     * Electronics
     * ├── Phones
     * │   ├── Android
     * │   └── iPhone
     * └── Computers
     *
     * → [Electronics, Phones, Android, iPhone, Computers] (IDs)
     */
    public function getDescendantIds(Category $category): Collection
    {
        $childrenMap = $this->allCategories()->groupBy('parent_id');

        $ids   = collect([$category->id]);
        $queue = [$category->id];

        while ($queue) {
            $currentId = array_shift($queue);

            foreach ($childrenMap->get($currentId, collect()) as $child) {
                $ids->push($child->id);
                $queue[] = $child->id;
            }
        }

        return $ids;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Recursively build the tree from a grouped parent_id map.
     */
    private function buildTree(Collection $grouped, ?int $parentId): Collection
    {
        return $grouped
            ->get($parentId, collect())
            ->map(function (Category $category) use ($grouped) {
                $category->setRelation('children', $this->buildTree($grouped, $category->id));

                return $category;
            })
            ->values();
    }

    /**
     * Normalise the parent_id field from raw request data.
     * Returns null for root categories (empty string or missing key).
     */
    private function resolveParentId(array $data): ?int
    {
        return isset($data['parent_id']) && $data['parent_id'] !== ''
            ? (int) $data['parent_id']
            : null;
    }

    /**
     * Business rule: a parent_id must be valid and must not create a cycle.
     *
     * Checks (in order):
     *  1. Parent must exist.
     *  2. A category cannot be its own parent.
     *  3. A category cannot be moved into one of its own descendants.
     */
    private function validateParent(?int $parentId, ?Category $category = null): void
    {
        if ($parentId === null) {
            return;
        }

        $parent = $this->allCategories()->firstWhere('id', $parentId);

        if (! $parent) {
            throw ValidationException::withMessages([
                'parent_id' => ['The selected parent category does not exist.'],
            ]);
        }

        if ($category === null) {
            return;
        }

        if ($parent->id === $category->id) {
            throw ValidationException::withMessages([
                'parent_id' => ['A category cannot be its own parent.'],
            ]);
        }

        if ($this->getDescendantIds($category)->contains($parent->id)) {
            throw ValidationException::withMessages([
                'parent_id' => ["You cannot select one of this category's descendants as its parent."],
            ]);
        }
    }

    /**
     * Business rule: category names must be unique under the same parent.
     *
     * Uses the in-memory cache — zero extra database queries.
     * The database unique constraint (parent_id_safe, name) is the
     * last line of defence against race conditions.
     */
    private function ensureUniqueName(
        string $name,
        ?int $parentId,
        ?Category $category = null
    ): void {
        $duplicate = $this->allCategories()
            ->when($category, fn ($col) => $col->where('id', '!=', $category->id))
            ->where('name', $name)
            ->firstWhere('parent_id', $parentId);

        if ($duplicate) {
            throw ValidationException::withMessages([
                'name' => ['A category with this name already exists under the selected parent.'],
            ]);
        }
    }

    /**
     * Convert a QueryException for a unique-constraint violation into
     * a user-friendly ValidationException.
     *
     * @throws ValidationException
     * @throws QueryException
     */
    private function handleDuplicateKey(QueryException $e): never
    {
        $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());

        $isDuplicate = $sqlState === '23000'
            || $sqlState === '23505'
            || str_contains($e->getMessage(), 'UNIQUE constraint failed')
            || str_contains($e->getMessage(), 'Duplicate entry');

        if ($isDuplicate) {
            throw ValidationException::withMessages([
                'name' => ['A category with this name already exists under the selected parent.'],
            ]);
        }

        throw $e;
    }
}