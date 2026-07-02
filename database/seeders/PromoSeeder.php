<?php

namespace Database\Seeders;

use App\Models\Promo;
use Illuminate\Database\Seeder;

class PromoSeeder extends Seeder
{
    /**
     * Seed customer promo and voucher data.
     */
    public function run(): void
    {
        Promo::updateOrCreate(
            ['code' => 'PROMO20'],
            [
                'name' => 'Diskon 20%',
                'type' => 'percentage',
                'value' => 20,
                'min_purchase' => 0,
                'max_discount' => 20000,
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->addYear()->toDateString(),
                'is_active' => true,
                'usage_limit' => null,
            ],
        );
    }
}
