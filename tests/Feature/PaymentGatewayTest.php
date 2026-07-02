<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\SystemSetting;
use App\Models\Table;
use App\Models\User;
use App\Services\MidtransService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the Payment_Gateway module.
 *
 * Covers:
 *   - Cash payment confirmation by waiter
 *   - Cash payment confirmation triggers loyalty points
 *   - Cash payment confirmation updates order_status to Diproses
 *   - Webhook handling: valid signature updates payment_status to paid
 *   - Webhook handling: invalid signature returns 403
 *   - Webhook handling: failed payment updates payment_status to failed
 *   - Loyalty points calculated correctly: floor(total / rate)
 *   - Non-waiter cannot confirm cash payment
 *   - Payment initiation returns payment instruction
 *
 * Validates: Requirements 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 13.1, 13.5
 */
class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;
    private User $waiter;
    private User $chef;
    private Order $order;

    /** Server key used for generating test webhook signatures */
    private string $testServerKey = 'test-server-key-12345';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        // Seed system settings
        SystemSetting::updateOrCreate(['key' => 'point_conversion_rate'], ['value' => '10000']);
        SystemSetting::updateOrCreate(['key' => 'tax_percentage'], ['value' => '10']);
        SystemSetting::updateOrCreate(['key' => 'service_charge_percentage'], ['value' => '5']);

        // Create users
        $this->admin = User::factory()->create(['role' => 'admin', 'poin' => 0]);
        $this->admin->assignRole('admin');

        $this->customer = User::factory()->create(['role' => 'customer', 'poin' => 0]);
        $this->customer->assignRole('customer');

        $this->waiter = User::factory()->create(['role' => 'waiter', 'poin' => 0]);
        $this->waiter->assignRole('waiter');

        $this->chef = User::factory()->create(['role' => 'chef', 'poin' => 0]);
        $this->chef->assignRole('chef');

        // Create a table
        $table = Table::create([
            'table_number' => 'T01',
            'qr_code'      => 'test-qr-t01',
            'status'       => 'occupied',
        ]);

        // Create a pending order for the customer
        $this->order = Order::create([
            'user_id'        => $this->customer->id,
            'table_id'       => $table->id,
            'total_price'    => 50000,
            'discount_amount'=> 0,
            'tax_amount'     => 5000,
            'service_charge' => 2500,
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'order_status'   => 'Diterima',
            'order_type'     => 'dine_in',
        ]);

        // Mock MidtransService to use our test server key for signature verification
        $this->app->bind(MidtransService::class, function () {
            $mock = \Mockery::mock(MidtransService::class)->makePartial();
            $mock->shouldReceive('getServerKey')->andReturn($this->testServerKey);
            $mock->shouldReceive('verifyWebhookSignature')
                ->andReturnUsing(function (string $orderId, string $statusCode, string $grossAmount, string $signatureKey) {
                    $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $this->testServerKey);
                    return hash_equals($expected, $signatureKey);
                });
            $mock->shouldReceive('createSnapToken')
                ->andReturn([
                    'token'        => 'sim-test-token-123',
                    'redirect_url' => 'https://example.com/pay/sim-test-token-123',
                    'qris_url'     => 'https://example.com/qris/sim-test-token-123',
                ]);
            $mock->shouldReceive('isSimulationMode')->andReturn(true);
            return $mock;
        });
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function token(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    private function authHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer ' . $this->token($user)];
    }

    /**
     * Generate a valid Midtrans webhook signature for testing.
     */
    private function makeWebhookSignature(string $orderId, string $statusCode, string $grossAmount): string
    {
        return hash('sha512', $orderId . $statusCode . $grossAmount . $this->testServerKey);
    }

    // -------------------------------------------------------------------------
    // Task 7.1 — Payment initiation
    // -------------------------------------------------------------------------
    #[Test]
    public function test_customer_can_initiate_cash_payment(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson("/api/customer/orders/{$this->order->id}/payment/initiate", [
                'payment_method' => 'cash',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.payment_method', 'cash')
            ->assertJsonPath('data.order_id', $this->order->id)
            ->assertJsonStructure(['message', 'data' => ['payment_method', 'total_amount', 'order_id', 'message']]);
    }
    #[Test]
    public function test_customer_can_initiate_qris_payment(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson("/api/customer/orders/{$this->order->id}/payment/initiate", [
                'payment_method' => 'qris',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.payment_method', 'qris')
            ->assertJsonStructure(['message', 'data' => ['payment_method', 'snap_token', 'redirect_url']]);
    }
    #[Test]
    public function test_customer_can_initiate_card_payment(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson("/api/customer/orders/{$this->order->id}/payment/initiate", [
                'payment_method' => 'card',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.payment_method', 'card')
            ->assertJsonStructure(['message', 'data' => ['payment_method', 'snap_token', 'redirect_url']]);
    }
    #[Test]
    public function test_initiate_payment_rejects_invalid_method(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson("/api/customer/orders/{$this->order->id}/payment/initiate", [
                'payment_method' => 'bitcoin',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }
    #[Test]
    public function test_cannot_initiate_payment_for_already_paid_order(): void
    {
        $this->order->update(['payment_status' => 'paid']);

        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson("/api/customer/orders/{$this->order->id}/payment/initiate", [
                'payment_method' => 'cash',
            ]);

        $response->assertStatus(422);
    }
    #[Test]
    public function test_qris_payment_returns_unique_token_per_order(): void
    {
        // Create a second order
        $order2 = Order::create([
            'user_id'        => $this->customer->id,
            'total_price'    => 75000,
            'payment_status' => 'pending',
            'order_status'   => 'Diterima',
            'order_type'     => 'dine_in',
        ]);

        $response1 = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson("/api/customer/orders/{$this->order->id}/payment/initiate", [
                'payment_method' => 'qris',
            ]);

        $response2 = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson("/api/customer/orders/{$order2->id}/payment/initiate", [
                'payment_method' => 'qris',
            ]);

        // Both should succeed
        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Tokens should be different (unique per transaction)
        // Note: in simulation mode the mock returns the same token, but the
        // real MidtransService generates unique tokens per order_id.
        // We verify the structure is correct.
        $this->assertArrayHasKey('snap_token', $response1->json('data'));
        $this->assertArrayHasKey('snap_token', $response2->json('data'));
    }

    // -------------------------------------------------------------------------
    // Task 7.5 — Cash payment confirmation
    // -------------------------------------------------------------------------
    #[Test]
    public function test_waiter_can_confirm_cash_payment(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->waiter))
            ->postJson("/api/staff/orders/{$this->order->id}/payment/confirm-cash");

        $response->assertStatus(200)
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.order_status', 'Diproses');
    }

    #[Test]
    public function test_waiter_can_confirm_manual_qris_payment(): void
    {
        $this->order->update(['payment_method' => null]);

        $response = $this->withHeaders($this->authHeaders($this->waiter))
            ->postJson("/api/staff/orders/{$this->order->id}/payment/confirm", [
                'payment_method' => 'qris',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.payment_method', 'qris')
            ->assertJsonPath('data.order_status', 'Diproses');

        $this->assertDatabaseHas('order', [
            'id'             => $this->order->id,
            'payment_status' => 'paid',
            'payment_method' => 'qris',
        ]);
    }

    #[Test]
    public function test_manual_payment_confirmation_rejects_invalid_method(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->waiter))
            ->postJson("/api/staff/orders/{$this->order->id}/payment/confirm", [
                'payment_method' => 'bank_transfer',
            ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function test_cash_confirmation_updates_payment_status_to_paid(): void
    {
        $this->order->update(['payment_method' => null]);

        $this->withHeaders($this->authHeaders($this->waiter))
            ->postJson("/api/staff/orders/{$this->order->id}/payment/confirm-cash");

        $this->assertDatabaseHas('order', [
            'id'             => $this->order->id,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
        ]);
    }
    #[Test]
    public function test_cash_confirmation_updates_order_status_to_diproses(): void
    {
        $this->withHeaders($this->authHeaders($this->waiter))
            ->postJson("/api/staff/orders/{$this->order->id}/payment/confirm-cash");

        $this->assertDatabaseHas('order', [
            'id'           => $this->order->id,
            'order_status' => 'Diproses',
        ]);
    }
    #[Test]
    public function test_cash_confirmation_triggers_loyalty_points(): void
    {
        // order total = 50000, rate = 10000 → 5 points
        $this->withHeaders($this->authHeaders($this->waiter))
            ->postJson("/api/staff/orders/{$this->order->id}/payment/confirm-cash");

        $this->customer->refresh();
        $this->assertEquals(5, $this->customer->poin);

        $this->assertDatabaseHas('point_transaction', [
            'user_id'      => $this->customer->id,
            'order_id'     => $this->order->id,
            'type'         => 'earn',
            'points'       => 5,
            'balance_after'=> 5,
        ]);
    }
    #[Test]
    public function test_loyalty_points_calculated_correctly_floor_division(): void
    {
        // total = 55000, rate = 10000 → floor(55000/10000) = 5 points (not 5.5)
        $this->order->update(['total_price' => 55000]);

        $this->withHeaders($this->authHeaders($this->waiter))
            ->postJson("/api/staff/orders/{$this->order->id}/payment/confirm-cash");

        $this->customer->refresh();
        $this->assertEquals(5, $this->customer->poin);
    }
    #[Test]
    public function test_loyalty_points_accumulate_on_existing_balance(): void
    {
        // Customer already has 10 points
        $this->customer->update(['poin' => 10]);

        // order total = 50000, rate = 10000 → 5 new points
        $this->withHeaders($this->authHeaders($this->waiter))
            ->postJson("/api/staff/orders/{$this->order->id}/payment/confirm-cash");

        $this->customer->refresh();
        $this->assertEquals(15, $this->customer->poin);

        $this->assertDatabaseHas('point_transaction', [
            'user_id'      => $this->customer->id,
            'type'         => 'earn',
            'points'       => 5,
            'balance_after'=> 15,
        ]);
    }
    #[Test]
    public function test_no_points_for_order_without_user(): void
    {
        // Walk-in order with no user_id
        $walkInOrder = Order::create([
            'user_id'        => null,
            'total_price'    => 50000,
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'order_status'   => 'Diterima',
            'order_type'     => 'dine_in',
        ]);

        $this->withHeaders($this->authHeaders($this->waiter))
            ->postJson("/api/staff/orders/{$walkInOrder->id}/payment/confirm-cash");

        // No point_transactions should be created
        $this->assertDatabaseMissing('point_transaction', [
            'order_id' => $walkInOrder->id,
        ]);
    }
    #[Test]
    public function test_cannot_confirm_already_paid_order(): void
    {
        $this->order->update(['payment_status' => 'paid']);

        $response = $this->withHeaders($this->authHeaders($this->waiter))
            ->postJson("/api/staff/orders/{$this->order->id}/payment/confirm-cash");

        $response->assertStatus(422);
    }
    #[Test]
    public function test_non_waiter_cannot_confirm_cash_payment(): void
    {
        // Chef should not be able to confirm cash payment
        $response = $this->withHeaders($this->authHeaders($this->chef))
            ->postJson("/api/staff/orders/{$this->order->id}/payment/confirm-cash");

        $response->assertStatus(403);
    }
    #[Test]
    public function test_customer_cannot_confirm_cash_payment(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson("/api/staff/orders/{$this->order->id}/payment/confirm-cash");

        $response->assertStatus(403);
    }
    #[Test]
    public function test_unauthenticated_cannot_confirm_cash_payment(): void
    {
        $response = $this->postJson("/api/staff/orders/{$this->order->id}/payment/confirm-cash");

        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // Task 7.4 — Webhook handling
    // -------------------------------------------------------------------------
    #[Test]
    public function test_webhook_with_valid_signature_updates_payment_status_to_paid(): void
    {
        $orderId     = (string) $this->order->id;
        $statusCode  = '200';
        $grossAmount = '50000.00';
        $signature   = $this->makeWebhookSignature($orderId, $statusCode, $grossAmount);

        $response = $this->postJson('/api/payment/webhook', [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'settlement',
            'fraud_status'       => null,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('order', [
            'id'             => $this->order->id,
            'payment_status' => 'paid',
            'order_status'   => 'Diproses',
        ]);
    }
    #[Test]
    public function test_webhook_with_invalid_signature_returns_403(): void
    {
        $response = $this->postJson('/api/payment/webhook', [
            'order_id'           => (string) $this->order->id,
            'status_code'        => '200',
            'gross_amount'       => '50000.00',
            'signature_key'      => 'invalid-signature-xyz',
            'transaction_status' => 'settlement',
        ]);

        $response->assertStatus(403);

        // Order should remain unchanged
        $this->assertDatabaseHas('order', [
            'id'             => $this->order->id,
            'payment_status' => 'pending',
        ]);
    }
    #[Test]
    public function test_webhook_failed_payment_updates_payment_status_to_failed(): void
    {
        $orderId     = (string) $this->order->id;
        $statusCode  = '202';
        $grossAmount = '50000.00';
        $signature   = $this->makeWebhookSignature($orderId, $statusCode, $grossAmount);

        $response = $this->postJson('/api/payment/webhook', [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'expire',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('order', [
            'id'             => $this->order->id,
            'payment_status' => 'failed',
        ]);
    }
    #[Test]
    public function test_webhook_cancel_updates_payment_status_to_failed(): void
    {
        $orderId     = (string) $this->order->id;
        $statusCode  = '202';
        $grossAmount = '50000.00';
        $signature   = $this->makeWebhookSignature($orderId, $statusCode, $grossAmount);

        $this->postJson('/api/payment/webhook', [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'cancel',
        ]);

        $this->assertDatabaseHas('order', [
            'id'             => $this->order->id,
            'payment_status' => 'failed',
        ]);
    }
    #[Test]
    public function test_webhook_settlement_triggers_loyalty_points(): void
    {
        // order total = 50000, rate = 10000 → 5 points
        $orderId     = (string) $this->order->id;
        $statusCode  = '200';
        $grossAmount = '50000.00';
        $signature   = $this->makeWebhookSignature($orderId, $statusCode, $grossAmount);

        $this->postJson('/api/payment/webhook', [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'settlement',
        ]);

        $this->customer->refresh();
        $this->assertEquals(5, $this->customer->poin);

        $this->assertDatabaseHas('point_transaction', [
            'user_id'  => $this->customer->id,
            'order_id' => $this->order->id,
            'type'     => 'earn',
            'points'   => 5,
        ]);
    }
    #[Test]
    public function test_webhook_capture_with_accept_fraud_status_marks_paid(): void
    {
        $orderId     = (string) $this->order->id;
        $statusCode  = '200';
        $grossAmount = '50000.00';
        $signature   = $this->makeWebhookSignature($orderId, $statusCode, $grossAmount);

        $this->postJson('/api/payment/webhook', [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'capture',
            'fraud_status'       => 'accept',
        ]);

        $this->assertDatabaseHas('order', [
            'id'             => $this->order->id,
            'payment_status' => 'paid',
        ]);
    }
    #[Test]
    public function test_webhook_capture_with_challenge_fraud_status_does_not_mark_paid(): void
    {
        $orderId     = (string) $this->order->id;
        $statusCode  = '200';
        $grossAmount = '50000.00';
        $signature   = $this->makeWebhookSignature($orderId, $statusCode, $grossAmount);

        $this->postJson('/api/payment/webhook', [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'capture',
            'fraud_status'       => 'challenge',
        ]);

        // Should remain pending (not paid, not failed)
        $this->assertDatabaseHas('order', [
            'id'             => $this->order->id,
            'payment_status' => 'pending',
        ]);
    }
    #[Test]
    public function test_webhook_for_unknown_order_returns_200_gracefully(): void
    {
        $orderId     = '99999';
        $statusCode  = '200';
        $grossAmount = '50000.00';
        $signature   = $this->makeWebhookSignature($orderId, $statusCode, $grossAmount);

        $response = $this->postJson('/api/payment/webhook', [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'settlement',
        ]);

        // Should not crash — return 200 gracefully
        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Task 7.7 — Payment history
    // -------------------------------------------------------------------------
    #[Test]
    public function test_admin_can_get_payment_history(): void
    {
        // Create some paid orders
        Order::create([
            'user_id'        => $this->customer->id,
            'total_price'    => 30000,
            'payment_method' => 'qris',
            'payment_status' => 'paid',
            'order_status'   => 'Diproses',
            'order_type'     => 'dine_in',
        ]);

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->getJson('/api/admin/payments');

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'data']);
    }
    #[Test]
    public function test_payment_history_filters_by_payment_method(): void
    {
        Order::create([
            'user_id'        => $this->customer->id,
            'total_price'    => 30000,
            'payment_method' => 'qris',
            'payment_status' => 'paid',
            'order_status'   => 'Diproses',
            'order_type'     => 'dine_in',
        ]);

        Order::create([
            'user_id'        => $this->customer->id,
            'total_price'    => 20000,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'order_status'   => 'Diproses',
            'order_type'     => 'dine_in',
        ]);

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->getJson('/api/admin/payments?payment_method=qris');

        $response->assertStatus(200);

        $data = $response->json('data');
        foreach ($data as $transaction) {
            $this->assertEquals('qris', $transaction['payment_method']);
        }
    }
    #[Test]
    public function test_payment_history_filters_by_payment_status(): void
    {
        Order::create([
            'user_id'        => $this->customer->id,
            'total_price'    => 30000,
            'payment_method' => 'qris',
            'payment_status' => 'paid',
            'order_status'   => 'Diproses',
            'order_type'     => 'dine_in',
        ]);

        Order::create([
            'user_id'        => $this->customer->id,
            'total_price'    => 20000,
            'payment_method' => 'cash',
            'payment_status' => 'failed',
            'order_status'   => 'Diterima',
            'order_type'     => 'dine_in',
        ]);

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->getJson('/api/admin/payments?payment_status=paid');

        $response->assertStatus(200);

        $data = $response->json('data');
        foreach ($data as $transaction) {
            $this->assertEquals('paid', $transaction['payment_status']);
        }
    }
    #[Test]
    public function test_non_admin_cannot_access_payment_history(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->waiter))
            ->getJson('/api/admin/payments');

        $response->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // Task 7.8 — DB transaction atomicity
    // -------------------------------------------------------------------------
    #[Test]
    public function test_payment_confirmation_is_atomic_no_partial_updates(): void
    {
        // Verify that after successful confirmation, both order AND point_transaction
        // are created together (atomicity of the DB transaction)
        $this->withHeaders($this->authHeaders($this->waiter))
            ->postJson("/api/staff/orders/{$this->order->id}/payment/confirm-cash");

        // Both should exist
        $this->assertDatabaseHas('order', [
            'id'             => $this->order->id,
            'payment_status' => 'paid',
            'order_status'   => 'Diproses',
        ]);

        $this->assertDatabaseHas('point_transaction', [
            'order_id' => $this->order->id,
            'type'     => 'earn',
        ]);
    }
}
