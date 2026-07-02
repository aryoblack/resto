<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_activate_inactive_staff(): void
    {
        $staff = User::factory()->create([
            'role' => 'waiter',
            'is_active' => false,
        ]);
        $staff->assignRole('waiter');

        $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/admin/staff/{$staff->id}/activate")
            ->assertOk()
            ->assertJsonFragment(['message' => 'Akun karyawan berhasil diaktifkan.']);

        $this->assertTrue($staff->fresh()->is_active);
    }

    public function test_admin_can_deactivate_active_staff(): void
    {
        $staff = User::factory()->create([
            'role' => 'chef',
            'is_active' => true,
        ]);
        $staff->assignRole('chef');

        $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/admin/staff/{$staff->id}/deactivate")
            ->assertOk()
            ->assertJsonFragment(['message' => 'Akun karyawan berhasil dinonaktifkan dan semua sesi telah dicabut.']);

        $this->assertFalse($staff->fresh()->is_active);
    }

    public function test_customer_cannot_activate_staff(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $customer->assignRole('customer');

        $staff = User::factory()->create([
            'role' => 'waiter',
            'is_active' => false,
        ]);
        $staff->assignRole('waiter');

        $this->actingAs($customer, 'sanctum')
            ->patchJson("/api/admin/staff/{$staff->id}/activate")
            ->assertForbidden();
    }

    public function test_admin_role_is_valid_for_staff_creation(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/staff', [
                'name' => 'Admin Baru',
                'email' => 'admin-baru@example.test',
                'role' => 'admin',
            ])
            ->assertCreated()
            ->assertJsonPath('user.role', 'admin');

        $this->assertDatabaseHas('users', [
            'email' => 'admin-baru@example.test',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
