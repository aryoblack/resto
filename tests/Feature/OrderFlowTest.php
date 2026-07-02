<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Menu;
use App\Models\MenuIngredientMap;
use App\Models\Order;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_order_and_stock_is_reduced()
    {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'customer']);
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        // Berikan role customer agar tidak kena 403 Forbidden
        $user->assignRole('customer');
        
        $table = Table::create([
            'table_number' => '5',
            'status' => 'available',
            'qr_code' => 'QR123',
        ]);

        $category = Category::create([
            'name' => 'Makanan Utama',
            'is_active' => true,
        ]);

        $menu = Menu::create([
            'category_id' => $category->id,
            'name' => 'Nasi Goreng',
            'description' => 'Enak',
            'price' => 20000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $inventory = Inventory::create([
            'ingredient_name' => 'Beras',
            'unit' => 'gram',
            'current_stock' => 5000,
            'min_stock' => 1000,
        ]);

        MenuIngredientMap::create([
            'menu_id' => $menu->id,
            'ingredient_id' => $inventory->id,
            'quantity_used' => 200, // 200 grams of rice per portion
        ]);

        // 2. Perform Request
        $payload = [
            'table_id' => $table->id,
            'order_type' => 'dine_in',
            'payment_method' => 'cash',
            'items' => [
                [
                    'menu_id' => $menu->id,
                    'quantity' => 2,
                    'variant_selected' => null,
                    'note' => 'Pedas',
                ]
            ]
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/customer/orders', $payload);

        // 3. Assertions
        $response->assertStatus(201)
                 ->assertJsonPath('data.order_status', 'Diterima');

        $orderId = $response->json('data.id');
        $this->assertDatabaseHas('order', ['id' => $orderId]);
        $this->assertDatabaseHas('order_item', [
            'order_id' => $orderId,
            'menu_id' => $menu->id,
            'quantity' => 2,
            'note' => 'Pedas'
        ]);

        // Check stock reduction
        // Inventory stock should decrease by 2 * 200 = 400 (5000 -> 4600)
        $this->assertDatabaseHas('inventory', [
            'id' => $inventory->id,
            'current_stock' => 4600,
        ]);
    }
}
