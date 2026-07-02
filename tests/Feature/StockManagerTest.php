<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use App\Events\StockCritical;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Menu;
use App\Models\MenuIngredientMap;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockMovement;
use App\Models\Table;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Feature tests for the Stock_Manager module.
 *
 * Covers:
 *   - Admin can list inventory
 *   - Admin can create ingredient
 *   - Admin can add stock (current_stock increases, stock_movement created)
 *   - Admin cannot add negative stock
 *   - Admin cannot add zero stock
 *   - Stock deduction when order is created (via MenuIngredientMap)
 *   - Stock deduction creates stock_movement type `out`
 *   - Critical stock detection (current_stock ≤ min_stock)
 *   - StockCritical event is broadcast when stock becomes critical
 *   - Stock history endpoint returns movements for an ingredient
 *   - DB transaction: both inventory update and stock_movement are created together
 *
 * Validates: Requirements 9.1, 9.2, 9.3, 9.4, 9.5, 9.6, 9.7
 */
class StockManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;
    private Inventory $ingredient;
    private Category $category;
    private Menu $menu;
    private Table $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->admin->assignRole('admin');

        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->customer->assignRole('customer');

        // Create a test ingredient
        $this->ingredient = Inventory::create([
            'ingredient_name' => 'Beras',
            'unit'            => 'kg',
            'current_stock'   => 50.0,
            'min_stock'       => 10.0,
            'supplier'        => 'Supplier A',
        ]);

        // Create category, menu, and table for order-related tests
        $this->category = Category::create(['name' => 'Makanan', 'sort_order' => 1]);

        $this->menu = Menu::create([
            'name'         => 'Nasi Goreng',
            'category_id'  => $this->category->id,
            'price'        => 20000,
            'stock'        => 100,
            'is_available' => true,
        ]);

        $this->table = Table::create([
            'table_number' => 'T01',
            'qr_code'      => 'test-qr-code',
            'status'       => 'available',
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function adminHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->admin->createToken('test')->plainTextToken];
    }

    private function customerHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->customer->createToken('test')->plainTextToken];
    }

    // -------------------------------------------------------------------------
    // 8.1 — InventoryController: index
    // -------------------------------------------------------------------------
    #[Test]
    public function test_admin_can_list_inventory(): void
    {
        $response = $this->withHeaders($this->adminHeaders())
            ->getJson('/api/admin/inventory');

        $response->assertStatus(200)
            ->assertJsonFragment(['ingredient_name' => 'Beras'])
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'ingredient_name',
                        'unit',
                        'current_stock',
                        'min_stock',
                        'supplier',
                        'is_critical',
                    ],
                ],
            ]);
    }
    #[Test]
    public function test_non_admin_cannot_list_inventory(): void
    {
        $response = $this->withHeaders($this->customerHeaders())
            ->getJson('/api/admin/inventory');

        $response->assertStatus(403);
    }
    #[Test]
    public function test_inventory_list_includes_critical_stock_indicator(): void
    {
        // Create a critical ingredient (current_stock ≤ min_stock)
        Inventory::create([
            'ingredient_name' => 'Garam',
            'unit'            => 'kg',
            'current_stock'   => 5.0,
            'min_stock'       => 10.0,
            'supplier'        => null,
        ]);

        $response = $this->withHeaders($this->adminHeaders())
            ->getJson('/api/admin/inventory');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Find the critical ingredient in the response
        $garamItem = collect($data)->firstWhere('ingredient_name', 'Garam');
        $this->assertNotNull($garamItem);
        $this->assertTrue($garamItem['is_critical']);

        // Non-critical ingredient
        $berasItem = collect($data)->firstWhere('ingredient_name', 'Beras');
        $this->assertNotNull($berasItem);
        $this->assertFalse($berasItem['is_critical']);
    }

    // -------------------------------------------------------------------------
    // 8.1 — InventoryController: store
    // -------------------------------------------------------------------------
    #[Test]
    public function test_admin_can_create_ingredient(): void
    {
        $response = $this->withHeaders($this->adminHeaders())
            ->postJson('/api/admin/inventory', [
                'ingredient_name' => 'Minyak Goreng',
                'unit'            => 'liter',
                'current_stock'   => 20.0,
                'min_stock'       => 5.0,
                'supplier'        => 'Supplier B',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['ingredient_name' => 'Minyak Goreng'])
            ->assertJsonPath('data.unit', 'liter');

        $this->assertDatabaseHas('inventory', [
            'ingredient_name' => 'Minyak Goreng',
            'unit'            => 'liter',
        ]);
    }
    #[Test]
    public function test_create_ingredient_requires_name_and_unit(): void
    {
        $response = $this->withHeaders($this->adminHeaders())
            ->postJson('/api/admin/inventory', [
                'current_stock' => 10.0,
                'min_stock'     => 2.0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ingredient_name', 'unit']);
    }
    #[Test]
    public function test_create_ingredient_rejects_negative_current_stock(): void
    {
        $response = $this->withHeaders($this->adminHeaders())
            ->postJson('/api/admin/inventory', [
                'ingredient_name' => 'Test',
                'unit'            => 'kg',
                'current_stock'   => -5.0,
                'min_stock'       => 2.0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_stock']);
    }

    // -------------------------------------------------------------------------
    // 8.2 — addStock: current_stock increases, stock_movement created
    // -------------------------------------------------------------------------
    #[Test]
    public function test_admin_can_add_stock_and_current_stock_increases(): void
    {
        $originalStock = (float) $this->ingredient->current_stock;

        $response = $this->withHeaders($this->adminHeaders())
            ->postJson("/api/admin/inventory/{$this->ingredient->id}/add-stock", [
                'quantity' => 10.0,
                'note'     => 'Pembelian rutin',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['type' => 'in']);

        $this->ingredient->refresh();
        $this->assertEquals($originalStock + 10.0, (float) $this->ingredient->current_stock);
    }
    #[Test]
    public function test_add_stock_creates_stock_movement_type_in(): void
    {
        $this->withHeaders($this->adminHeaders())
            ->postJson("/api/admin/inventory/{$this->ingredient->id}/add-stock", [
                'quantity' => 5.0,
                'note'     => 'Restock',
            ]);

        $this->assertDatabaseHas('stock_movement', [
            'ingredient_id'   => $this->ingredient->id,
            'quantity_change' => '5.000',
            'type'            => 'in',
            'note'            => 'Restock',
        ]);
    }

    // -------------------------------------------------------------------------
    // 8.5 — Validation: reject negative and zero stock
    // -------------------------------------------------------------------------
    #[Test]
    public function test_admin_cannot_add_negative_stock(): void
    {
        $response = $this->withHeaders($this->adminHeaders())
            ->postJson("/api/admin/inventory/{$this->ingredient->id}/add-stock", [
                'quantity' => -5.0,
                'note'     => 'Invalid',
            ]);

        $response->assertStatus(422);

        // Stock should remain unchanged
        $this->ingredient->refresh();
        $this->assertEquals(50.0, (float) $this->ingredient->current_stock);
    }
    #[Test]
    public function test_admin_cannot_add_zero_stock(): void
    {
        $response = $this->withHeaders($this->adminHeaders())
            ->postJson("/api/admin/inventory/{$this->ingredient->id}/add-stock", [
                'quantity' => 0,
                'note'     => 'Invalid',
            ]);

        $response->assertStatus(422);

        $this->ingredient->refresh();
        $this->assertEquals(50.0, (float) $this->ingredient->current_stock);
    }
    #[Test]
    public function test_stock_service_rejects_non_numeric_input(): void
    {
        $service = app(StockService::class);

        $this->expectException(ValidationException::class);

        $service->validateStockInput('abc');
    }
    #[Test]
    public function test_stock_service_rejects_negative_input(): void
    {
        $service = app(StockService::class);

        $this->expectException(ValidationException::class);

        $service->validateStockInput(-1.0);
    }
    #[Test]
    public function test_stock_service_rejects_zero_input(): void
    {
        $service = app(StockService::class);

        $this->expectException(ValidationException::class);

        $service->validateStockInput(0);
    }
    #[Test]
    public function test_stock_service_accepts_positive_input(): void
    {
        $service = app(StockService::class);

        $this->assertTrue($service->validateStockInput(1.5));
        $this->assertTrue($service->validateStockInput(100));
    }

    // -------------------------------------------------------------------------
    // 8.3 — deductStock: stock decreases, stock_movement type `out` created
    // -------------------------------------------------------------------------
    #[Test]
    public function test_deduct_stock_decreases_current_stock(): void
    {
        $service = app(StockService::class);

        $originalStock = (float) $this->ingredient->current_stock;

        $service->deductStock(
            ingredientId: $this->ingredient->id,
            quantity: 5.0,
            note: 'Test deduction',
        );

        $this->ingredient->refresh();
        $this->assertEquals($originalStock - 5.0, (float) $this->ingredient->current_stock);
    }
    #[Test]
    public function test_deduct_stock_creates_stock_movement_type_out(): void
    {
        $service = app(StockService::class);

        $service->deductStock(
            ingredientId: $this->ingredient->id,
            quantity: 3.0,
            note: 'Digunakan untuk masak',
        );

        $this->assertDatabaseHas('stock_movement', [
            'ingredient_id'   => $this->ingredient->id,
            'quantity_change' => '3.000',
            'type'            => 'out',
            'note'            => 'Digunakan untuk masak',
        ]);
    }

    // -------------------------------------------------------------------------
    // 8.3 — Auto-deduct when order is created via MenuIngredientMap
    // -------------------------------------------------------------------------
    #[Test]
    public function test_stock_is_deducted_when_order_is_created_via_menu_ingredient_map(): void
    {
        // Map: 1 portion of Nasi Goreng uses 0.2 kg of Beras
        MenuIngredientMap::create([
            'menu_id'       => $this->menu->id,
            'ingredient_id' => $this->ingredient->id,
            'quantity_used' => 0.2,
        ]);

        $originalStock = (float) $this->ingredient->current_stock; // 50.0

        // Create an order with 3 portions of Nasi Goreng
        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 60000,
            'order_status'   => 'Diterima',
            'payment_status' => 'pending',
            'order_type'     => 'dine_in',
        ]);

        $order->orderItems()->create([
            'menu_id'       => $this->menu->id,
            'quantity'      => 3,
            'price_at_time' => 20000,
        ]);

        $service = app(StockService::class);
        $service->deductStockForOrder($order);

        // Expected deduction: 0.2 kg × 3 portions = 0.6 kg
        $this->ingredient->refresh();
        $this->assertEqualsWithDelta($originalStock - 0.6, (float) $this->ingredient->current_stock, 0.001);
    }
    #[Test]
    public function test_stock_deduction_for_order_creates_stock_movement_with_order_id(): void
    {
        MenuIngredientMap::create([
            'menu_id'       => $this->menu->id,
            'ingredient_id' => $this->ingredient->id,
            'quantity_used' => 0.5,
        ]);

        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Diterima',
            'payment_status' => 'pending',
            'order_type'     => 'dine_in',
        ]);

        $order->orderItems()->create([
            'menu_id'       => $this->menu->id,
            'quantity'      => 2,
            'price_at_time' => 20000,
        ]);

        $service = app(StockService::class);
        $service->deductStockForOrder($order);

        $this->assertDatabaseHas('stock_movement', [
            'ingredient_id' => $this->ingredient->id,
            'type'          => 'out',
            'order_id'      => $order->id,
        ]);
    }
    #[Test]
    public function test_no_stock_deduction_when_menu_has_no_ingredient_mapping(): void
    {
        // No MenuIngredientMap created for this menu
        $originalStock = (float) $this->ingredient->current_stock;

        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Diterima',
            'payment_status' => 'pending',
            'order_type'     => 'dine_in',
        ]);

        $order->orderItems()->create([
            'menu_id'       => $this->menu->id,
            'quantity'      => 2,
            'price_at_time' => 20000,
        ]);

        $service = app(StockService::class);
        $service->deductStockForOrder($order);

        // Stock should remain unchanged
        $this->ingredient->refresh();
        $this->assertEquals($originalStock, (float) $this->ingredient->current_stock);
    }

    // -------------------------------------------------------------------------
    // 8.4 — Critical stock detection
    // -------------------------------------------------------------------------
    #[Test]
    public function test_ingredient_is_detected_as_critical_when_stock_equals_min_stock(): void
    {
        $ingredient = Inventory::create([
            'ingredient_name' => 'Telur',
            'unit'            => 'pcs',
            'current_stock'   => 10.0,
            'min_stock'       => 10.0,
            'supplier'        => null,
        ]);

        $this->assertTrue($ingredient->isCriticalStock());
    }
    #[Test]
    public function test_ingredient_is_detected_as_critical_when_stock_below_min_stock(): void
    {
        $ingredient = Inventory::create([
            'ingredient_name' => 'Gula',
            'unit'            => 'kg',
            'current_stock'   => 3.0,
            'min_stock'       => 5.0,
            'supplier'        => null,
        ]);

        $this->assertTrue($ingredient->isCriticalStock());
    }
    #[Test]
    public function test_ingredient_is_not_critical_when_stock_above_min_stock(): void
    {
        // Beras: current_stock = 50, min_stock = 10
        $this->assertFalse($this->ingredient->isCriticalStock());
    }
    #[Test]
    public function test_get_critical_stock_items_returns_only_critical_ingredients(): void
    {
        // Create a critical ingredient
        Inventory::create([
            'ingredient_name' => 'Garam',
            'unit'            => 'kg',
            'current_stock'   => 1.0,
            'min_stock'       => 5.0,
            'supplier'        => null,
        ]);

        $service = app(StockService::class);
        $criticalItems = $service->getCriticalStockItems();

        // Only Garam should be critical (Beras has 50 > 10)
        $this->assertCount(1, $criticalItems);
        $this->assertEquals('Garam', $criticalItems->first()->ingredient_name);
    }

    // -------------------------------------------------------------------------
    // 8.4 — StockCritical event broadcast
    // -------------------------------------------------------------------------
    #[Test]
    public function test_stock_critical_event_is_broadcast_when_stock_becomes_critical_after_deduction(): void
    {
        Event::fake([StockCritical::class]);

        // Set stock just above min_stock
        $this->ingredient->update(['current_stock' => 10.5, 'min_stock' => 10.0]);

        $service = app(StockService::class);

        // Deduct enough to make it critical (10.5 - 1.0 = 9.5 ≤ 10.0)
        $service->deductStock(
            ingredientId: $this->ingredient->id,
            quantity: 1.0,
            note: 'Test deduction to critical',
        );

        Event::assertDispatched(StockCritical::class, function (StockCritical $event) {
            return $event->ingredient->id === $this->ingredient->id;
        });
    }
    #[Test]
    public function test_stock_critical_event_is_not_broadcast_when_stock_is_above_min(): void
    {
        Event::fake([StockCritical::class]);

        // Stock is well above min_stock (50 - 5 = 45 > 10)
        $service = app(StockService::class);
        $service->deductStock(
            ingredientId: $this->ingredient->id,
            quantity: 5.0,
            note: 'Normal deduction',
        );

        Event::assertNotDispatched(StockCritical::class);
    }
    #[Test]
    public function test_stock_critical_event_is_broadcast_when_stock_is_still_critical_after_add(): void
    {
        Event::fake([StockCritical::class]);

        // Set stock below min_stock
        $this->ingredient->update(['current_stock' => 2.0, 'min_stock' => 10.0]);

        $service = app(StockService::class);

        // Add a small amount — still critical (2 + 3 = 5 ≤ 10)
        $service->addStock(
            ingredientId: $this->ingredient->id,
            quantity: 3.0,
            note: 'Partial restock',
        );

        Event::assertDispatched(StockCritical::class);
    }

    // -------------------------------------------------------------------------
    // 8.6 — Stock history endpoint
    // -------------------------------------------------------------------------
    #[Test]
    public function test_admin_can_get_stock_movement_history_for_ingredient(): void
    {
        // Create some movements
        StockMovement::create([
            'ingredient_id'   => $this->ingredient->id,
            'quantity_change' => 10.0,
            'type'            => 'in',
            'note'            => 'Pembelian',
            'created_at'      => now(),
        ]);

        StockMovement::create([
            'ingredient_id'   => $this->ingredient->id,
            'quantity_change' => 2.0,
            'type'            => 'out',
            'note'            => 'Digunakan',
            'created_at'      => now(),
        ]);

        $response = $this->withHeaders($this->adminHeaders())
            ->getJson("/api/admin/inventory/{$this->ingredient->id}/movements");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'ingredient_id',
                        'quantity_change',
                        'type',
                        'note',
                        'order_id',
                        'created_by',
                        'created_at',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);
    }
    #[Test]
    public function test_stock_history_only_returns_movements_for_specified_ingredient(): void
    {
        $otherIngredient = Inventory::create([
            'ingredient_name' => 'Gula',
            'unit'            => 'kg',
            'current_stock'   => 20.0,
            'min_stock'       => 5.0,
            'supplier'        => null,
        ]);

        StockMovement::create([
            'ingredient_id'   => $this->ingredient->id,
            'quantity_change' => 5.0,
            'type'            => 'in',
            'note'            => 'For Beras',
            'created_at'      => now(),
        ]);

        StockMovement::create([
            'ingredient_id'   => $otherIngredient->id,
            'quantity_change' => 3.0,
            'type'            => 'in',
            'note'            => 'For Gula',
            'created_at'      => now(),
        ]);

        $response = $this->withHeaders($this->adminHeaders())
            ->getJson("/api/admin/inventory/{$this->ingredient->id}/movements");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->ingredient->id, $data[0]['ingredient_id']);
    }

    // -------------------------------------------------------------------------
    // 8.7 — DB transaction: both inventory update and stock_movement created together
    // -------------------------------------------------------------------------
    #[Test]
    public function test_add_stock_is_atomic_inventory_and_movement_both_created(): void
    {
        $service = app(StockService::class);

        $movement = $service->addStock(
            ingredientId: $this->ingredient->id,
            quantity: 15.0,
            note: 'Transaction test',
        );

        // Both the inventory update and the movement record must exist
        $this->ingredient->refresh();
        $this->assertEquals(65.0, (float) $this->ingredient->current_stock);

        $this->assertDatabaseHas('stock_movement', [
            'id'              => $movement->id,
            'ingredient_id'   => $this->ingredient->id,
            'quantity_change' => '15.000',
            'type'            => 'in',
        ]);
    }
    #[Test]
    public function test_deduct_stock_is_atomic_inventory_and_movement_both_created(): void
    {
        $service = app(StockService::class);

        $movement = $service->deductStock(
            ingredientId: $this->ingredient->id,
            quantity: 8.0,
            note: 'Transaction test deduct',
        );

        $this->ingredient->refresh();
        $this->assertEquals(42.0, (float) $this->ingredient->current_stock);

        $this->assertDatabaseHas('stock_movement', [
            'id'              => $movement->id,
            'ingredient_id'   => $this->ingredient->id,
            'quantity_change' => '8.000',
            'type'            => 'out',
        ]);
    }

    // -------------------------------------------------------------------------
    // 8.1 — show, update, destroy
    // -------------------------------------------------------------------------
    #[Test]
    public function test_admin_can_view_single_ingredient(): void
    {
        $response = $this->withHeaders($this->adminHeaders())
            ->getJson("/api/admin/inventory/{$this->ingredient->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.ingredient_name', 'Beras')
            ->assertJsonPath('data.unit', 'kg');
    }
    #[Test]
    public function test_admin_can_update_ingredient(): void
    {
        $response = $this->withHeaders($this->adminHeaders())
            ->putJson("/api/admin/inventory/{$this->ingredient->id}", [
                'ingredient_name' => 'Beras Premium',
                'unit'            => 'kg',
                'current_stock'   => 50.0,
                'min_stock'       => 15.0,
                'supplier'        => 'Supplier Baru',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.ingredient_name', 'Beras Premium')
            ->assertJsonPath('data.min_stock', '15.000');

        $this->assertDatabaseHas('inventory', [
            'id'              => $this->ingredient->id,
            'ingredient_name' => 'Beras Premium',
        ]);
    }
    #[Test]
    public function test_admin_can_delete_ingredient(): void
    {
        $response = $this->withHeaders($this->adminHeaders())
            ->deleteJson("/api/admin/inventory/{$this->ingredient->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('inventory', ['id' => $this->ingredient->id]);
    }
}
