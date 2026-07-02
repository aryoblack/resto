<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupplierManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->admin->assignRole('admin');

        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->customer->assignRole('customer');
    }

    private function adminHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->admin->createToken('test')->plainTextToken];
    }

    private function customerHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->customer->createToken('test')->plainTextToken];
    }

    #[Test]
    public function admin_can_create_supplier(): void
    {
        $response = $this->withHeaders($this->adminHeaders())
            ->postJson('/api/admin/suppliers', [
                'name' => 'Supplier Sayur Jaya',
                'contact_person' => 'Budi',
                'phone' => '08123456789',
                'email' => 'sales@sayur.test',
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Supplier Sayur Jaya')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Supplier Sayur Jaya',
            'contact_person' => 'Budi',
        ]);
    }

    #[Test]
    public function non_admin_cannot_manage_suppliers(): void
    {
        $response = $this->withHeaders($this->customerHeaders())
            ->getJson('/api/admin/suppliers');

        $response->assertStatus(403);
    }

    #[Test]
    public function inventory_can_use_master_supplier_as_primary_supplier(): void
    {
        $supplier = Supplier::create([
            'name' => 'Supplier Daging Utama',
            'is_active' => true,
        ]);

        $response = $this->withHeaders($this->adminHeaders())
            ->postJson('/api/admin/inventory', [
                'ingredient_name' => 'Daging Sapi',
                'unit' => 'kg',
                'current_stock' => 12,
                'min_stock' => 3,
                'supplier_id' => $supplier->id,
                'last_price' => 95000,
                'lead_time_days' => 2,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.supplier', 'Supplier Daging Utama')
            ->assertJsonPath('data.supplier_id', $supplier->id)
            ->assertJsonPath('data.last_price', '95000.00');

        $this->assertDatabaseHas('inventory_supplier', [
            'supplier_id' => $supplier->id,
            'is_primary' => true,
        ]);
    }

    #[Test]
    public function inventory_rejects_purchase_metadata_without_supplier(): void
    {
        $response = $this->withHeaders($this->adminHeaders())
            ->postJson('/api/admin/inventory', [
                'ingredient_name' => 'Cabai',
                'unit' => 'kg',
                'current_stock' => 5,
                'min_stock' => 1,
                'last_price' => 45000,
                'lead_time_days' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['supplier_id']);
    }

    #[Test]
    public function supplier_in_use_cannot_be_deleted(): void
    {
        $supplier = Supplier::create([
            'name' => 'Supplier Beras Utama',
            'is_active' => true,
        ]);

        $ingredient = Inventory::create([
            'ingredient_name' => 'Beras',
            'unit' => 'kg',
            'current_stock' => 50,
            'min_stock' => 10,
            'supplier' => $supplier->name,
        ]);

        $ingredient->suppliers()->attach($supplier->id, ['is_primary' => true]);

        $response = $this->withHeaders($this->adminHeaders())
            ->deleteJson("/api/admin/suppliers/{$supplier->id}");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Supplier tidak dapat dihapus karena masih dipakai bahan baku.');

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }
}
