<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          $electronics = Category::create([
            'name' => 'Electronics',
            'parent_id' => null,
        ]);

        $phones = Category::create([
            'name' => 'Phones',
            'parent_id' => $electronics->id,
        ]);

        Category::create([
            'name' => 'Android',
            'parent_id' => $phones->id,
        ]);

        Category::create([
            'name' => 'iPhone',
            'parent_id' => $phones->id,
        ]);

        $computers = Category::create([
            'name' => 'Computers',
            'parent_id' => $electronics->id,
        ]);

        Category::create([
            'name' => 'Laptops',
            'parent_id' => $computers->id,
        ]);

        Category::create([
            'name' => 'Desktops',
            'parent_id' => $computers->id,
        ]);

        // Clothing
        $clothing = Category::create([
            'name' => 'Clothing',
            'parent_id' => null,
        ]);

        $men = Category::create([
            'name' => 'Men',
            'parent_id' => $clothing->id,
        ]);

        Category::create([
            'name' => 'Shirts',
            'parent_id' => $men->id,
        ]);

        Category::create([
            'name' => 'Pants',
            'parent_id' => $men->id,
        ]);

        $women = Category::create([
            'name' => 'Women',
            'parent_id' => $clothing->id,
        ]);

        Category::create([
            'name' => 'Dresses',
            'parent_id' => $women->id,
        ]);

        Category::create([
            'name' => 'Skirts',
            'parent_id' => $women->id,
        ]);
    }
}
