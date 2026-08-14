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

    /**
     * Get or load all categories for the current service instance.
     */
    private function allCategories(): EloquentCollection
    {
        return $this->categoriesCache ??= Category::query()
            ->select(['id', 'name', 'parent_id'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Clear the internal category cache after mutations.
     */
    public function clearCache(): void
    {
        $this->categoriesCache = null;
    }

    /**
     * Get paginated categories with optional search
     * and category/descendant filtering.
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

        /*
         * Filter by selected category and all its descendants.
         */
        if ($categoryId !== null) {
            $category = $this->allCategories()->firstWhere('id', $categoryId);

            if ($category) {
                $descendantIds = $this->getDescendantIds($category);
                $query->whereIn('id', $descendantIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        /*
         * Search by category name.
         */
        if ($search !== null && trim($search) !== '') {
            $query->where(
                'name',
                'like',
                '%' . trim($search) . '%'
            );
        }

        return Paginator::paginate($query, $perPage);
    }

    /**
     * Get all categories for dropdowns.
     */
    public function getAllCategories(): EloquentCollection
    {
        return $this->allCategories();
    }

    /**
     * Get a category and all descendant IDs using a single in-memory lookup.
     *
     * Example:
     *
     * Electronics
     * ├── Phones
     * │   ├── Android
     * │   └── iPhone
     * └── Computers
     *
     * Returns:
     * [Electronics ID, Phones ID, Android ID, iPhone ID, Computers ID]
     */
    public function getDescendantIds(Category $category): Collection
    {
        $categories = $this->allCategories();
        $childrenMap = $categories->groupBy('parent_id');

        $ids = collect([$category->id]);
        $queue = [$category->id];

        while (!empty($queue)) {
            $currentId = array_shift($queue);
            $children = $childrenMap->get($currentId, collect());

            foreach ($children as $child) {
                $ids->push($child->id);
                $queue[] = $child->id;
            }
        }

        return $ids->unique()->values();
    }

    /**
     * Get categories that can be used as parents.
     *
     * When editing a category, the category itself
     * and all of its descendants are excluded.
     */
    public function getAvailableParents(
        ?Category $category = null
    ): EloquentCollection {
        $categories = $this->allCategories();

        if ($category !== null) {
            $excludedIds = $this->getDescendantIds($category);
            return $categories->whereNotIn('id', $excludedIds)->values();
        }

        return $categories;
    }

    /**
     * Build the category tree using in-memory parent_id grouping.
     *
     * Returns only root categories, with their children
     * recursively attached.
     */
    public function getTree(): Collection
    {
        $categories = $this->allCategories();
        $grouped = $categories->groupBy('parent_id');

        return $this->buildTreeFromGrouped($grouped, null);
    }

    /**
     * Recursively build the tree from parent_id grouped map.
     */
    private function buildTreeFromGrouped(
        Collection $grouped,
        ?int $parentId = null
    ): Collection {
        $children = $grouped->get($parentId, collect());

        return $children->map(function (Category $category) use ($grouped) {
            $category->setRelation(
                'children',
                $this->buildTreeFromGrouped($grouped, $category->id)
            );

            return $category;
        })->values();
    }

    /**
     * Create a category.
     */
    public function create(array $data): Category
    {
        $name = trim($data['name']);
        $parentId = isset($data['parent_id']) && $data['parent_id'] !== ''
            ? (int) $data['parent_id']
            : null;

        $this->validateParent($parentId);

        $this->ensureUniqueName(
            $name,
            $parentId
        );

        try {
            $category = DB::transaction(function () use ($name, $parentId) {
                return Category::create([
                    'name' => $name,
                    'parent_id' => $parentId,
                ]);
            });

            $this->clearCache();

            return $category;
        } catch (QueryException $e) {
            if ($this->isDuplicateKeyException($e)) {
                throw ValidationException::withMessages([
                    'name' => [
                        'A category with this name already exists under the selected parent.'
                    ],
                ]);
            }
            throw $e;
        }
    }

    /**
     * Update a category.
     */
    public function update(
        Category $category,
        array $data
    ): Category {
        $name = trim($data['name']);
        $parentId = isset($data['parent_id']) && $data['parent_id'] !== ''
            ? (int) $data['parent_id']
            : null;

        /*
         * Make sure the new parent is valid and does not
         * create a circular relationship.
         */
        $this->validateParent(
            $parentId,
            $category
        );

        /*
         * Make sure another category with the same name
         * does not already exist under the same parent.
         */
        $this->ensureUniqueName(
            $name,
            $parentId,
            $category
        );

        try {
            $updatedCategory = DB::transaction(function () use (
                $category,
                $name,
                $parentId
            ) {
                $category->update([
                    'name' => $name,
                    'parent_id' => $parentId,
                ]);

                return $category->fresh([
                    'parent:id,name',
                    'children',
                ]);
            });

            $this->clearCache();

            return $updatedCategory;
        } catch (QueryException $e) {
            if ($this->isDuplicateKeyException($e)) {
                throw ValidationException::withMessages([
                    'name' => [
                        'A category with this name already exists under the selected parent.'
                    ],
                ]);
            }
            throw $e;
        }
    }

    /**
     * Delete a single category.
     *
     * Categories with children cannot be deleted.
     */
    public function delete(Category $category): void
    {
        if ($category->children()->exists()) {
            throw ValidationException::withMessages([
                'category' => [
                    "Cannot delete '{$category->name}' because it has child categories."
                ],
            ]);
        }

        DB::transaction(function () use ($category) {
            $category->delete();
        });

        $this->clearCache();
    }

    /**
     * Delete multiple categories.
     *
     * The whole operation fails if even one selected category
     * has children. Single query check for child existence.
     */
    public function bulkDelete(array $ids): void
    {
        $ids = collect($ids)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            throw ValidationException::withMessages([
                'ids' => [
                    'Please select at least one category.'
                ],
            ]);
        }

        DB::transaction(function () use ($ids) {
            $categories = Category::query()
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->get();

            /*
             * Make sure all requested IDs exist.
             */
            if ($categories->count() !== $ids->count()) {
                throw ValidationException::withMessages([
                    'ids' => [
                        'One or more selected categories do not exist.'
                    ],
                ]);
            }

            /*
             * Single query check for any children among selected IDs.
             */
            $hasChildren = Category::query()
                ->whereIn('parent_id', $ids)
                ->exists();

            if ($hasChildren) {
                throw ValidationException::withMessages([
                    'ids' => [
                        'Cannot delete categories that have child categories.'
                    ],
                ]);
            }

            /*
             * Safe to delete everything.
             */
            Category::query()
                ->whereIn('id', $ids)
                ->delete();
        });

        $this->clearCache();
    }

    /**
     * Validate that a parent exists and does not create a cycle.
     */
    private function validateParent(
        ?int $parentId,
        ?Category $category = null
    ): void {
        if ($parentId === null) {
            return;
        }

        $parent = $this->allCategories()->firstWhere('id', $parentId);

        if (!$parent) {
            throw ValidationException::withMessages([
                'parent_id' => [
                    'The selected parent category does not exist.'
                ],
            ]);
        }

        if ($category !== null && $parent->id === $category->id) {
            throw ValidationException::withMessages([
                'parent_id' => [
                    'A category cannot be its own parent.'
                ],
            ]);
        }

        if ($category !== null) {
            $descendantIds = $this->getDescendantIds($category);

            if ($descendantIds->contains($parent->id)) {
                throw ValidationException::withMessages([
                    'parent_id' => [
                        'You cannot select one of this category\'s descendants as its parent.'
                    ],
                ]);
            }
        }
    }

    /**
     * Make sure the category name is unique under
     * the same parent.
     */
    private function ensureUniqueName(
        string $name,
        ?int $parentId,
        ?Category $category = null
    ): void {
        $query = Category::query()
            ->where('name', $name)
            ->where(function ($query) use ($parentId) {
                if ($parentId === null) {
                    $query->whereNull('parent_id');
                } else {
                    $query->where('parent_id', $parentId);
                }
            });

        if ($category !== null) {
            $query->where('id', '!=', $category->id);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => [
                    'A category with this name already exists under the selected parent.'
                ],
            ]);
        }
    }

    /**
     * Check if a QueryException is a duplicate key/unique constraint violation.
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