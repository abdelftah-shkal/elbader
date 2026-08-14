<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Utils\Paginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_paginates_a_query(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            Category::create(['name' => "Category {$i}"]);
        }

        $result = Paginator::paginate(Category::query(), 10, 1);

        $this->assertIsArray($result);
        $this->assertEquals(25, $result['pagination']['total']);
        $this->assertEquals(10, $result['pagination']['per_page']);
        $this->assertEquals(1, $result['pagination']['current_page']);
        $this->assertEquals(3, $result['pagination']['last_page']);
        $this->assertEquals(1, $result['pagination']['from']);
        $this->assertEquals(10, $result['pagination']['to']);
        $this->assertCount(10, $result['data']);
    }

    public function test_it_paginates_second_page_correctly(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            Category::create(['name' => "Category {$i}"]);
        }

        $result = Paginator::paginate(Category::query(), 10, 2);

        $this->assertEquals(2, $result['pagination']['current_page']);
        $this->assertEquals(11, $result['pagination']['from']);
        $this->assertEquals(20, $result['pagination']['to']);
        $this->assertCount(10, $result['data']);
    }

    public function test_it_clamps_per_page_to_maximum_100(): void
    {
        for ($i = 1; $i <= 150; $i++) {
            Category::create(['name' => "Cat {$i}"]);
        }

        $result = Paginator::paginate(Category::query(), 99999, 1);

        $this->assertEquals(100, $result['pagination']['per_page']);
        $this->assertCount(100, $result['data']);

        $resultZero = Paginator::paginate(Category::query(), 0, 1);
        $this->assertEquals(1, $resultZero['pagination']['per_page']);
    }
}
