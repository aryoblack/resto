<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    /**
     * Create default demo users for non-admin roles.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Pelanggan Demo',
                'email' => 'customer@restoapp.com',
                'phone' => '081100000002',
                'password' => 'Customer@123456',
                'role' => 'customer',
            ],
            [
                'name' => 'Waiter Restoran',
                'email' => 'waiter@restoapp.com',
                'phone' => '081100000003',
                'password' => 'Waiter@123456',
                'role' => 'waiter',
            ],
            [
                'name' => 'Kasir Restoran',
                'email' => 'kasir@restoapp.com',
                'phone' => '081100000005',
                'password' => 'Kasir@123456',
                'role' => 'waiter',
            ],
            [
                'name' => 'Chef Dapur',
                'email' => 'chef@restoapp.com',
                'phone' => '081100000004',
                'password' => 'Chef@123456',
                'role' => 'chef',
            ],
        ];

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'password' => Hash::make($data['password']),
                    'role' => $data['role'],
                    'poin' => 0,
                    'is_active' => true,
                    'failed_login_attempts' => 0,
                    'locked_until' => null,
                ]
            );

            if (! $user->hasRole($data['role'])) {
                $user->assignRole($data['role']);
            }
        }
    }
}
