<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class VariantSeeder extends Seeder
{
    /**
     * Create starter menu variants.
     */
    public function run(): void
    {
        $variantsByMenu = [
            'Nasi Goreng Spesial' => [
                ['variant_name' => 'Regular', 'extra_price' => 0],
                ['variant_name' => 'Jumbo', 'extra_price' => 5000],
            ],
            'Ayam Geprek Sambal Bawang' => [
                ['variant_name' => 'Regular', 'extra_price' => 0],
                ['variant_name' => 'Extra Sambal', 'extra_price' => 3000],
            ],
            'Mie Goreng Jawa' => [
                ['variant_name' => 'Regular', 'extra_price' => 0],
                ['variant_name' => 'Jumbo', 'extra_price' => 5000],
            ],
            'Sate Ayam' => [
                ['variant_name' => '10 Tusuk', 'extra_price' => 0],
                ['variant_name' => '15 Tusuk', 'extra_price' => 12000],
            ],
            'Es Teh Manis' => [
                ['variant_name' => 'Regular', 'extra_price' => 0],
                ['variant_name' => 'Large', 'extra_price' => 3000],
            ],
            'Es Jeruk' => [
                ['variant_name' => 'Regular', 'extra_price' => 0],
                ['variant_name' => 'Large', 'extra_price' => 4000],
            ],
            'Kopi Susu Gula Aren' => [
                ['variant_name' => 'Regular', 'extra_price' => 0],
                ['variant_name' => 'Extra Shot', 'extra_price' => 5000],
            ],
        ];

        foreach ($variantsByMenu as $menuName => $variants) {
            $menu = Menu::where('name', $menuName)->first();
            if (! $menu) {
                continue;
            }

            foreach ($variants as $variant) {
                $menu->variants()->updateOrCreate(
                    ['variant_name' => $variant['variant_name']],
                    ['extra_price' => $variant['extra_price']],
                );
            }
        }

        try {
            Cache::increment('menu:generation');
        } catch (\Throwable) {
            // Cache invalidation is best-effort during local seeding.
        }
    }
}
