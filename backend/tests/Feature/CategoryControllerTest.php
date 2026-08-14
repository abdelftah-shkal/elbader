<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_load_categories_page(): void
    {
        Category::create(['name' => 'Electronics']);

        $response = $this->get('/categories');

        $response->assertStatus(200);
        $response->assertSee('Category Management');
        $response->assertSee('Electronics');
    }

    public function test_ajax_load_returns_partial_table(): void
    {
        Category::create(['name' => 'Electronics']);

        $response = $this->get('/categories', [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Electronics');
        $response->assertDontSee('<!DOCTYPE html>');
    }

    public function test_can_create_category(): void
    {
        $response = $this->postJson('/categories', [
            'name' => 'Phones',
            'parent_id' => null,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'data' => [
                'name' => 'Phones',
            ],
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Phones',
            'parent_id' => null,
        ]);
    }

    public function test_cannot_create_duplicate_category_under_same_parent(): void
    {
        $parent = Category::create(['name' => 'Electronics']);
        Category::create(['name' => 'Phones', 'parent_id' => $parent->id]);

        $response = $this->postJson('/categories', [
            'name' => 'Phones',
            'parent_id' => $parent->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_cannot_create_duplicate_root_category_name(): void
    {
        Category::create(['name' => 'Electronics', 'parent_id' => null]);

        $response = $this->postJson('/categories', [
            'name' => 'Electronics',
            'parent_id' => null,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_parent_options_excludes_self_and_descendants(): void
    {
        $electronics = Category::create(['name' => 'Electronics']);
        $phones = Category::create(['name' => 'Phones', 'parent_id' => $electronics->id]);
        $android = Category::create(['name' => 'Android', 'parent_id' => $phones->id]);
        $clothing = Category::create(['name' => 'Clothing']);

        // Request available parents when editing 'Phones' (should exclude Phones and Android)
        $response = $this->getJson("/categories/parents/{$phones->id}");

        $response->assertStatus(200);
        $data = $response->json('data');

        $ids = collect($data)->pluck('id')->all();

        $this->assertContains($electronics->id, $ids);
        $this->assertContains($clothing->id, $ids);
        $this->assertNotContains($phones->id, $ids);
        $this->assertNotContains($android->id, $ids);
    }

    public function test_cannot_set_descendant_as_parent(): void
    {
        $electronics = Category::create(['name' => 'Electronics']);
        $phones = Category::create(['name' => 'Phones', 'parent_id' => $electronics->id]);
        $android = Category::create(['name' => 'Android', 'parent_id' => $phones->id]);

        // Try setting 'Android' as parent of 'Electronics'
        $response = $this->putJson("/categories/{$electronics->id}", [
            'name' => 'Electronics',
            'parent_id' => $android->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['parent_id']);
    }

    public function test_cannot_delete_category_with_children(): void
    {
        $parent = Category::create(['name' => 'Electronics']);
        Category::create(['name' => 'Phones', 'parent_id' => $parent->id]);

        $response = $this->deleteJson("/categories/{$parent->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('categories', ['id' => $parent->id]);
    }

    public function test_can_delete_leaf_category(): void
    {
        $category = Category::create(['name' => 'Headphones']);

        $response = $this->deleteJson("/categories/{$category->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_bulk_delete_leaf_categories(): void
    {
        $cat1 = Category::create(['name' => 'Item 1']);
        $cat2 = Category::create(['name' => 'Item 2']);

        $response = $this->deleteJson('/categories/bulk-delete', [
            'ids' => [$cat1->id, $cat2->id],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('categories', ['id' => $cat1->id]);
        $this->assertDatabaseMissing('categories', ['id' => $cat2->id]);
    }

    public function test_bulk_delete_fails_and_rolls_back_if_any_category_has_children(): void
    {
        $parent = Category::create(['name' => 'Parent']);
        Category::create(['name' => 'Child', 'parent_id' => $parent->id]);
        $leaf = Category::create(['name' => 'Leaf']);

        $response = $this->deleteJson('/categories/bulk-delete', [
            'ids' => [$parent->id, $leaf->id],
        ]);

        $response->assertStatus(422);
        // Neither category should be deleted
        $this->assertDatabaseHas('categories', ['id' => $parent->id]);
        $this->assertDatabaseHas('categories', ['id' => $leaf->id]);
    }

    public function test_pagination_links_preserve_query_string_parameters(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            Category::create(['name' => "Gadget $i"]);
        }

        $response = $this->get('/categories?search=Gadget&page=1', [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
        $response->assertSee('search=Gadget');
    }
}
