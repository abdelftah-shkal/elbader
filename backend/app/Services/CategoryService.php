<?php

namespace App\Services;

use App\Models\Category;
use App\Utils\Paginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function __construct(
        protected ?Paginator $paginator = null
    ) {
        $this->paginator = $paginator ?? new Paginator();
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
            ->with('parent')
            ->orderBy('name');

        /*
         * Filter by selected category and all its descendants.
         */
        if ($categoryId !== null) {
            $category = Category::findOrFail($categoryId);

            $descendantIds = $this->getDescendantIds($category);

            $query->whereIn('id', $descendantIds);
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
        return Category::query()
            ->orderBy('name')
            ->get();
    }

    /**
     * Get a category and all descendant IDs.
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
        $ids = collect([$category->id]);

        $currentParentIds = collect([$category->id]);

        while ($currentParentIds->isNotEmpty()) {
            $childIds = Category::query()
                ->whereIn('parent_id', $currentParentIds)
                ->pluck('id');

            if ($childIds->isEmpty()) {
                break;
            }

            $ids = $ids->merge($childIds);

            $currentParentIds = $childIds;
        }

        return $ids
            ->unique()
            ->values();
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
        $query = Category::query()
            ->orderBy('name');

        if ($category !== null) {
            $excludedIds = $this->getDescendantIds($category);

            $query->whereNotIn('id', $excludedIds);
        }

        return $query->get();
    }

    /**
     * Build the category tree.
     *
     * Returns only root categories, with their children
     * recursively attached.
     */
    public function getTree(): Collection
    {
        $categories = Category::query()
            ->orderBy('name')
            ->get();

        return $this->buildTree($categories);
    }

    /**
     * Recursively build the tree.
     */
    private function buildTree(
        Collection $categories,
        ?int $parentId = null
    ): Collection {
        return $categories
            ->where('parent_id', $parentId)
            ->map(function (Category $category) use ($categories) {
                $category->setRelation(
                    'children',
                    $this->buildTree(
                        $categories,
                        $category->id
                    )
                );

                return $category;
            })
            ->values();
    }

    /**
     * Create a category.
     */
    public function create(array $data): Category
    {
        $name = trim($data['name']);
        $parentId = $data['parent_id'] ?? null;

        $this->validateParent($parentId);

        $this->ensureUniqueName(
            $name,
            $parentId
        );

        return DB::transaction(function () use ($name, $parentId) {
            return Category::create([
                'name' => $name,
                'parent_id' => $parentId,
            ]);
        });
    }

    /**
     * Update a category.
     */
    public function update(
        Category $category,
        array $data
    ): Category {
        $name = trim($data['name']);
        $parentId = $data['parent_id'] ?? null;

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

        return DB::transaction(function () use (
            $category,
            $name,
            $parentId
        ) {
            $category->update([
                'name' => $name,
                'parent_id' => $parentId,
            ]);

            return $category->fresh([
                'parent',
                'children',
            ]);
        });
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
    }

    /**
     * Delete multiple categories.
     *
     * The whole operation fails if even one selected category
     * has children.
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
             * Check every selected category.
             */
            foreach ($categories as $category) {
                if ($category->children()->exists()) {
                    throw ValidationException::withMessages([
                        'ids' => [
                            "Cannot delete '{$category->name}' because it has child categories."
                        ],
                    ]);
                }
            }

            /*
             * Safe to delete everything.
             */
            Category::query()
                ->whereIn('id', $ids)
                ->delete();
        });
    }

    /**
     * Validate that a parent exists and does not create a cycle.
     */
    private function validateParent(
        ?int $parentId,
        ?Category $category = null
    ): void {
        /*
         * NULL means root category.
         */
        if ($parentId === null) {
            return;
        }

        /*
         * Parent must exist.
         */
        $parent = Category::find($parentId);

        if (!$parent) {
            throw ValidationException::withMessages([
                'parent_id' => [
                    'The selected parent category does not exist.'
                ],
            ]);
        }

        /*
         * During update, category cannot be its own parent.
         */
        if ($category !== null && $parent->id === $category->id) {
            throw ValidationException::withMessages([
                'parent_id' => [
                    'A category cannot be its own parent.'
                ],
            ]);
        }

        /*
         * During update, parent cannot be one of the
         * category's descendants.
         */
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
     *
     * Example:
     *
     * Electronics
     * ├── Phones
     *
     * Clothing
     * └── Phones
     *
     * This is allowed because the parents are different.
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

        /*
         * When updating, exclude the current category.
         */
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
}