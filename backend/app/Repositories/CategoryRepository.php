<?php

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository implements CategoryRepositoryInterface
{
    /**
     * Find a category by ID.
     */
    public function find(int $id): ?Category
    {
        return Category::query()->find($id);
    }

    /**
     * Get all categories with only the columns needed for
     * tree construction, dropdown, and hierarchy lookups.
     */
    public function getAll(): Collection
    {
        return Category::query()
            ->select(['id', 'name', 'parent_id'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Return a base Eloquent query builder for the paginated table.
     *
     * The Service is responsible for applying search/filter conditions
     * on top of this query before passing it to the Paginator.
     */
    public function paginatedQuery(): Builder
    {
        return Category::query()
            ->select(['id', 'name', 'parent_id'])
            ->with('parent:id,name')
            ->orderBy('name');
    }

    /**
     * Insert a new category row.
     */
    public function create(array $data): Category
    {
        return Category::create($data);
    }

    /**
     * Update an existing category row and return it refreshed
     * with its parent and children relations loaded.
     */
    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->fresh(['parent:id,name', 'children']);
    }

    /**
     * Delete a single category row.
     */
    public function delete(Category $category): void
    {
        $category->delete();
    }

    /**
     * Delete multiple category rows by ID in one query.
     */
    public function deleteMany(array $ids): void
    {
        Category::whereIn('id', $ids)->delete();
    }

    /**
     * Return true if the given category has at least one direct child.
     */
    public function hasChildren(Category $category): bool
    {
        return $category->children()->exists();
    }

    /**
     * Return true if any category in the given ID list has children.
     *
     * Uses a single EXISTS query — no loops, no N+1.
     */
    public function hasAnyChildren(array $ids): bool
    {
        return Category::whereIn('parent_id', $ids)->exists();
    }

    /**
     * Lock the rows for the given IDs within a transaction.
     *
     * Returns the locked collection so the Service can verify
     * all requested IDs actually exist before deleting.
     */
    public function lockForDelete(array $ids): Collection
    {
        return Category::query()
            ->whereIn('id', $ids)
            ->lockForUpdate()
            ->get();
    }
}
