<?php

namespace Tests\Unit;

use App\Utils\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class PaginatorTest extends TestCase
{
    public function test_it_paginates_a_collection(): void
    {
        $data = collect(range(1, 25));
        $paginated = Paginator::paginate($data, 10, 1);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginated);
        $this->assertEquals(25, $paginated->total());
        $this->assertEquals(10, $paginated->perPage());
        $this->assertEquals(1, $paginated->currentPage());
        $this->assertEquals(3, $paginated->lastPage());
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 10], $paginated->items());
    }

    public function test_it_paginates_second_page_correctly(): void
    {
        $data = collect(range(1, 25));
        $paginated = Paginator::paginate($data, 10, 2);

        $this->assertEquals(2, $paginated->currentPage());
        $this->assertEquals([11, 12, 13, 14, 15, 16, 17, 18, 19, 20], $paginated->items());
    }

    public function test_it_paginates_an_array(): void
    {
        $data = range(1, 15);
        $paginated = Paginator::paginate($data, 5, 2);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginated);
        $this->assertEquals(15, $paginated->total());
        $this->assertEquals(5, $paginated->perPage());
        $this->assertEquals(2, $paginated->currentPage());
        $this->assertEquals([6, 7, 8, 9, 10], $paginated->items());
    }

    public function test_instance_method_make_paginated(): void
    {
        $paginator = new Paginator();
        $data = [1, 2, 3];
        $paginated = $paginator->makePaginated($data, 2, 1);

        $this->assertEquals(3, $paginated->total());
        $this->assertEquals([1, 2], $paginated->items());
    }

    public function test_it_clamps_per_page_to_maximum_100(): void
    {
        $data = collect(range(1, 200));

        // Attempting perPage = 99999 should be clamped to 100
        $paginated = Paginator::paginate($data, 99999, 1);
        $this->assertEquals(100, $paginated->perPage());
        $this->assertCount(100, $paginated->items());

        // Attempting negative or zero perPage should be clamped to 1
        $paginatedZero = Paginator::paginate($data, 0, 1);
        $this->assertEquals(1, $paginatedZero->perPage());
    }
}
