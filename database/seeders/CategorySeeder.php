<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Create sample menu categories.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Makanan', 'sort_order' => 1],
            ['name' => 'Minuman', 'sort_order' => 2],
            ['name' => 'Snack', 'sort_order' => 3],
            ['name' => 'Dessert', 'sort_order' => 4],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                ['sort_order' => $category['sort_order']]
            );
        }
    }
}
