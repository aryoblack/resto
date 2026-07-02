<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminSeeder::class,
            DemoUserSeeder::class,
            CategorySeeder::class,
            TableSeeder::class,
            MenuSeeder::class,
            VariantSeeder::class,
            PromoSeeder::class,
            SystemSettingSeeder::class,
        ]);
    }
}
