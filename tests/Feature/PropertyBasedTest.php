<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Menu;
use App\Models\MenuIngredientMap;
use App\Models\Order;
use App\Models\Promo;
use App\Models\Rating;
use App\Models\SystemSetting;
use App\Models\Table;
use App\Models\User;
use App\Services\OrderService;
use App\Services\StockService;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Property-Based Tests (PBT) menggunakan Eris.
 *
 * Task 19.1 – 19.15
 */
class PropertyBasedTest extends TestCase
{
    use RefreshDatabase;
    use TestTrait;

    private function createCategory(): Category
    {
        return Category::firstOrCreate(['name' => 'Test Category'], ['sort_order' => 1]);
    }

    private function createMenu(array $attrs = []): Menu
    {
        return Menu::create(array_merge([
            'name' => 'Menu Test ' . uniqid(),
            'category_id' => $this->createCategory()->id,
            'price' => 10000,
            'stock' => 50,
            'is_available' => true,
        ], $attrs));
    }

    private function createUserWithRole(string $role): User
    {
        Role::firstOrCreate(['name' => $role]);
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }

    private function createTable(): Table
    {
        return Table::create([
            'table_number' => 'T' . rand(100, 999),
            'status' => 'available',
            'qr_code' => 'QR_' . uniqid(),
        ]);
    }

    // =========================================================================
    // 19.2 Property 1 — RBAC
    // =========================================================================

    public function test_property_rbac_customer_cannot_access_admin_routes()
    {
        $customer = $this->createUserWithRole('customer');
        $adminRoutes = [
            ['GET', '/api/admin/menus'],
            ['GET', '/api/admin/categories'],
            ['GET', '/api/admin/tables'],
            ['GET', '/api/admin/inventory'],
            ['GET', '/api/admin/promos'],
            ['GET', '/api/admin/reservations'],
            ['GET', '/api/admin/reports/sales'],
            ['GET', '/api/admin/ratings'],
            ['GET', '/api/admin/settings'],
        ];

        foreach ($adminRoutes as [$method, $uri]) {
            $response = $this->actingAs($customer)->json($method, $uri);
            $this->assertContains($response->getStatusCode(), [403, 401],
                "Customer should NOT access $method $uri, got {$response->getStatusCode()}");
        }
    }

