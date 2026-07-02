<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use App\Models\Order;
use App\Models\Promo;
use App\Models\User;
use App\Models\VoucherUsage;
use App\Services\PromoService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the Promo_Engine module.
 *
 * Covers:
 *   - Admin CRUD for promos (percentage and nominal types)
 *   - Voucher validation: valid, expired, below min_purchase, non-existent, inactive
 *   - Discount calculation: percentage with max_discount cap, nominal
 *   - Voucher application: usage recorded, usage_count incremented
 *   - Usage limit enforcement
 *   - Active promos endpoint
 *
 * Validates: Requirements 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 12.7
 */
class PromoEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;
    private PromoService $promoService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->admin->assignRole('admin');

        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->customer->assignRole('customer');

        $this->promoService = app(PromoService::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function adminHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->admin->createToken('test')->plainTextToken];
    }

    private function makePromo(array $overrides = []): Promo
    {
        return Promo::create(array_merge([
            'name'         => 'Test Promo',
            'code'         => 'TESTCODE',
            'type'         => 'percentage',
            'value'        => 10,
            'min_purchase' => 0,
            'max_discount' => null,
            'start_date'   => now()->subDay()->toDateString(),
            'end_date'     => now()->addDay()->toDateString(),
            'is_active'    => true,
            'usage_limit'  => null,
            'usage_count'  => 0,
        ], $overrides));
    }

    private function makeOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'user_id'        => $this->customer->id,
            'total_price'    => 100000,
            'discount_amount'=> 0,
            'tax_amount'     => 0,
            'service_charge' => 0,
            'payment_status' => 'pending',
            'order_status'   => 'Diterima',
            'order_type'     => 'dine_in',
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // Task 10.1 — Admin CRUD
    // -------------------------------------------------------------------------
    #[Test]
    public function test_admin_can_create_promo_percentage_type(): void
    {
        $response = $this->withHeaders($this->adminHeaders())
            ->postJson('/api/admin/promos', [
                'name'         => 'Diskon 20%',
                'code'         => 'DISC20',
                'type'         => 'percentage',
                'value'        => 20,
                'min_purchase' => 50000,
                'max_discount' => 30000,
                'start_date'   => now()->toDateString(),
                'end_date'     => now()->addMonth()->toDateString(),
                'is_active'    => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Diskon 20%'])
            ->assertJsonFragment(['type' => 'percentage'])
            ->assertJsonFragment(['code' => 'DISC20']);

        $this->assertDatabaseHas('promo', [
            'code' => 'DISC20',
            'type' => 'percentage',
        ]);
    }
    #[Test]
    public function test_admin_can_create_promo_nominal_type(): void
    {
        $response = $this->withHeaders($this->adminHeaders())
            ->postJson('/api/admin/promos', [
                'name'         => 'Diskon Rp 15.000',
                'code'         => 'FLAT15K',
                'type'         => 'nominal',
                'value'        => 15000,
                'min_purchase' => 100000,
                'start_date'   => now()->toDateString(),
                'end_date'     => now()->addMonth()->toDateString(),
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['type' => 'nominal'])
            ->assertJsonFragment(['code' => 'FLAT15K']);

        $this->assertDatabaseHas('promo', [
            'code'  => 'FLAT15K',
            'type'  => 'nominal',
            'value' => '15000.00',
        ]);
    }
    #[Test]
    public function test_admin_can_list_promos(): void
    {
        $this->makePromo(['code' => 'PROMO1']);
        $this->makePromo(['code' => 'PROMO2']);

        $response = $this->withHeaders($this->adminHeaders())
            ->getJson('/api/admin/promos');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }
    #[Test]
    public function test_admin_can_show_single_promo(): void
    {
        $promo = $this->makePromo();

        $response = $this->withHeaders($this->adminHeaders())
            ->getJson("/api/admin/promos/{$promo->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $promo->id);
    }
    #[Test]
    public function test_admin_can_update_promo(): void
    {
        $promo = $this->makePromo();

        $response = $this->withHeaders($this->adminHeaders())
            ->putJson("/api/admin/promos/{$promo->id}", [
                'name'      => 'Updated Promo',
                'is_active' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Promo')
            ->assertJsonPath('data.is_active', false);
    }
    #[Test]
    public function test_admin_can_delete_promo(): void
    {
        $promo = $this->makePromo();

        $response = $this->withHeaders($this->adminHeaders())
            ->deleteJson("/api/admin/promos/{$promo->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('promo', ['id' => $promo->id]);
    }
    #[Test]
    public function test_create_promo_requires_valid_type(): void
    {
        $response = $this->withHeaders($this->adminHeaders())
            ->postJson('/api/admin/promos', [
                'name'       => 'Bad Promo',
                'type'       => 'invalid_type',
                'value'      => 10,
                'start_date' => now()->toDateString(),
                'end_date'   => now()->addDay()->toDateString(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }
    #[Test]
    public function test_create_promo_requires_positive_value(): void
    {
        $response = $this->withHeaders($this->adminHeaders())
            ->postJson('/api/admin/promos', [
                'name'       => 'Zero Value Promo',
                'type'       => 'percentage',
                'value'      => 0,
                'start_date' => now()->toDateString(),
                'end_date'   => now()->addDay()->toDateString(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['value']);
    }
    #[Test]
    public function test_create_promo_end_date_must_be_after_start_date(): void
    {
        $response = $this->withHeaders($this->adminHeaders())
            ->postJson('/api/admin/promos', [
                'name'       => 'Bad Dates',
                'type'       => 'percentage',
                'value'      => 10,
                'start_date' => now()->addDay()->toDateString(),
                'end_date'   => now()->toDateString(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    // -------------------------------------------------------------------------
    // Task 10.3 — Voucher validation
    // -------------------------------------------------------------------------
    #[Test]
    public function test_validate_valid_voucher_returns_discount_amount(): void
    {
        $this->makePromo([
            'code'  => 'VALID10',
            'type'  => 'percentage',
            'value' => 10,
        ]);

        $response = $this->postJson('/api/voucher/validate', [
            'code'       => 'VALID10',
            'cart_total' => 100000,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.discount_amount', 10000);
    }
    #[Test]
    public function test_validate_expired_voucher_returns_error(): void
    {
        $this->makePromo([
            'code'       => 'EXPIRED',
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date'   => now()->subDay()->toDateString(),
        ]);

        $response = $this->postJson('/api/voucher/validate', [
            'code'       => 'EXPIRED',
            'cart_total' => 100000,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.valid', false);

        $this->assertStringContainsString('kedaluwarsa', $response->json('message'));
    }
    #[Test]
    public function test_validate_voucher_with_cart_below_min_purchase_returns_error(): void
    {
        $this->makePromo([
            'code'         => 'MINPURCH',
            'min_purchase' => 200000,
        ]);

        $response = $this->postJson('/api/voucher/validate', [
            'code'       => 'MINPURCH',
            'cart_total' => 100000,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.valid', false);

        $this->assertStringContainsString('minimum', $response->json('message'));
    }
    #[Test]
    public function test_validate_non_existent_voucher_returns_error(): void
    {
        $response = $this->postJson('/api/voucher/validate', [
            'code'       => 'DOESNOTEXIST',
            'cart_total' => 100000,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.valid', false);
    }
    #[Test]
    public function test_validate_inactive_voucher_returns_error(): void
    {
        $this->makePromo([
            'code'      => 'INACTIVE',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/voucher/validate', [
            'code'       => 'INACTIVE',
            'cart_total' => 100000,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.valid', false);
    }

    // -------------------------------------------------------------------------
    // Task 10.2 — Discount calculation
    // -------------------------------------------------------------------------
    #[Test]
    public function test_percentage_discount_calculation_with_max_discount_cap(): void
    {
        $promo = $this->makePromo([
            'type'         => 'percentage',
            'value'        => 50,       // 50%
            'max_discount' => 20000,    // capped at 20000
        ]);

        // 50% of 100000 = 50000, but capped at 20000
        $discount = $this->promoService->calculateDiscount($promo, 100000);

        $this->assertEquals(20000.0, $discount);
    }
    #[Test]
    public function test_percentage_discount_calculation_without_cap(): void
    {
        $promo = $this->makePromo([
            'type'         => 'percentage',
            'value'        => 10,
            'max_discount' => null,
        ]);

        // 10% of 80000 = 8000
        $discount = $this->promoService->calculateDiscount($promo, 80000);

        $this->assertEquals(8000.0, $discount);
    }
    #[Test]
    public function test_nominal_discount_calculation(): void
    {
        $promo = $this->makePromo([
            'type'  => 'nominal',
            'value' => 25000,
        ]);

        // Flat 25000 discount
        $discount = $this->promoService->calculateDiscount($promo, 100000);

        $this->assertEquals(25000.0, $discount);
    }
    #[Test]
    public function test_nominal_discount_cannot_exceed_cart_total(): void
    {
        $promo = $this->makePromo([
            'type'  => 'nominal',
            'value' => 150000,
        ]);

        // Cart total is 50000, discount capped at 50000
        $discount = $this->promoService->calculateDiscount($promo, 50000);

        $this->assertEquals(50000.0, $discount);
    }

    // -------------------------------------------------------------------------
    // Task 10.5 — Voucher usage recording
    // -------------------------------------------------------------------------
    #[Test]
    public function test_voucher_usage_is_recorded_after_applying(): void
    {
        $promo = $this->makePromo([
            'code'  => 'RECORD10',
            'type'  => 'percentage',
            'value' => 10,
        ]);

        $order = $this->makeOrder(['total_price' => 100000]);

        $this->promoService->applyVoucher('RECORD10', $order, $this->customer->id);

        $this->assertDatabaseHas('voucher_usage', [
            'promo_id' => $promo->id,
            'user_id'  => $this->customer->id,
            'order_id' => $order->id,
        ]);
    }
    #[Test]
    public function test_usage_count_increments_after_applying_voucher(): void
    {
        $promo = $this->makePromo([
            'code'        => 'COUNTME',
            'usage_count' => 0,
        ]);

        $order = $this->makeOrder(['total_price' => 100000]);

        $this->promoService->applyVoucher('COUNTME', $order, $this->customer->id);

        $promo->refresh();
        $this->assertEquals(1, $promo->usage_count);
    }
    #[Test]
    public function test_usage_limit_is_enforced_when_limit_reached(): void
    {
        $this->makePromo([
            'code'        => 'LIMITED',
            'usage_limit' => 2,
            'usage_count' => 2,  // already at limit
        ]);

        $response = $this->postJson('/api/voucher/validate', [
            'code'       => 'LIMITED',
            'cart_total' => 100000,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.valid', false);

        $this->assertStringContainsString('batas', $response->json('message'));
    }
    #[Test]
    public function test_voucher_within_usage_limit_is_accepted(): void
    {
        $this->makePromo([
            'code'        => 'NOTLIMITED',
            'usage_limit' => 5,
            'usage_count' => 3,  // still has capacity
        ]);

        $response = $this->postJson('/api/voucher/validate', [
            'code'       => 'NOTLIMITED',
            'cart_total' => 100000,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.valid', true);
    }

    // -------------------------------------------------------------------------
    // Task 10.7 — Active promos endpoint
    // -------------------------------------------------------------------------
    #[Test]
    public function test_get_active_promos_returns_only_active_promos_within_date_range(): void
    {
        // Active and within date range
        $this->makePromo(['code' => 'ACTIVE1', 'is_active' => true]);

        // Active but expired
        $this->makePromo([
            'code'       => 'EXPIRED2',
            'is_active'  => true,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date'   => now()->subDay()->toDateString(),
        ]);

        // Inactive but within date range
        $this->makePromo([
            'code'      => 'INACTIVE3',
            'is_active' => false,
        ]);

        // Active and within date range (no code — banner promo)
        Promo::create([
            'name'       => 'Banner Promo',
            'code'       => null,
            'type'       => 'percentage',
            'value'      => 5,
            'start_date' => now()->subDay()->toDateString(),
            'end_date'   => now()->addDay()->toDateString(),
            'is_active'  => true,
        ]);

        $response = $this->getJson('/api/promos/active');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        $codes = array_column($data, 'code');
        $this->assertContains('ACTIVE1', $codes);
        $this->assertNotContains('EXPIRED2', $codes);
        $this->assertNotContains('INACTIVE3', $codes);
    }
    #[Test]
    public function test_get_active_promos_returns_empty_when_none_active(): void
    {
        // Only expired promo
        $this->makePromo([
            'code'       => 'OLDPROMO',
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date'   => now()->subDay()->toDateString(),
        ]);

        $response = $this->getJson('/api/promos/active');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }
    #[Test]
    public function test_active_promos_not_yet_started_are_excluded(): void
    {
        $this->makePromo([
            'code'       => 'FUTURE',
            'start_date' => now()->addDay()->toDateString(),
            'end_date'   => now()->addDays(10)->toDateString(),
            'is_active'  => true,
        ]);

        $response = $this->getJson('/api/promos/active');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }
    #[Test]
    public function test_active_promos_with_exhausted_usage_limit_are_excluded(): void
    {
        $this->makePromo([
            'code'        => 'USEDUP',
            'is_active'   => true,
            'usage_limit' => 5,
            'usage_count' => 5,
        ]);

        $this->makePromo([
            'code'        => 'AVAILABLE',
            'is_active'   => true,
            'usage_limit' => 5,
            'usage_count' => 4,
        ]);

        $response = $this->getJson('/api/promos/active');

        $response->assertStatus(200);

        $codes = array_column($response->json('data'), 'code');
        $this->assertContains('AVAILABLE', $codes);
        $this->assertNotContains('USEDUP', $codes);
    }

    // -------------------------------------------------------------------------
    // Task 10.4 — Apply voucher updates order discount_amount
    // -------------------------------------------------------------------------
    #[Test]
    public function test_apply_voucher_updates_order_discount_amount(): void
    {
        $this->makePromo([
            'code'  => 'APPLY10',
            'type'  => 'percentage',
            'value' => 10,
        ]);

        $order = $this->makeOrder(['total_price' => 100000, 'discount_amount' => 0]);

        $discountApplied = $this->promoService->applyVoucher('APPLY10', $order, $this->customer->id);

        $this->assertEquals(10000.0, $discountApplied);

        $order->refresh();
        $this->assertEquals('10000.00', $order->discount_amount);
    }
    #[Test]
    public function test_apply_invalid_voucher_throws_validation_exception(): void
    {
        $this->makePromo([
            'code'      => 'NOTACTIVE',
            'is_active' => false,
        ]);

        $order = $this->makeOrder(['total_price' => 100000]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->promoService->applyVoucher('NOTACTIVE', $order, $this->customer->id);
    }

    // -------------------------------------------------------------------------
    // Task 10.6 — POST /api/voucher/validate endpoint
    // -------------------------------------------------------------------------
    #[Test]
    public function test_voucher_validate_endpoint_requires_code_and_cart_total(): void
    {
        $response = $this->postJson('/api/voucher/validate', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'cart_total']);
    }
    #[Test]
    public function test_voucher_validate_endpoint_returns_promo_details_on_success(): void
    {
        $this->makePromo([
            'code'  => 'DETAILS',
            'type'  => 'nominal',
            'value' => 10000,
        ]);

        $response = $this->postJson('/api/voucher/validate', [
            'code'       => 'DETAILS',
            'cart_total' => 50000,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.discount_amount', 10000)
            ->assertJsonPath('data.promo.code', 'DETAILS')
            ->assertJsonPath('data.promo.type', 'nominal');
    }
}
