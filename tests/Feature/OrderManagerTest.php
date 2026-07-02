<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Promo;
use App\Models\SystemSetting;
use App\Models\Table;
use App\Models\User;
use App\Models\Variant;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Feature tests for the Order_Manager module.
 *
 * Covers:
 *   - Order creation (happy path)
 *   - Stock validation (insufficient stock rejection)
 *   - State machine transitions (valid and invalid)
 *   - Total calculation formula
 *   - Table auto-release
 *   - Admin cancellation
 *   - Non-admin cancellation rejection
 *   - KDS endpoint (only Dimasak orders)
 *
 * Validates: Requirements 5.5, 5.6, 7.2, 7.3, 10.3
 */
class OrderManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;
    private User $waiter;
    private User $chef;
    private Category $category;
    private Menu $menu;
    private Table $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        // Seed system settings for tax and service charge
        SystemSetting::updateOrCreate(['key' => 'tax_percentage'], ['value' => '10']);
        SystemSetting::updateOrCreate(['key' => 'service_charge_percentage'], ['value' => '5']);

        // Create users
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->admin->assignRole('admin');

        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->customer->assignRole('customer');

        $this->waiter = User::factory()->create(['role' => 'waiter']);
        $this->waiter->assignRole('waiter');

        $this->chef = User::factory()->create(['role' => 'chef']);
        $this->chef->assignRole('chef');

        // Create category and menu
        $this->category = Category::create(['name' => 'Makanan', 'sort_order' => 1]);

        $this->menu = Menu::create([
            'name'         => 'Nasi Goreng',
            'category_id'  => $this->category->id,
            'price'        => 20000,
            'stock'        => 10,
            'is_available' => true,
        ]);

        // Create a table
        $this->table = Table::create([
            'table_number' => 'T01',
            'qr_code'      => 'test-qr-code',
            'status'       => 'occupied',
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function token(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    private function customerHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token($this->customer)];
    }

    private function adminHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token($this->admin)];
    }

    private function waiterHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token($this->waiter)];
    }

    private function chefHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token($this->chef)];
    }

    private function validOrderPayload(int $quantity = 2): array
    {
        return [
            'table_id'   => $this->table->id,
            'order_type' => 'dine_in',
            'items'      => [
                [
                    'menu_id'  => $this->menu->id,
                    'quantity' => $quantity,
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Task 6.2 — Create order successfully
    // -------------------------------------------------------------------------
    #[Test]
    public function test_customer_can_create_order_successfully(): void
    {
        Event::fake([OrderCreated::class]);

        $response = $this->withHeaders($this->customerHeaders())
            ->postJson('/api/customer/orders', $this->validOrderPayload(2));

        $response->assertStatus(201)
            ->assertJsonFragment(['message' => 'Pesanan berhasil dibuat.'])
            ->assertJsonStructure(['data' => ['order_number']])
            ->assertJsonPath('data.order_status', 'Diterima')
            ->assertJsonPath('data.payment_status', 'pending');

        $orderNumber = $response->json('data.order_number');
        $this->assertIsString($orderNumber);
        $this->assertStringStartsWith('ORD-', $orderNumber);

        $this->assertDatabaseHas('order', [
            'order_number'   => $orderNumber,
            'order_status'   => 'Diterima',
            'payment_status' => 'pending',
            'order_type'     => 'dine_in',
        ]);

        // Verify order items were created with price_at_time snapshot
        $this->assertDatabaseHas('order_item', [
            'menu_id'       => $this->menu->id,
            'quantity'      => 2,
            'price_at_time' => '20000.00',
        ]);

        Event::assertDispatched(OrderCreated::class);
    }

    #[Test]
    public function test_guest_can_reload_order_with_matching_table_id_for_tracking(): void
    {
        Event::fake([OrderCreated::class]);

        $createResponse = $this->withHeaders($this->customerHeaders())
            ->postJson('/api/customer/orders', $this->validOrderPayload(2));

        $createResponse->assertStatus(201);
        $orderId = $createResponse->json('data.id');

        $response = $this->getJson("/api/customer/orders/{$orderId}?table_id={$this->table->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $orderId)
            ->assertJsonPath('data.order_number', $createResponse->json('data.order_number'));
    }

    #[Test]
    public function test_guest_cannot_reload_order_with_wrong_table_id(): void
    {
        Event::fake([OrderCreated::class]);

        $createResponse = $this->withHeaders($this->customerHeaders())
            ->postJson('/api/customer/orders', $this->validOrderPayload(2));

        $createResponse->assertStatus(201);
        $orderId = $createResponse->json('data.id');

        $response = $this->getJson("/api/customer/orders/{$orderId}?table_id=999999");

        $response->assertStatus(404);
    }

    #[Test]
    public function test_guest_cannot_create_order(): void
    {
        $response = $this->postJson('/api/customer/orders', $this->validOrderPayload(2));

        $response->assertStatus(401);
    }

    #[Test]
    public function test_order_creation_requires_items(): void
    {
        $response = $this->withHeaders($this->customerHeaders())
            ->postJson('/api/customer/orders', [
                'order_type' => 'dine_in',
                'items'      => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }
    #[Test]
    public function test_order_creation_requires_valid_menu_id(): void
    {
        $response = $this->withHeaders($this->customerHeaders())
            ->postJson('/api/customer/orders', [
                'order_type' => 'dine_in',
                'items'      => [
                    ['menu_id' => 99999, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.menu_id']);
    }

    #[Test]
    public function test_dine_in_checkout_merges_into_open_table_bill(): void
    {
        Event::fake([OrderCreated::class]);

        $first = $this->withHeaders($this->customerHeaders())
            ->postJson('/api/customer/orders', $this->validOrderPayload(1));

        $first->assertStatus(201);
        $orderId = $first->json('data.id');

        $second = $this->withHeaders($this->customerHeaders())
            ->postJson('/api/customer/orders', $this->validOrderPayload(3));

        $second->assertStatus(201)
            ->assertJsonPath('data.id', $orderId)
            ->assertJsonPath('data.total_price', '92000.00');

        $this->assertSame(1, Order::where('table_id', $this->table->id)->count());
        $this->assertSame(2, OrderItem::where('order_id', $orderId)->count());
        $this->assertDatabaseHas('order_item', [
            'order_id' => $orderId,
            'menu_id' => $this->menu->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('order_item', [
            'order_id' => $orderId,
            'menu_id' => $this->menu->id,
            'quantity' => 3,
        ]);
    }

    // -------------------------------------------------------------------------
    // Task 6.5 — Total calculation
    // -------------------------------------------------------------------------
    #[Test]
    public function test_order_total_is_calculated_correctly(): void
    {
        // menu price = 20000, qty = 2
        // subtotal = 40000
        // tax (10%) = 4000
        // service_charge (5%) = 2000
        // total = 40000 + 4000 + 2000 = 46000

        $response = $this->withHeaders($this->customerHeaders())
            ->postJson('/api/customer/orders', $this->validOrderPayload(2));

        $response->assertStatus(201);

        $data = $response->json('data');

        $this->assertEquals('4000.00', $data['tax_amount']);
        $this->assertEquals('2000.00', $data['service_charge']);
        $this->assertEquals('46000.00', $data['total_price']);
    }

    #[Test]
    public function test_order_total_uses_default_tax_and_service_when_settings_are_missing(): void
    {
        SystemSetting::whereIn('key', ['tax_percentage', 'service_charge_percentage'])->delete();

        $response = $this->withHeaders($this->customerHeaders())
            ->postJson('/api/customer/orders', $this->validOrderPayload(2));

        $response->assertStatus(201);

        $data = $response->json('data');

        $this->assertEquals('4400.00', $data['tax_amount']);
        $this->assertEquals('2000.00', $data['service_charge']);
        $this->assertEquals('46400.00', $data['total_price']);
    }

    #[Test]
    public function test_customer_supplied_discount_amount_is_ignored(): void
    {
        // subtotal = 20000 * 1 = 20000
        // tax (10%) = 2000
        // service_charge (5%) = 1000
        // customer-supplied discount_amount must be ignored
        // total = 20000 + 2000 + 1000 = 23000

        $payload = [
            'table_id'        => $this->table->id,
            'order_type'      => 'dine_in',
            'discount_amount' => 5000,
            'items'           => [
                ['menu_id' => $this->menu->id, 'quantity' => 1],
            ],
        ];

        $response = $this->withHeaders($this->customerHeaders())
            ->postJson('/api/customer/orders', $payload);

        $response->assertStatus(201);

        $data = $response->json('data');
        $this->assertEquals('23000.00', $data['total_price']);
        $this->assertEquals('0.00', $data['discount_amount']);
    }

    #[Test]
    public function test_voucher_applies_to_open_table_bill_and_recalculates_on_additional_order(): void
    {
        $promo = Promo::create([
            'name'        => 'Diskon 10%',
            'code'        => 'BILL10',
            'type'        => 'percentage',
            'value'       => 10,
            'min_purchase'=> 0,
            'start_date'  => now()->subDay()->toDateString(),
            'end_date'    => now()->addDay()->toDateString(),
            'is_active'   => true,
            'usage_limit' => 10,
            'usage_count' => 0,
        ]);

        $firstPayload = $this->validOrderPayload(1);
        $firstPayload['voucher_code'] = 'BILL10';

        $first = $this->withHeaders($this->customerHeaders())
            ->postJson('/api/customer/orders', $firstPayload);

        $first->assertStatus(201)
            ->assertJsonPath('data.discount_amount', '2000.00')
            ->assertJsonPath('data.tax_amount', '1800.00')
            ->assertJsonPath('data.service_charge', '900.00')
            ->assertJsonPath('data.total_price', '20700.00');

        $second = $this->withHeaders($this->customerHeaders())
            ->postJson('/api/customer/orders', $this->validOrderPayload(2));

        $second->assertStatus(201)
            ->assertJsonPath('data.id', $first->json('data.id'))
            ->assertJsonPath('data.voucher_code', 'BILL10')
            ->assertJsonPath('data.discount_amount', '6000.00')
            ->assertJsonPath('data.tax_amount', '5400.00')
            ->assertJsonPath('data.service_charge', '2700.00')
            ->assertJsonPath('data.total_price', '62100.00');

        $this->assertSame(1, $promo->fresh()->usage_count);
    }

    #[Test]
    public function test_order_total_uses_database_variant_extra_price(): void
    {
        $variant = Variant::create([
            'menu_id'      => $this->menu->id,
            'variant_name' => 'Porsi Besar',
            'extra_price'  => 5000,
        ]);

        $payload = $this->validOrderPayload(2);
        $payload['items'][0]['variant_id'] = $variant->id;

        $response = $this->withHeaders($this->customerHeaders())
            ->postJson('/api/customer/orders', $payload);

        $response->assertStatus(201);

        $data = $response->json('data');
        $this->assertEquals('5000.00', $data['tax_amount']);
        $this->assertEquals('2500.00', $data['service_charge']);
        $this->assertEquals('57500.00', $data['total_price']);
        $this->assertEquals('25000.00', $data['items'][0]['price_at_time']);
        $this->assertEquals('Porsi Besar', $data['items'][0]['variant_selected']);

        $this->assertDatabaseHas('order_item', [
            'menu_id'          => $this->menu->id,
            'quantity'         => 2,
            'variant_selected' => 'Porsi Besar',
            'price_at_time'    => '25000.00',
        ]);
    }

    #[Test]
    public function test_order_rejects_variant_that_does_not_belong_to_menu(): void
    {
        $otherMenu = Menu::create([
            'name'         => 'Es Teh',
            'category_id'  => $this->category->id,
            'price'        => 5000,
            'stock'        => 10,
            'is_available' => true,
        ]);

        $variant = Variant::create([
            'menu_id'      => $otherMenu->id,
            'variant_name' => 'Large',
            'extra_price'  => 2000,
        ]);

        $payload = $this->validOrderPayload(1);
        $payload['items'][0]['variant_id'] = $variant->id;

        $response = $this->withHeaders($this->customerHeaders())
            ->postJson('/api/customer/orders', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    // -------------------------------------------------------------------------
    // Task 6.6 — Stock validation / rejection
    // -------------------------------------------------------------------------
    #[Test]
    public function test_order_is_rejected_when_stock_is_insufficient(): void
    {
        // Menu has stock = 10, requesting 15
        $response = $this->withHeaders($this->customerHeaders())
            ->postJson('/api/customer/orders', $this->validOrderPayload(15));

        $response->assertStatus(422);

        $errors = $response->json('errors');
        $this->assertArrayHasKey('insufficient_items', $errors);

        $insufficientItems = $errors['insufficient_items'];
        $this->assertCount(1, $insufficientItems);
        $this->assertEquals($this->menu->id, $insufficientItems[0]['menu_id']);
        $this->assertEquals(15, $insufficientItems[0]['requested']);
        $this->assertEquals(10, $insufficientItems[0]['available']);
    }
    #[Test]
    public function test_stock_is_not_deducted_when_order_is_rejected(): void
    {
        $originalStock = $this->menu->stock;

        $this->withHeaders($this->customerHeaders())
            ->postJson('/api/customer/orders', $this->validOrderPayload(15));

        // Stock should remain unchanged
        $this->menu->refresh();
        $this->assertEquals($originalStock, $this->menu->stock);
    }
    #[Test]
    public function test_order_with_zero_stock_menu_is_rejected(): void
    {
        $this->menu->update(['stock' => 0]);

        $response = $this->withHeaders($this->customerHeaders())
            ->postJson('/api/customer/orders', $this->validOrderPayload(1));

        $response->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Task 6.3 — State machine: valid transitions
    // -------------------------------------------------------------------------
    #[Test]
    public function test_valid_state_machine_transition_diterima_to_diproses(): void
    {
        Event::fake([OrderStatusUpdated::class]);

        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Diterima',
            'payment_status' => 'pending',
            'order_type'     => 'dine_in',
        ]);

        $response = $this->withHeaders($this->waiterHeaders())
            ->patchJson("/api/staff/orders/{$order->id}/status", ['status' => 'Diproses']);

        $response->assertStatus(200)
            ->assertJsonPath('data.order_status', 'Diproses');

        $this->assertDatabaseHas('order', ['id' => $order->id, 'order_status' => 'Diproses']);

        Event::assertDispatched(OrderStatusUpdated::class);
    }
    #[Test]
    public function test_valid_state_machine_transition_diproses_to_dimasak(): void
    {
        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Diproses',
            'payment_status' => 'paid',
            'order_type'     => 'dine_in',
        ]);

        $response = $this->withHeaders($this->waiterHeaders())
            ->patchJson("/api/staff/orders/{$order->id}/status", ['status' => 'Dimasak']);

        $response->assertStatus(200)
            ->assertJsonPath('data.order_status', 'Dimasak');
    }
    #[Test]
    public function test_valid_state_machine_transition_dimasak_to_selesai(): void
    {
        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Dimasak',
            'payment_status' => 'paid',
            'order_type'     => 'dine_in',
        ]);

        $response = $this->withHeaders($this->chefHeaders())
            ->patchJson("/api/staff/orders/{$order->id}/status", ['status' => 'Selesai']);

        $response->assertStatus(200)
            ->assertJsonPath('data.order_status', 'Selesai');
    }
    #[Test]
    public function test_valid_state_machine_transition_selesai_to_disajikan(): void
    {
        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Selesai',
            'payment_status' => 'paid',
            'order_type'     => 'dine_in',
        ]);

        $response = $this->withHeaders($this->waiterHeaders())
            ->patchJson("/api/staff/orders/{$order->id}/status", ['status' => 'Disajikan']);

        $response->assertStatus(200)
            ->assertJsonPath('data.order_status', 'Disajikan');
    }

    // -------------------------------------------------------------------------
    // Task 6.3 — State machine: invalid transitions rejected
    // -------------------------------------------------------------------------
    #[Test]
    public function test_invalid_state_machine_transition_is_rejected(): void
    {
        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Diterima',
            'payment_status' => 'pending',
            'order_type'     => 'dine_in',
        ]);

        // Cannot jump from Diterima directly to Dimasak
        $response = $this->withHeaders($this->waiterHeaders())
            ->patchJson("/api/staff/orders/{$order->id}/status", ['status' => 'Dimasak']);

        $response->assertStatus(422);

        // Status should remain unchanged
        $this->assertDatabaseHas('order', ['id' => $order->id, 'order_status' => 'Diterima']);
    }
    #[Test]
    public function test_cannot_transition_from_dibatalkan(): void
    {
        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Dibatalkan',
            'payment_status' => 'pending',
            'order_type'     => 'dine_in',
        ]);

        $response = $this->withHeaders($this->adminHeaders())
            ->patchJson("/api/staff/orders/{$order->id}/status", ['status' => 'Diterima']);

        $response->assertStatus(422);
    }
    #[Test]
    public function test_cannot_skip_from_diterima_to_disajikan(): void
    {
        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Diterima',
            'payment_status' => 'pending',
            'order_type'     => 'dine_in',
        ]);

        $response = $this->withHeaders($this->adminHeaders())
            ->patchJson("/api/staff/orders/{$order->id}/status", ['status' => 'Disajikan']);

        $response->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Task 6.4 — Admin cancellation
    // -------------------------------------------------------------------------
    #[Test]
    public function test_admin_can_cancel_order(): void
    {
        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Diterima',
            'payment_status' => 'pending',
            'order_type'     => 'dine_in',
        ]);

        $response = $this->withHeaders($this->adminHeaders())
            ->postJson("/api/admin/orders/{$order->id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('data.order_status', 'Dibatalkan');

        $this->assertDatabaseHas('order', ['id' => $order->id, 'order_status' => 'Dibatalkan']);
    }
    #[Test]
    public function test_non_admin_cannot_cancel_order_via_cancel_endpoint(): void
    {
        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Diterima',
            'payment_status' => 'pending',
            'order_type'     => 'dine_in',
        ]);

        // Waiter tries to access admin cancel endpoint — should be blocked by middleware
        $response = $this->withHeaders($this->waiterHeaders())
            ->postJson("/api/admin/orders/{$order->id}/cancel");

        $response->assertStatus(403);

        // Order should remain unchanged
        $this->assertDatabaseHas('order', ['id' => $order->id, 'order_status' => 'Diterima']);
    }
    #[Test]
    public function test_non_admin_cannot_cancel_via_status_update(): void
    {
        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Diterima',
            'payment_status' => 'pending',
            'order_type'     => 'dine_in',
        ]);

        // Waiter tries to set status to Dibatalkan via the status update endpoint
        $response = $this->withHeaders($this->waiterHeaders())
            ->patchJson("/api/staff/orders/{$order->id}/status", ['status' => 'Dibatalkan']);

        $response->assertStatus(422);

        $this->assertDatabaseHas('order', ['id' => $order->id, 'order_status' => 'Diterima']);
    }

    // -------------------------------------------------------------------------
    // Task 6.7 — Table auto-release
    // -------------------------------------------------------------------------
    #[Test]
    public function test_table_becomes_available_when_all_orders_are_disajikan_and_paid(): void
    {
        // Table starts as occupied
        $this->assertEquals('occupied', $this->table->status);

        // Create a single order on the table
        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Selesai',
            'payment_status' => 'paid',
            'order_type'     => 'dine_in',
        ]);

        // Update to Disajikan — should trigger table release
        $response = $this->withHeaders($this->waiterHeaders())
            ->patchJson("/api/staff/orders/{$order->id}/status", ['status' => 'Disajikan']);

        $response->assertStatus(200);

        // Table should now be available
        $this->table->refresh();
        $this->assertEquals('available', $this->table->status);
    }
    #[Test]
    public function test_table_stays_occupied_when_some_orders_are_not_complete(): void
    {
        // Create two orders on the same table
        $order1 = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Selesai',
            'payment_status' => 'paid',
            'order_type'     => 'dine_in',
        ]);

        // Second order is still being processed
        Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 15000,
            'order_status'   => 'Dimasak',
            'payment_status' => 'pending',
            'order_type'     => 'dine_in',
        ]);

        // Update first order to Disajikan
        $this->withHeaders($this->waiterHeaders())
            ->patchJson("/api/staff/orders/{$order1->id}/status", ['status' => 'Disajikan']);

        // Table should still be occupied (second order not done)
        $this->table->refresh();
        $this->assertEquals('occupied', $this->table->status);
    }

    // -------------------------------------------------------------------------
    // Task 6.9 — KDS endpoint
    // -------------------------------------------------------------------------
    #[Test]
    public function test_kds_returns_only_dimasak_orders(): void
    {
        // Create orders with various statuses
        Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Diterima',
            'payment_status' => 'pending',
            'order_type'     => 'dine_in',
        ]);

        $cookingOrder = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 25000,
            'order_status'   => 'Dimasak',
            'payment_status' => 'paid',
            'order_type'     => 'dine_in',
        ]);

        Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 30000,
            'order_status'   => 'Selesai',
            'payment_status' => 'paid',
            'order_type'     => 'dine_in',
        ]);

        $response = $this->withHeaders($this->chefHeaders())
            ->getJson('/api/staff/kds');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($cookingOrder->id, $data[0]['id']);
        $this->assertEquals('Dimasak', $data[0]['order_status']);
    }
    #[Test]
    public function test_kds_includes_order_items_with_menu_name(): void
    {
        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Dimasak',
            'payment_status' => 'paid',
            'order_type'     => 'dine_in',
        ]);

        OrderItem::create([
            'order_id'      => $order->id,
            'menu_id'       => $this->menu->id,
            'quantity'      => 2,
            'price_at_time' => 20000,
            'note'          => 'Pedas',
        ]);

        $response = $this->withHeaders($this->chefHeaders())
            ->getJson('/api/staff/kds');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertCount(1, $data[0]['items']);
        $this->assertEquals('Nasi Goreng', $data[0]['items'][0]['menu_name']);
        $this->assertEquals(2, $data[0]['items'][0]['quantity']);
        $this->assertEquals('Pedas', $data[0]['items'][0]['note']);
    }
    #[Test]
    public function test_kds_includes_table_number_and_time_elapsed(): void
    {
        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Dimasak',
            'payment_status' => 'paid',
            'order_type'     => 'dine_in',
        ]);

        $response = $this->withHeaders($this->chefHeaders())
            ->getJson('/api/staff/kds');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals('T01', $data[0]['table']['table_number']);
        $this->assertArrayHasKey('minutes_elapsed', $data[0]);
    }
    #[Test]
    public function test_kds_returns_empty_when_no_dimasak_orders(): void
    {
        $response = $this->withHeaders($this->chefHeaders())
            ->getJson('/api/staff/kds');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }
    #[Test]
    public function test_customer_cannot_access_kds(): void
    {
        $response = $this->withHeaders($this->customerHeaders())
            ->getJson('/api/staff/kds');

        $response->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // Task 6.1 — index and show
    // -------------------------------------------------------------------------
    #[Test]
    public function test_staff_can_list_orders_with_filters(): void
    {
        Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Diterima',
            'payment_status' => 'pending',
            'order_type'     => 'dine_in',
        ]);

        Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => null,
            'total_price'    => 30000,
            'order_status'   => 'Dimasak',
            'payment_method' => 'qris',
            'payment_status' => 'paid',
            'order_type'     => 'delivery',
        ]);

        // Filter by order_type
        $response = $this->withHeaders($this->waiterHeaders())
            ->getJson('/api/staff/orders?order_type=dine_in');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('dine_in', $data[0]['order_type']);

        $response = $this->withHeaders($this->waiterHeaders())
            ->getJson('/api/staff/orders?payment_status=paid&payment_method=qris');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('paid', $data[0]['payment_status']);
        $this->assertEquals('qris', $data[0]['payment_method']);
    }
    #[Test]
    public function test_staff_can_view_single_order(): void
    {
        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Diterima',
            'payment_status' => 'pending',
            'order_type'     => 'dine_in',
        ]);

        OrderItem::create([
            'order_id'      => $order->id,
            'menu_id'       => $this->menu->id,
            'quantity'      => 1,
            'price_at_time' => 20000,
        ]);

        $response = $this->withHeaders($this->waiterHeaders())
            ->getJson("/api/staff/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonCount(1, 'data.items');
    }
    #[Test]
    public function test_customer_cannot_access_staff_orders(): void
    {
        $response = $this->withHeaders($this->customerHeaders())
            ->getJson('/api/staff/orders');

        $response->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // Task 6.8 — Broadcast events
    // -------------------------------------------------------------------------
    #[Test]
    public function test_order_created_event_is_broadcast(): void
    {
        Event::fake([OrderCreated::class]);

        $this->withHeaders($this->customerHeaders())
            ->postJson('/api/customer/orders', $this->validOrderPayload(1));

        Event::assertDispatched(OrderCreated::class);
    }
    #[Test]
    public function test_order_status_updated_event_is_broadcast(): void
    {
        Event::fake([OrderStatusUpdated::class]);

        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Diterima',
            'payment_status' => 'pending',
            'order_type'     => 'dine_in',
        ]);

        $this->withHeaders($this->waiterHeaders())
            ->patchJson("/api/staff/orders/{$order->id}/status", ['status' => 'Diproses']);

        Event::assertDispatched(OrderStatusUpdated::class, function (OrderStatusUpdated $event) use ($order) {
            return $event->order->id === $order->id
                && $event->previousStatus === 'Diterima'
                && $event->newStatus === 'Diproses';
        });
    }

    // -------------------------------------------------------------------------
    // Task 14.4 — show_rating_prompt (Requirement 16.1)
    // -------------------------------------------------------------------------

    /**
     * When status transitions to Disajikan and the order has no rating yet,
     * the API response must include show_rating_prompt = true.
     *
     */
    #[Test]
    public function test_show_rating_prompt_is_true_when_status_becomes_disajikan_without_rating(): void
    {
        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Selesai',
            'payment_status' => 'paid',
            'order_type'     => 'dine_in',
        ]);

        $response = $this->withHeaders($this->waiterHeaders())
            ->patchJson("/api/staff/orders/{$order->id}/status", ['status' => 'Disajikan']);

        $response->assertStatus(200)
            ->assertJsonPath('data.order_status', 'Disajikan')
            ->assertJsonPath('data.show_rating_prompt', true);
    }

    /**
     * When status transitions to Disajikan but the order already has a rating,
     * show_rating_prompt must be false (no duplicate prompt).
     *
     */
    #[Test]
    public function test_show_rating_prompt_is_false_when_order_already_has_rating(): void
    {
        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Selesai',
            'payment_status' => 'paid',
            'order_type'     => 'dine_in',
        ]);

        // Pre-existing rating
        \App\Models\Rating::create([
            'order_id' => $order->id,
            'user_id'  => $this->customer->id,
            'rating'   => 4,
        ]);

        $response = $this->withHeaders($this->waiterHeaders())
            ->patchJson("/api/staff/orders/{$order->id}/status", ['status' => 'Disajikan']);

        $response->assertStatus(200)
            ->assertJsonPath('data.order_status', 'Disajikan')
            ->assertJsonPath('data.show_rating_prompt', false);
    }

    /**
     * For statuses other than Disajikan, show_rating_prompt must always be false.
     *
     */
    #[Test]
    public function test_show_rating_prompt_is_false_for_non_disajikan_statuses(): void
    {
        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Diterima',
            'payment_status' => 'pending',
            'order_type'     => 'dine_in',
        ]);

        $response = $this->withHeaders($this->waiterHeaders())
            ->patchJson("/api/staff/orders/{$order->id}/status", ['status' => 'Diproses']);

        $response->assertStatus(200)
            ->assertJsonPath('data.order_status', 'Diproses')
            ->assertJsonPath('data.show_rating_prompt', false);
    }

    /**
     * The OrderStatusUpdated broadcast payload must include show_rating_prompt = true
     * when transitioning to Disajikan without an existing rating.
     *
     */
    #[Test]
    public function test_broadcast_event_includes_show_rating_prompt_true_for_disajikan(): void
    {
        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Selesai',
            'payment_status' => 'paid',
            'order_type'     => 'dine_in',
        ]);

        $event = new OrderStatusUpdated($order, 'Selesai', 'Disajikan');
        $payload = $event->broadcastWith();

        $this->assertTrue($payload['order']['show_rating_prompt']);
    }

    /**
     * The OrderStatusUpdated broadcast payload must include show_rating_prompt = false
     * when the order already has a rating.
     *
     */
    #[Test]
    public function test_broadcast_event_includes_show_rating_prompt_false_when_already_rated(): void
    {
        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Disajikan',
            'payment_status' => 'paid',
            'order_type'     => 'dine_in',
        ]);

        \App\Models\Rating::create([
            'order_id' => $order->id,
            'user_id'  => $this->customer->id,
            'rating'   => 5,
        ]);

        $event = new OrderStatusUpdated($order, 'Selesai', 'Disajikan');
        $payload = $event->broadcastWith();

        $this->assertFalse($payload['order']['show_rating_prompt']);
    }

    /**
     * The OrderStatusUpdated broadcast payload must include show_rating_prompt = false
     * for statuses other than Disajikan.
     *
     */
    #[Test]
    public function test_broadcast_event_includes_show_rating_prompt_false_for_non_disajikan(): void
    {
        $order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $this->table->id,
            'total_price'    => 20000,
            'order_status'   => 'Diproses',
            'payment_status' => 'pending',
            'order_type'     => 'dine_in',
        ]);

        $event = new OrderStatusUpdated($order, 'Diterima', 'Diproses');
        $payload = $event->broadcastWith();

        $this->assertFalse($payload['order']['show_rating_prompt']);
    }
}
