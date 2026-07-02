<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class MenuSeeder extends Seeder
{
    /**
     * Create starter menu data for the restaurant.
     */
    public function run(): void
    {
        $categories = [
            'Makanan' => 1,
            'Minuman' => 2,
            'Snack' => 3,
            'Dessert' => 4,
        ];

        foreach ($categories as $name => $sortOrder) {
            Category::firstOrCreate(
                ['name' => $name],
                ['sort_order' => $sortOrder],
            );
        }

        $menus = [
            [
                'category' => 'Makanan',
                'name' => 'Nasi Goreng Spesial',
                'price' => 28000,
                'stock' => 50,
                'description' => 'Nasi goreng dengan telur, ayam suwir, bakso, dan acar.',
            ],
            [
                'category' => 'Makanan',
                'name' => 'Ayam Geprek Sambal Bawang',
                'price' => 26000,
                'stock' => 45,
                'description' => 'Ayam crispy geprek dengan sambal bawang pedas.',
            ],
            [
                'category' => 'Makanan',
                'name' => 'Mie Goreng Jawa',
                'price' => 24000,
                'stock' => 40,
                'description' => 'Mie goreng bumbu Jawa dengan sayuran dan telur.',
            ],
            [
                'category' => 'Makanan',
                'name' => 'Sate Ayam',
                'price' => 32000,
                'stock' => 35,
                'description' => 'Sate ayam bumbu kacang dengan lontong.',
            ],
            [
                'category' => 'Minuman',
                'name' => 'Es Teh Manis',
                'price' => 8000,
                'stock' => 100,
                'description' => 'Teh manis dingin segar.',
            ],
            [
                'category' => 'Minuman',
                'name' => 'Es Jeruk',
                'price' => 12000,
                'stock' => 80,
                'description' => 'Jeruk peras dingin dengan gula secukupnya.',
            ],
            [
                'category' => 'Minuman',
                'name' => 'Kopi Susu Gula Aren',
                'price' => 18000,
                'stock' => 60,
                'description' => 'Kopi susu dingin dengan gula aren.',
            ],
            [
                'category' => 'Snack',
                'name' => 'Kentang Goreng',
                'price' => 16000,
                'stock' => 60,
                'description' => 'Kentang goreng renyah dengan saus.',
            ],
            [
                'category' => 'Snack',
                'name' => 'Tahu Crispy',
                'price' => 14000,
                'stock' => 60,
                'description' => 'Tahu crispy dengan cabai rawit dan saus.',
            ],
            [
                'category' => 'Snack',
                'name' => 'Pisang Goreng Keju',
                'price' => 17000,
                'stock' => 45,
                'description' => 'Pisang goreng dengan topping keju dan susu.',
            ],
            [
                'category' => 'Dessert',
                'name' => 'Puding Cokelat',
                'price' => 15000,
                'stock' => 40,
                'description' => 'Puding cokelat lembut dengan vla.',
            ],
            [
                'category' => 'Dessert',
                'name' => 'Es Krim Vanilla',
                'price' => 14000,
                'stock' => 40,
                'description' => 'Es krim vanilla dengan topping cokelat.',
            ],
        ];

        foreach ($menus as $menu) {
            $category = Category::where('name', $menu['category'])->firstOrFail();

            Menu::updateOrCreate(
                ['name' => $menu['name']],
                [
                    'category_id' => $category->id,
                    'price' => $menu['price'],
                    'stock' => $menu['stock'],
                    'description' => $menu['description'],
                    'image_url' => null,
                    'is_available' => true,
                ],
            );
        }

        try {
            Cache::increment('menu:generation');
        } catch (\Throwable) {
            // Cache invalidation is best-effort during local seeding.
        }
    }
}
