<?php

namespace App\Services;

use App\Models\Category;
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
     * Request-level category cache.
     */
    private ?EloquentCollection $categoriesCache = null;

    public function __construct(
        protected ?Paginator $paginator = null
    ) {
        $this->paginator = $paginator ?? new Paginator();
    }

    // -------------------------------------------------------------------------
    // Cache
    // -------------------------------------------------------------------------

    /**
     * Load all categories once per request and cache them in memory.
     */
    private function allCategories(): EloquentCollection
    {
        return $this->categoriesCache ??= Category::query()
            ->select(['id', 'name', 'parent_id'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Clear the internal cache after any mutation.
     */
    public function clearCache(): void
    {
        $this->categoriesCache = null;
    }

    // -------------------------------------------------------------------------
    // Queries
    // -------------------------------------------------------------------------

    /**
     * Get paginated categories with optional search and category-tree filtering.
     */
    public function getPaginatedCategories(
        ?string $search = null,
        ?int $categoryId = null,
        int $perPage = 10
    ): LengthAwarePaginator {
        $query = Category::query()
            ->select(['id', 'name', 'parent_id'])
            ->with('parent:id,name')
            ->orderBy('name');

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
     * Get all categories (uses cache).
     */
    public function getAllCategories(): EloquentCollection
    {
        return $this->allCategories();
    }

    /**
     * Get categories that may be selected as a parent.
     *
     * When editing a category, the category itself and all its
     * descendants are excluded to prevent circular relationships.
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
     * Build the full category tree using a single in-memory pass.
     *
     * Returns root-level categories with their children
     * recursively attached as a relation.
     */
    public function getTree(): Collection
    {
        $grouped = $this->allCategories()->groupBy('parent_id');

        return $this->buildTree($grouped, null);
    }

    // -------------------------------------------------------------------------
    // Mutations
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
                fn () => Category::create(['name' => $name, 'parent_id' => $parentId])
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
            $updated = DB::transaction(function () use ($category, $name, $parentId) {
                $category->update(['name' => $name, 'parent_id' => $parentId]);

                return $category->fresh(['parent:id,name', 'children']);
            });

            $this->clearCache();

            return $updated;
        } catch (QueryException $e) {
            $this->handleDuplicateKey($e);
        }
    }

    /**
     * Delete a single leaf category.
     *
     * Throws a ValidationException when the category has children.
     */
    public function delete(Category $category): void
    {
        if ($category->children()->exists()) {
            throw ValidationException::withMessages([
                'category' => ["Cannot delete '{$category->name}' because it has child categories."],
            ]);
        }

        DB::transaction(fn () => $category->delete());

        $this->clearCache();
    }

    /**
     * Atomically delete multiple leaf categories.
     *
     * Rolls back the entire transaction if any selected category
     * has children, or if any requested ID no longer exists.
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
            $categories = Category::query()
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->get();

            if ($categories->count() !== $ids->count()) {
                throw ValidationException::withMessages([
                    'ids' => ['One or more selected categories do not exist.'],
                ]);
            }

            if (Category::whereIn('parent_id', $ids)->exists()) {
                throw ValidationException::withMessages([
                    'ids' => ['Cannot delete categories that have child categories.'],
                ]);
            }

            Category::whereIn('id', $ids)->delete();
        });

        $this->clearCache();
    }

    // -------------------------------------------------------------------------
    // Hierarchy helpers
    // -------------------------------------------------------------------------

    /**
     * Return a category's ID and all descendant IDs using a
     * single in-memory BFS over the cached category list.
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
     * Recursively build a tree from a parent_id → children map.
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
     * Parse parent_id from raw request data.
     *
     * Returns null for root categories (empty string or missing key).
     */
    private function resolveParentId(array $data): ?int
    {
        return isset($data['parent_id']) && $data['parent_id'] !== ''
            ? (int) $data['parent_id']
            : null;
    }

    /**
     * Validate that a parent_id is legal:
     *  – the parent must exist
     *  – a category cannot be its own parent
     *  – a category cannot move into one of its own descendants
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
     * Ensure no sibling already uses the same name under the same parent.
     *
     * Uses the in-memory cache for zero extra queries. The database unique
     * constraint is the authoritative last line of defence.
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
     * Re-throw a QueryException as a user-friendly ValidationException
     * when it originates from a unique-constraint violation.
     *
     * @throws ValidationException
     * @throws QueryException
     */
    private function handleDuplicateKey(QueryException $e): never
    {
        if ($this->isDuplicateKeyException($e)) {
            throw ValidationException::withMessages([
                'name' => ['A category with this name already exists under the selected parent.'],
            ]);
        }

        throw $e;
    }

    /**
     * Detect SQLSTATE 23000 / 23505 (unique constraint violations)
     * across MySQL, PostgreSQL, and SQLite drivers.
     */
    private function isDuplicateKeyException(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());

        return $sqlState === '23000'
            || $sqlState === '23505'
            || str_contains($e->getMessage(), 'UNIQUE constraint failed')
            || str_contains($e->getMessage(), 'Duplicate entry');
    }
}