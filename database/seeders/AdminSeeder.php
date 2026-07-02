<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Create the default admin user.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@restoapp.com'],
            [
                'name' => 'Admin Restoran',
                'email' => 'admin@restoapp.com',
                'phone' => '081100000001',
                'password' => Hash::make('Admin@123456'),
                'role' => 'admin',
                'poin' => 0,
                'is_active' => true,
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]
        );

        // Assign Spatie role for middleware compatibility
        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
    }
}
