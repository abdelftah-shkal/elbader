<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CategoryServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a CategoryService with a real CategoryRepository.
     */
    private function makeService(): CategoryService
    {
        return new CategoryService(new CategoryRepository());
    }

    public function test_it_returns_paginated_categories_using_custom_paginator(): void
    {
        Category::create(['name' => 'Category A']);
        Category::create(['name' => 'Category B']);
        Category::create(['name' => 'Category C']);

        $paginated = $this->makeService()->getPaginatedCategories(perPage: 2);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginated);
        $this->assertEquals(3, $paginated->total());
        $this->assertEquals(2, $paginated->perPage());
        $this->assertCount(2, $paginated->items());
    }

    public function test_it_filters_and_paginates_categories_by_search(): void
    {
        Category::create(['name' => 'Electronics']);
        Category::create(['name' => 'Clothing']);
        Category::create(['name' => 'Electric Toys']);

        $paginated = $this->makeService()->getPaginatedCategories(search: 'Electr', perPage: 10);

        $this->assertEquals(2, $paginated->total());
        $names = collect($paginated->items())->pluck('name')->toArray();
        $this->assertContains('Electric Toys', $names);
        $this->assertContains('Electronics', $names);
    }

    public function test_get_descendant_ids_for_root_child_and_deep_hierarchy(): void
    {
        $electronics = Category::create(['name' => 'Electronics']);
        $phones      = Category::create(['name' => 'Phones',  'parent_id' => $electronics->id]);
        $android     = Category::create(['name' => 'Android', 'parent_id' => $phones->id]);
        $pixel       = Category::create(['name' => 'Pixel',   'parent_id' => $android->id]);

        $service = $this->makeService();

        // Deep hierarchy root
        $this->assertEqualsCanonicalizing(
            [$electronics->id, $phones->id, $android->id, $pixel->id],
            $service->getDescendantIds($electronics)->all()
        );

        // Mid child
        $this->assertEqualsCanonicalizing(
            [$phones->id, $android->id, $pixel->id],
            $service->getDescendantIds($phones)->all()
        );

        // Leaf node
        $this->assertEqualsCanonicalizing(
            [$pixel->id],
            $service->getDescendantIds($pixel)->all()
        );
    }

    public function test_get_descendant_ids_uses_single_query_for_entire_tree(): void
    {
        $root = Category::create(['name' => 'Level 0']);
        $l1   = Category::create(['name' => 'Level 1', 'parent_id' => $root->id]);
        $l2   = Category::create(['name' => 'Level 2', 'parent_id' => $l1->id]);
        $l3   = Category::create(['name' => 'Level 3', 'parent_id' => $l2->id]);
        $l4   = Category::create(['name' => 'Level 4', 'parent_id' => $l3->id]);

        $service = $this->makeService();

        DB::enableQueryLog();

        $descendants = $service->getDescendantIds($root);

        $queries = DB::getQueryLog();
        $this->assertCount(1, $queries);
        $this->assertCount(5, $descendants);

        DB::disableQueryLog();
    }

    public function test_get_tree_builds_recursive_hierarchy_without_n_plus_one_queries(): void
    {
        $root1  = Category::create(['name' => 'Electronics']);
        $phones = Category::create(['name' => 'Phones', 'parent_id' => $root1->id]);
        Category::create(['name' => 'Android', 'parent_id' => $phones->id]);

        $root2 = Category::create(['name' => 'Clothing']);
        Category::create(['name' => 'Shirts', 'parent_id' => $root2->id]);

        $service = $this->makeService();

        DB::enableQueryLog();

        $tree    = $service->getTree();
        $queries = DB::getQueryLog();

        $this->assertCount(1, $queries);
        $this->assertCount(2, $tree);

        $electronicsNode = $tree->firstWhere('name', 'Electronics');
        $this->assertCount(1, $electronicsNode->children);
        $this->assertEquals('Phones', $electronicsNode->children[0]->name);

        DB::disableQueryLog();
    }

    public function test_descendant_filtering_in_paginated_categories(): void
    {
        $electronics = Category::create(['name' => 'Electronics']);
        $phones      = Category::create(['name' => 'Phones',  'parent_id' => $electronics->id]);
        $android     = Category::create(['name' => 'Android', 'parent_id' => $phones->id]);
        $clothing    = Category::create(['name' => 'Clothing']);

        $result = $this->makeService()->getPaginatedCategories(categoryId: $phones->id);
        $names  = collect($result->items())->pluck('name')->all();

        $this->assertEqualsCanonicalizing(['Phones', 'Android'], $names);
        $this->assertNotContains('Electronics', $names);
        $this->assertNotContains('Clothing', $names);
    }

    public function test_duplicate_name_same_parent_rejected_different_parents_allowed(): void
    {
        $service     = $this->makeService();
        $electronics = Category::create(['name' => 'Electronics']);
        $clothing    = Category::create(['name' => 'Clothing']);

        // Phones under Electronics
        $service->create(['name' => 'Phones', 'parent_id' => $electronics->id]);

        // Phones under Clothing — ALLOWED (different parent)
        $phonesClothing = $service->create(['name' => 'Phones', 'parent_id' => $clothing->id]);
        $this->assertNotNull($phonesClothing->id);

        // Phones under Electronics again — REJECTED (same parent)
        $this->expectException(ValidationException::class);
        $service->create(['name' => 'Phones', 'parent_id' => $electronics->id]);
    }

    public function test_circular_parent_prevention(): void
    {
        $electronics = Category::create(['name' => 'Electronics']);
        $phones      = Category::create(['name' => 'Phones', 'parent_id' => $electronics->id]);

        $service = $this->makeService();

        // Self-parent
        try {
            $service->update($electronics, ['name' => 'Electronics', 'parent_id' => $electronics->id]);
            $this->fail('Expected ValidationException for self parent');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('parent_id', $e->errors());
        }

        // Descendant as parent
        try {
            $service->update($electronics, ['name' => 'Electronics', 'parent_id' => $phones->id]);
            $this->fail('Expected ValidationException for descendant parent');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('parent_id', $e->errors());
        }
    }

    public function test_bulk_delete_uses_single_query_for_children_check(): void
    {
        $cat1 = Category::create(['name' => 'Leaf 1']);
        $cat2 = Category::create(['name' => 'Leaf 2']);
        $cat3 = Category::create(['name' => 'Leaf 3']);

        $service = $this->makeService();

        DB::enableQueryLog();

        $service->bulkDelete([$cat1->id, $cat2->id, $cat3->id]);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // 1: lockForUpdate select, 2: single exists check, 3: delete
        $this->assertLessThanOrEqual(4, count($queries));
        $this->assertDatabaseMissing('categories', ['id' => $cat1->id]);
        $this->assertDatabaseMissing('categories', ['id' => $cat2->id]);
        $this->assertDatabaseMissing('categories', ['id' => $cat3->id]);
    }

    public function test_bulk_delete_nonexistent_id_fails(): void
    {
        $cat1 = Category::create(['name' => 'Leaf 1']);

        $this->expectException(ValidationException::class);
        $this->makeService()->bulkDelete([$cat1->id, 99999]);
    }

    public function test_category_service_uses_single_hierarchy_query_per_request_cache(): void
    {
        $electronics = Category::create(['name' => 'Electronics']);
        $phones      = Category::create(['name' => 'Phones',  'parent_id' => $electronics->id]);
        Category::create(['name' => 'Android', 'parent_id' => $phones->id]);

        $service = $this->makeService();

        DB::enableQueryLog();

        $service->getPaginatedCategories(categoryId: $electronics->id);
        $service->getAllCategories();
        $service->getTree();
        $service->getAvailableParents($phones);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $allCategoriesQueries = array_filter($queries, function ($q) {
            $sql = strtolower($q['query']);
            return str_contains($sql, 'select')
                && (str_contains($sql, 'from "categories"') || str_contains($sql, 'from `categories`'))
                && !str_contains($sql, 'where');
        });

        $this->assertCount(1, $allCategoriesQueries);
    }

    public function test_database_unique_constraint_handling(): void
    {
        $electronics = Category::create(['name' => 'Electronics']);

        DB::table('categories')->insert([
            'name'       => 'DuplicateName',
            'parent_id'  => $electronics->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = $this->makeService();

        try {
            $service->create(['name' => 'DuplicateName', 'parent_id' => $electronics->id]);
            $this->fail('Expected ValidationException due to duplicate name under same parent');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('name', $e->errors());
        }
    }

    public function test_get_available_parents_excludes_self_and_descendants(): void
    {
        $electronics = Category::create(['name' => 'Electronics']);
        $phones      = Category::create(['name' => 'Phones',  'parent_id' => $electronics->id]);
        $android     = Category::create(['name' => 'Android', 'parent_id' => $phones->id]);
        $clothing    = Category::create(['name' => 'Clothing']);

        $service = $this->makeService();

        $allParents = $service->getAvailableParents(null);
        $this->assertCount(4, $allParents);

        $availableForPhones = $service->getAvailableParents($phones);
        $ids = $availableForPhones->pluck('id')->all();

        $this->assertContains($electronics->id, $ids);
        $this->assertContains($clothing->id, $ids);
        $this->assertNotContains($phones->id, $ids);
        $this->assertNotContains($android->id, $ids);
    }
}
