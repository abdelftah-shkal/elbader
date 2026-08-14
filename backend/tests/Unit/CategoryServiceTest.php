<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class CategoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_paginated_categories_using_custom_paginator(): void
    {
        Category::create(['name' => 'Category A']);
        Category::create(['name' => 'Category B']);
        Category::create(['name' => 'Category C']);

        $service = new CategoryService();
        $paginated = $service->getPaginatedCategories(perPage: 2);

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

        $service = new CategoryService();
        $paginated = $service->getPaginatedCategories(search: 'Electr', perPage: 10);

        $this->assertEquals(2, $paginated->total());
        $names = collect($paginated->items())->pluck('name')->toArray();
        $this->assertContains('Electric Toys', $names);
        $this->assertContains('Electronics', $names);
    }
}