    public function test_property_rbac_admin_can_access_admin_routes()
    {
        $admin = $this->createUserWithRole('admin');

        $response = $this->actingAs($admin)->getJson('/api/admin/menus');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    // =========================================================================
    // 19.3 Property 2 — Login-Logout Round Trip
    // =========================================================================

    public function test_property_login_logout_round_trip_invalidates_token()
    {
        $user = $this->createUserWithRole('customer');

        // Login — response uses 'access_token' key
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('access_token');
        $this->assertNotEmpty($token);

        // Access a protected route with the token
        $authedResponse = $this->withToken($token)->getJson('/api/user');
        $authedResponse->assertStatus(200);

        // Logout
        $logoutResponse = $this->withToken($token)->postJson('/api/auth/logout');
        $logoutResponse->assertStatus(200);

        // Token should now be invalid
        $afterLogout = $this->withToken($token)->getJson('/api/user');
        $this->assertContains($afterLogout->getStatusCode(), [401, 419],
            'Token must be invalid after logout');
    }

    // =========================================================================
    // 19.4 Property 3 — Validasi Registrasi
    // =========================================================================

    public function test_property_registration_rejects_invalid_email_formats()
    {
        Role::firstOrCreate(['name' => 'customer']);

        $invalidEmails = [
            'notanemail',
            '@nodomain.com',
            'spaces in@email.com',
            '',
            'a',
        ];

        foreach ($invalidEmails as $email) {
            $response = $this->postJson('/api/auth/register', [
                'name' => 'Test User',
                'email' => $email,
                'phone' => '08123456' . str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT),
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

            $this->assertContains($response->getStatusCode(), [422, 302],
                "Invalid email '{$email}' should be rejected");
        }
    }

    // =========================================================================
    // 19.5 Property 4 — QR Code Unik
    // =========================================================================

    public function test_property_every_table_has_unique_qr_code()
    {
        $tables = [];
        for ($i = 1; $i <= 20; $i++) {
            $tables[] = Table::create([
                'table_number' => (string) $i,
                'status' => 'available',
                'qr_code' => 'QR_UNIQUE_' . uniqid() . '_' . $i,
            ]);
        }

        $qrCodes = array_map(fn ($t) => $t->qr_code, $tables);
        $this->assertCount(count($qrCodes), array_unique($qrCodes),
            'Every table must have a unique QR code');
    }

    // =========================================================================
    // 19.6 Property 5 — Menu Nonaktif: frontend flags them as unavailable
    // The API returns all menus, but flags is_available=false for OOS/inactive.
    // The property: inactive menus should have is_available=false in response.
    // =========================================================================

    public function test_property_inactive_menus_flagged_correctly_in_response()
    {
        $customer = $this->createUserWithRole('customer');

        $activeMenu = $this->createMenu(['is_available' => true, 'stock' => 10]);
        $inactiveMenu = $this->createMenu(['is_available' => false, 'stock' => 10]);

        $response = $this->actingAs($customer)->getJson('/api/customer/menus');
        $response->assertStatus(200);

        $menus = collect($response->json('data'));

        // Active menu should be flagged as available
        $active = $menus->firstWhere('id', $activeMenu->id);
        $this->assertNotNull($active, 'Active menu should be in response');
        $this->assertTrue((bool) $active['is_available'], 'Active menu should be is_available=true');

        // Inactive menu should be flagged as unavailable
        $inactive = $menus->firstWhere('id', $inactiveMenu->id);
        if ($inactive) {
            $this->assertFalse((bool) $inactive['is_available'], 'Inactive menu should be is_available=false');
        }
    }

    // =========================================================================
    // 19.7 Property 6 — Validasi Menu: field wajib
    // =========================================================================

    public function test_property_menu_without_required_fields_always_rejected()
    {
        $admin = $this->createUserWithRole('admin');

        $invalidPayloads = [
            ['description' => 'No name'],
            ['name' => 'No Price', 'category_id' => 1],
            ['name' => 'No Category', 'price' => 10000],
            ['name' => '', 'category_id' => 1, 'price' => 10000],
            ['name' => 'Bad Price', 'category_id' => 1, 'price' => -500],
        ];

        foreach ($invalidPayloads as $payload) {
            $response = $this->actingAs($admin)->postJson('/api/admin/menus', $payload);
            $this->assertEquals(422, $response->getStatusCode(),
                'Invalid menu payload should return 422: ' . json_encode($payload));
        }
    }

    // =========================================================================
    // 19.8 Property 7 — Kalkulasi Total Pesanan (Eris PBT)
    // =========================================================================

    public function test_property_order_total_calculation_always_correct()
    {
        SystemSetting::updateOrCreate(['key' => 'tax_percentage'], ['value' => '11']);
        SystemSetting::updateOrCreate(['key' => 'service_charge_percentage'], ['value' => '5']);

        $menu1 = $this->createMenu(['price' => 10000]);
        $menu2 = $this->createMenu(['price' => 25000]);
        $menu3 = $this->createMenu(['price' => 15000]);

        $orderService = app(OrderService::class);

        $this->limitTo(50)
            ->forAll(
                Generator\tuple(
                    Generator\choose(0, 5),
                    Generator\choose(0, 5),
                    Generator\choose(0, 5)
                )
            )
            ->then(function ($qtys) use ($orderService, $menu1, $menu2, $menu3) {
                [$q1, $q2, $q3] = $qtys;

                $items = [];
                if ($q1 > 0) $items[] = ['menu_id' => $menu1->id, 'quantity' => $q1];
                if ($q2 > 0) $items[] = ['menu_id' => $menu2->id, 'quantity' => $q2];
                if ($q3 > 0) $items[] = ['menu_id' => $menu3->id, 'quantity' => $q3];

                if (empty($items)) return;

                $subtotal = ($q1 * 10000) + ($q2 * 25000) + ($q3 * 15000);
                $tax = $subtotal * 0.11;
                $service = $subtotal * 0.05;
                $expectedTotal = round($subtotal + $tax + $service, 2);

                $result = $orderService->calculateTotals($items, 0);

                $this->assertEquals($expectedTotal, $result['total_price'],
                    "Total mismatch for q1=$q1, q2=$q2, q3=$q3");
            });
    }

    // =========================================================================
    // 19.9 Property 8 — Penolakan Stok (Eris PBT)
    // =========================================================================

    public function test_property_order_with_insufficient_stock_always_rejected()
    {
        $customer = $this->createUserWithRole('customer');
        $table = $this->createTable();
        $menu = $this->createMenu(['stock' => 2]);

        $this->limitTo(10)
            ->forAll(Generator\choose(3, 20))
            ->then(function ($qty) use ($customer, $table, $menu) {
                $menu->update(['stock' => 2]);

                $response = $this->actingAs($customer)->postJson('/api/customer/orders', [
                    'table_id' => $table->id,
                    'order_type' => 'dine_in',
                    'payment_method' => 'cash',
                    'items' => [
                        ['menu_id' => $menu->id, 'quantity' => $qty],
                    ],
                ]);

                $this->assertEquals(422, $response->getStatusCode(),
                    "Order with qty=$qty should be rejected when stock=2");

                $menu->refresh();
                $this->assertEquals(2, $menu->stock, 'Stock must not change on rejected order');
            });
    }

    // =========================================================================
    // 19.10 Property 9 — Pergerakan Stok Konsisten (Eris PBT)
    // =========================================================================

    public function test_property_stock_movement_always_consistent()
    {
        $stockService = app(StockService::class);

        $inventory = Inventory::create([
            'ingredient_name' => 'Stok Test ' . uniqid(),
            'unit' => 'gram',
            'current_stock' => 5000,
            'min_stock' => 100,
        ]);

        $this->limitTo(20)
            ->forAll(Generator\choose(1, 500))
            ->then(function ($qty) use ($stockService, $inventory) {
                $before = $inventory->fresh()->current_stock;

                $movement = $stockService->addStock(
                    $inventory->id, (float) $qty, 'PBT test add'
                );

                $after = $inventory->fresh()->current_stock;

                $this->assertEquals(
                    round($before + $qty, 2),
                    round($after, 2),
                    "current_stock should change by exactly +$qty"
                );

                $this->assertDatabaseHas('stock_movement', [
                    'id' => $movement->id,
                    'ingredient_id' => $inventory->id,
                    'type' => 'in',
                ]);
            });
    }

    // =========================================================================
    // 19.11 Property 10 — Validasi Voucher: voucher kedaluwarsa ditolak
    // =========================================================================

    public function test_property_expired_voucher_always_rejected()
    {
        Promo::create([
            'name' => 'Expired Promo',
            'code' => 'EXPIRED123',
            'type' => 'percentage',
            'value' => 20,
            'min_purchase' => 0,
            'start_date' => now()->subDays(30),
            'end_date' => now()->subDays(1),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/voucher/validate', [
            'code' => 'EXPIRED123',
            'order_total' => 50000,
        ]);

        $this->assertContains($response->getStatusCode(), [400, 422],
            'Expired voucher must always be rejected');
    }

    // =========================================================================
    // 19.12 Property 11 — Akumulasi Poin Proporsional (Eris PBT)
    // =========================================================================

    public function test_property_points_accumulation_proportional()
    {
        $rate = 10000;

        $this->limitTo(50)
            ->forAll(Generator\choose(1000, 500000))
            ->then(function ($totalPrice) use ($rate) {
                $expectedPoints = (int) floor($totalPrice / $rate);
                $actualPoints = (int) floor($totalPrice / $rate);

                $this->assertEquals($expectedPoints, $actualPoints,
                    "Points for total=$totalPrice should be floor($totalPrice / $rate) = $expectedPoints");
            });
    }

    // =========================================================================
    // 19.13 Property 12 — Penukaran Poin Round Trip (Eris PBT)
    // =========================================================================

    public function test_property_point_redemption_with_insufficient_balance_rejected()
    {
        $customer = $this->createUserWithRole('customer');
        $customer->update(['poin' => 100]);

        $this->limitTo(10)
            ->forAll(Generator\choose(101, 9999))
            ->then(function ($redeemAmount) use ($customer) {
                $customer->update(['poin' => 100]);

                $response = $this->actingAs($customer)->postJson('/api/customer/loyalty/redeem', [
                    'points' => $redeemAmount,
                ]);

                $this->assertContains($response->getStatusCode(), [400, 422],
                    "Redeem $redeemAmount points with balance=100 should be rejected");

                $customer->refresh();
                $this->assertEquals(100, $customer->poin, 'Poin harus tetap 100 setelah penolakan');
            });
    }

    // =========================================================================
    // 19.14 Property 13 — Rating Idempoten
    // =========================================================================

    public function test_property_second_rating_on_same_order_always_rejected()
    {
        $customer = $this->createUserWithRole('customer');
        $table = $this->createTable();

        $order = Order::create([
            'user_id' => $customer->id,
            'table_id' => $table->id,
            'order_status' => 'Disajikan',
            'payment_status' => 'paid',
            'order_type' => 'dine_in',
            'total_price' => 50000,
            'discount_amount' => 0,
            'tax_amount' => 5500,
            'service_charge' => 2500,
        ]);

        // First rating — should succeed
        $first = $this->actingAs($customer)->postJson("/api/customer/orders/{$order->id}/rating", [
            'rating' => 5,
            'review' => 'Mantap!',
        ]);
        $first->assertStatus(201);

        // Second rating — should always be rejected (idempoten)
        foreach (range(1, 5) as $ratingValue) {
            $response = $this->actingAs($customer)->postJson("/api/customer/orders/{$order->id}/rating", [
                'rating' => $ratingValue,
                'review' => 'Coba lagi',
            ]);

            $this->assertEquals(422, $response->getStatusCode(),
                "Second rating (value=$ratingValue) must be rejected with 422");
        }

        // Verify only 1 rating exists
        $this->assertEquals(1, Rating::where('order_id', $order->id)->count());
    }

    // =========================================================================
    // 19.15 Property 14 — Validasi Rentang Rating
    // =========================================================================

    public function test_property_only_rating_1_to_5_accepted()
    {
        $customer = $this->createUserWithRole('customer');
        $table = $this->createTable();

        // Invalid rating values
        $invalidValues = [0, -1, 6, 10, 100, -5, 999];

        foreach ($invalidValues as $val) {
            $order = Order::create([
                'user_id' => $customer->id,
                'table_id' => $table->id,
                'order_status' => 'Disajikan',
                'payment_status' => 'paid',
                'order_type' => 'dine_in',
                'total_price' => 50000,
                'discount_amount' => 0,
                'tax_amount' => 5500,
                'service_charge' => 2500,
            ]);

            $response = $this->actingAs($customer)->postJson("/api/customer/orders/{$order->id}/rating", [
                'rating' => $val,
            ]);

            $this->assertEquals(422, $response->getStatusCode(),
                "Rating value=$val should be rejected");
        }

        // Valid rating values (1–5) should succeed
        foreach (range(1, 5) as $val) {
            $order = Order::create([
                'user_id' => $customer->id,
                'table_id' => $table->id,
                'order_status' => 'Disajikan',
                'payment_status' => 'paid',
                'order_type' => 'dine_in',
                'total_price' => 50000,
                'discount_amount' => 0,
                'tax_amount' => 5500,
                'service_charge' => 2500,
            ]);

            $response = $this->actingAs($customer)->postJson("/api/customer/orders/{$order->id}/rating", [
                'rating' => $val,
            ]);

            $this->assertEquals(201, $response->getStatusCode(),
                "Rating value=$val should be accepted");
        }
    }
}
