<?php

namespace App\Repositories\Contracts;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface
{
    /**
     * Find a category by ID.
     */
    public function find(int $id): ?Category;

    /**
     * Get all categories (id, name, parent_id) ordered by name.
     * Used for tree, dropdowns, and in-memory hierarchy lookups.
     */
    public function getAll(): Collection;

    /**
     * Return a base query builder for the paginated table.
     * The Service applies search/filter before paginating.
     */
    public function paginatedQuery(): Builder;

    /**
     * Persist a new category to the database.
     */
    public function create(array $data): Category;

    /**
     * Update an existing category in the database.
     */
    public function update(Category $category, array $data): Category;

    /**
     * Delete a single category from the database.
     */
    public function delete(Category $category): void;

    /**
     * Atomically delete multiple categories.
     */
    public function deleteMany(array $ids): void;

    /**
     * Check whether a single category has any direct children.
     */
    public function hasChildren(Category $category): bool;

    /**
     * Check whether any category in the list has children.
     * Uses a single EXISTS query — no N+1.
     */
    public function hasAnyChildren(array $ids): bool;

    /**
     * Confirm all given IDs exist and return the locked rows.
     * Used inside bulk-delete to prevent phantom-read races.
     */
    public function lockForDelete(array $ids): Collection;
}
