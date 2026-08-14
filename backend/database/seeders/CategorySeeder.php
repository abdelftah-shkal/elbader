<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Electronics Branch
        $electronics = Category::create(['name' => 'Electronics', 'parent_id' => null]);
        $phones = Category::create(['name' => 'Phones', 'parent_id' => $electronics->id]);
        $android = Category::create(['name' => 'Android', 'parent_id' => $phones->id]);
        $samsung = Category::create(['name' => 'Samsung', 'parent_id' => $android->id]);
        Category::create(['name' => 'Galaxy S Series', 'parent_id' => $samsung->id]);
        Category::create(['name' => 'Google Pixel', 'parent_id' => $android->id]);
        Category::create(['name' => 'iPhone', 'parent_id' => $phones->id]);

        $computers = Category::create(['name' => 'Computers', 'parent_id' => $electronics->id]);
        Category::create(['name' => 'Laptops', 'parent_id' => $computers->id]);
        Category::create(['name' => 'Desktops', 'parent_id' => $computers->id]);

        $audio = Category::create(['name' => 'Audio', 'parent_id' => $electronics->id]);
        Category::create(['name' => 'Headphones', 'parent_id' => $audio->id]);
        Category::create(['name' => 'Speakers', 'parent_id' => $audio->id]);

        // 2. Clothing Branch
        $clothing = Category::create(['name' => 'Clothing', 'parent_id' => null]);
        $men = Category::create(['name' => 'Men', 'parent_id' => $clothing->id]);
        Category::create(['name' => 'Shirts', 'parent_id' => $men->id]);
        Category::create(['name' => 'Pants', 'parent_id' => $men->id]);

        $women = Category::create(['name' => 'Women', 'parent_id' => $clothing->id]);
        Category::create(['name' => 'Dresses', 'parent_id' => $women->id]);
        Category::create(['name' => 'Skirts', 'parent_id' => $women->id]);

        $kids = Category::create(['name' => 'Kids', 'parent_id' => $clothing->id]);
        Category::create(['name' => 'Footwear', 'parent_id' => $kids->id]);

        // 3. Home & Kitchen Branch
        $home = Category::create(['name' => 'Home & Kitchen', 'parent_id' => null]);
        $furniture = Category::create(['name' => 'Furniture', 'parent_id' => $home->id]);
        Category::create(['name' => 'Beds', 'parent_id' => $furniture->id]);
        Category::create(['name' => 'Tables', 'parent_id' => $furniture->id]);
        Category::create(['name' => 'Chairs', 'parent_id' => $furniture->id]);

        $appliances = Category::create(['name' => 'Appliances', 'parent_id' => $home->id]);
        Category::create(['name' => 'Refrigerators', 'parent_id' => $appliances->id]);
        Category::create(['name' => 'Washing Machines', 'parent_id' => $appliances->id]);

        // 4. Books & Media Branch
        $booksMedia = Category::create(['name' => 'Books & Media', 'parent_id' => null]);
        $books = Category::create(['name' => 'Books', 'parent_id' => $booksMedia->id]);
        Category::create(['name' => 'Fiction', 'parent_id' => $books->id]);
        Category::create(['name' => 'Non-Fiction', 'parent_id' => $books->id]);
        Category::create(['name' => 'Sci-Fi', 'parent_id' => $books->id]);

        // 5. Sports & Outdoors Branch
        $sports = Category::create(['name' => 'Sports & Outdoors', 'parent_id' => null]);
        $exercise = Category::create(['name' => 'Exercise Equipment', 'parent_id' => $sports->id]);
        Category::create(['name' => 'Treadmills', 'parent_id' => $exercise->id]);
        Category::create(['name' => 'Dumbbells', 'parent_id' => $exercise->id]);

        // 6. Automotive Branch
        $automotive = Category::create(['name' => 'Automotive', 'parent_id' => null]);
        $parts = Category::create(['name' => 'Car Parts', 'parent_id' => $automotive->id]);
        Category::create(['name' => 'Tires', 'parent_id' => $parts->id]);
        Category::create(['name' => 'Batteries', 'parent_id' => $parts->id]);
    }
}
