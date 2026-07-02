<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\SystemSetting;
use App\Models\Table;
use App\Models\User;
use App\Services\LoyaltyService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the Loyalty_Engine module.
 *
 * Covers:
 *   - Get point balance returns correct balance
 *   - Add points: floor(total / rate) calculation
 *   - Add points: proportional to transaction amount
 *   - Add points: creates point_transaction type earn
 *   - Add points: balance_after is correct
 *   - Redeem points: valid redemption reduces balance
 *   - Redeem points: creates point_transaction type redeem
 *   - Redeem points: balance_after is correct
 *   - Redeem points: rejected when balance insufficient
 *   - Round-trip: earn then redeem returns to original balance
 *   - Redeem endpoint: customer can redeem points
 *   - Redeem endpoint: rejected when insufficient balance
 *   - History endpoint: returns all transactions for user
 *   - Balance endpoint: returns current balance
 *
 * Validates: Requirements 13.1, 13.2, 13.3, 13.4, 13.5
 */
class LoyaltyEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $admin;
    private LoyaltyService $loyaltyService;

    /** Default point_conversion_rate: Rp 10.000 = 1 point */
    private int $conversionRate = 10000;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        // Seed system settings
        SystemSetting::updateOrCreate(
            ['key' => 'point_conversion_rate'],
            ['value' => (string) $this->conversionRate]
        );
        SystemSetting::updateOrCreate(
            ['key' => 'point_value'],
            ['value' => '1'] // 1 point = Rp 1 discount
        );

        // Create users
        $this->customer = User::factory()->create(['role' => 'customer', 'poin' => 0]);
        $this->customer->assignRole('customer');

        $this->admin = User::factory()->create(['role' => 'admin', 'poin' => 0]);
        $this->admin->assignRole('admin');

        $this->loyaltyService = app(LoyaltyService::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function authHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer ' . $user->createToken('test')->plainTextToken];
    }

    private function createOrder(User $user, float $total): Order
    {
        return Order::create([
            'user_id'        => $user->id,
            'total_price'    => $total,
            'discount_amount'=> 0,
            'tax_amount'     => 0,
            'service_charge' => 0,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'order_status'   => 'Diproses',
            'order_type'     => 'dine_in',
        ]);
    }

    // =========================================================================
    // 11.2 — Point Accumulation (addPoints / calculatePointsForTransaction)
    // =========================================================================
    #[Test]
    public function test_calculate_points_uses_floor_division(): void
    {
        // floor(55000 / 10000) = 5, not 5.5
        $points = $this->loyaltyService->calculatePointsForTransaction(55000);
        $this->assertSame(5, $points);
    }
    #[Test]
    public function test_calculate_points_exact_multiple(): void
    {
        // floor(50000 / 10000) = 5
        $points = $this->loyaltyService->calculatePointsForTransaction(50000);
        $this->assertSame(5, $points);
    }
    #[Test]
    public function test_calculate_points_proportional_to_amount(): void
    {
        // Larger amount → more points
        $points100k = $this->loyaltyService->calculatePointsForTransaction(100000);
        $points50k  = $this->loyaltyService->calculatePointsForTransaction(50000);

        $this->assertSame(10, $points100k);
        $this->assertSame(5, $points50k);
        $this->assertGreaterThan($points50k, $points100k);
    }
    #[Test]
    public function test_calculate_points_returns_zero_for_amount_below_rate(): void
    {
        // floor(5000 / 10000) = 0
        $points = $this->loyaltyService->calculatePointsForTransaction(5000);
        $this->assertSame(0, $points);
    }
    #[Test]
    public function test_add_points_increases_user_balance(): void
    {
        $order = $this->createOrder($this->customer, 50000);

        $newBalance = $this->loyaltyService->addPoints($this->customer->id, 50000, $order->id);

        $this->assertSame(5, $newBalance);
        $this->customer->refresh();
        $this->assertSame(5, $this->customer->poin);
    }
    #[Test]
    public function test_add_points_creates_earn_transaction(): void
    {
        $order = $this->createOrder($this->customer, 50000);

        $this->loyaltyService->addPoints($this->customer->id, 50000, $order->id);

        $this->assertDatabaseHas('point_transaction', [
            'user_id'  => $this->customer->id,
            'order_id' => $order->id,
            'type'     => 'earn',
            'points'   => 5,
        ]);
    }
    #[Test]
    public function test_add_points_records_correct_balance_after(): void
    {
        // Customer starts with 10 points
        $this->customer->update(['poin' => 10]);
        $order = $this->createOrder($this->customer, 50000);

        $this->loyaltyService->addPoints($this->customer->id, 50000, $order->id);

        $this->assertDatabaseHas('point_transaction', [
            'user_id'       => $this->customer->id,
            'type'          => 'earn',
            'points'        => 5,
            'balance_after' => 15, // 10 + 5
        ]);
    }
    #[Test]
    public function test_add_points_accumulates_on_existing_balance(): void
    {
        $this->customer->update(['poin' => 20]);

        $newBalance = $this->loyaltyService->addPoints($this->customer->id, 30000);

        // floor(30000 / 10000) = 3 points; 20 + 3 = 23
        $this->assertSame(23, $newBalance);
        $this->customer->refresh();
        $this->assertSame(23, $this->customer->poin);
    }
    #[Test]
    public function test_add_points_does_nothing_when_amount_below_rate(): void
    {
        $this->customer->update(['poin' => 5]);

        $newBalance = $this->loyaltyService->addPoints($this->customer->id, 5000);

        // floor(5000 / 10000) = 0 → no change
        $this->assertSame(5, $newBalance);
        $this->assertDatabaseMissing('point_transaction', [
            'user_id' => $this->customer->id,
            'type'    => 'earn',
        ]);
    }

    // =========================================================================
    // 11.3 & 11.4 — Point Redemption (redeemPoints)
    // =========================================================================
    #[Test]
    public function test_redeem_points_reduces_user_balance(): void
    {
        $this->customer->update(['poin' => 20]);

        $result = $this->loyaltyService->redeemPoints($this->customer->id, 10);

        $this->assertTrue($result['success']);
        $this->assertSame(10, $result['new_balance']);
        $this->customer->refresh();
        $this->assertSame(10, $this->customer->poin);
    }
    #[Test]
    public function test_redeem_points_creates_redeem_transaction(): void
    {
        $this->customer->update(['poin' => 20]);
        $order = $this->createOrder($this->customer, 50000);

        $this->loyaltyService->redeemPoints($this->customer->id, 10, $order->id);

        $this->assertDatabaseHas('point_transaction', [
            'user_id'  => $this->customer->id,
            'order_id' => $order->id,
            'type'     => 'redeem',
            'points'   => 10,
        ]);
    }
    #[Test]
    public function test_redeem_points_records_correct_balance_after(): void
    {
        $this->customer->update(['poin' => 20]);

        $this->loyaltyService->redeemPoints($this->customer->id, 10);

        $this->assertDatabaseHas('point_transaction', [
            'user_id'       => $this->customer->id,
            'type'          => 'redeem',
            'points'        => 10,
            'balance_after' => 10, // 20 - 10
        ]);
    }
    #[Test]
    public function test_redeem_points_calculates_discount_amount(): void
    {
        $this->customer->update(['poin' => 50]);

        $result = $this->loyaltyService->redeemPoints($this->customer->id, 30);

        // 1 point = Rp 1 discount (point_value = 1)
        $this->assertTrue($result['success']);
        $this->assertSame(30.0, $result['discount_amount']);
    }
    #[Test]
    public function test_redeem_points_rejected_when_balance_insufficient(): void
    {
        $this->customer->update(['poin' => 5]);

        $result = $this->loyaltyService->redeemPoints($this->customer->id, 10);

        $this->assertFalse($result['success']);
        $this->assertSame(5, $result['available_balance']);
        $this->assertSame(5, $result['new_balance']); // balance unchanged

        // No transaction should be recorded
        $this->assertDatabaseMissing('point_transaction', [
            'user_id' => $this->customer->id,
            'type'    => 'redeem',
        ]);
    }
    #[Test]
    public function test_redeem_points_rejected_when_balance_is_zero(): void
    {
        $this->customer->update(['poin' => 0]);

        $result = $this->loyaltyService->redeemPoints($this->customer->id, 1);

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['available_balance']);
    }
    #[Test]
    public function test_redeem_points_rejected_for_zero_or_negative_amount(): void
    {
        $this->customer->update(['poin' => 100]);

        $result = $this->loyaltyService->redeemPoints($this->customer->id, 0);

        $this->assertFalse($result['success']);
        // Balance unchanged
        $this->customer->refresh();
        $this->assertSame(100, $this->customer->poin);
    }

    // =========================================================================
    // Round-trip: earn then redeem returns to original balance
    // =========================================================================
    #[Test]
    public function test_round_trip_earn_then_redeem_returns_to_original_balance(): void
    {
        $initialBalance = 0;
        $this->customer->update(['poin' => $initialBalance]);

        // Earn 5 points from a Rp 50.000 transaction
        $this->loyaltyService->addPoints($this->customer->id, 50000);
        $this->customer->refresh();
        $this->assertSame(5, $this->customer->poin);

        // Redeem all 5 points
        $result = $this->loyaltyService->redeemPoints($this->customer->id, 5);
        $this->assertTrue($result['success']);

        $this->customer->refresh();
        $this->assertSame($initialBalance, $this->customer->poin);
    }
    #[Test]
    public function test_round_trip_multiple_earn_and_redeem(): void
    {
        $this->customer->update(['poin' => 0]);

        // Earn 10 points
        $this->loyaltyService->addPoints($this->customer->id, 100000);
        // Earn 5 more points
        $this->loyaltyService->addPoints($this->customer->id, 50000);

        $this->customer->refresh();
        $this->assertSame(15, $this->customer->poin);

        // Redeem 10 points
        $this->loyaltyService->redeemPoints($this->customer->id, 10);

        $this->customer->refresh();
        $this->assertSame(5, $this->customer->poin);
    }

    // =========================================================================
    // 11.1 — LoyaltyController Endpoints
    // =========================================================================
    #[Test]
    public function test_balance_endpoint_returns_current_balance(): void
    {
        $this->customer->update(['poin' => 42]);

        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->getJson('/api/customer/loyalty/balance');

        $response->assertStatus(200)
            ->assertJsonPath('data.balance', 42)
            ->assertJsonPath('data.user_id', $this->customer->id)
            ->assertJsonStructure(['message', 'data' => ['user_id', 'balance']]);
    }
    #[Test]
    public function test_balance_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/customer/loyalty/balance');
        $response->assertStatus(401);
    }
    #[Test]
    public function test_balance_endpoint_requires_customer_role(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->getJson('/api/customer/loyalty/balance');

        $response->assertStatus(403);
    }
    #[Test]
    public function test_history_endpoint_returns_all_transactions_for_user(): void
    {
        $this->customer->update(['poin' => 0]);

        // Create some transactions
        $this->loyaltyService->addPoints($this->customer->id, 50000);
        $this->loyaltyService->addPoints($this->customer->id, 30000);

        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->getJson('/api/customer/loyalty/history');

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'data'])
            ->assertJsonCount(2, 'data');
    }
    #[Test]
    public function test_history_endpoint_returns_empty_for_new_user(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->getJson('/api/customer/loyalty/history');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }
    #[Test]
    public function test_history_endpoint_only_returns_own_transactions(): void
    {
        // Another customer
        $otherCustomer = User::factory()->create(['role' => 'customer', 'poin' => 0]);
        $otherCustomer->assignRole('customer');

        // Add points to both customers
        $this->loyaltyService->addPoints($this->customer->id, 50000);
        $this->loyaltyService->addPoints($otherCustomer->id, 50000);

        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->getJson('/api/customer/loyalty/history');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // Verify the transaction belongs to our customer
        $data = $response->json('data');
        $this->assertSame($this->customer->id, $data[0]['user_id']);
    }
    #[Test]
    public function test_redeem_endpoint_customer_can_redeem_points(): void
    {
        $this->customer->update(['poin' => 50]);

        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson('/api/customer/loyalty/redeem', [
                'points_to_redeem' => 20,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.new_balance', 30)
            ->assertJsonPath('data.discount_amount', 20)
            ->assertJsonStructure(['message', 'data' => ['discount_amount', 'new_balance']]);
    }
    #[Test]
    public function test_redeem_endpoint_rejected_when_insufficient_balance(): void
    {
        $this->customer->update(['poin' => 5]);

        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson('/api/customer/loyalty/redeem', [
                'points_to_redeem' => 10,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.available_balance', 5);
    }
    #[Test]
    public function test_redeem_endpoint_validates_points_to_redeem_is_positive(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson('/api/customer/loyalty/redeem', [
                'points_to_redeem' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['points_to_redeem']);
    }
    #[Test]
    public function test_redeem_endpoint_validates_points_to_redeem_is_required(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson('/api/customer/loyalty/redeem', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['points_to_redeem']);
    }
    #[Test]
    public function test_redeem_endpoint_requires_authentication(): void
    {
        $response = $this->postJson('/api/customer/loyalty/redeem', [
            'points_to_redeem' => 10,
        ]);

        $response->assertStatus(401);
    }
    #[Test]
    public function test_redeem_endpoint_with_order_id_links_transaction(): void
    {
        $this->customer->update(['poin' => 50]);
        $order = $this->createOrder($this->customer, 50000);

        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson('/api/customer/loyalty/redeem', [
                'points_to_redeem' => 10,
                'order_id'         => $order->id,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('point_transaction', [
            'user_id'  => $this->customer->id,
            'order_id' => $order->id,
            'type'     => 'redeem',
            'points'   => 10,
        ]);
    }

    // =========================================================================
    // 11.5 — PointTransaction recording
    // =========================================================================
    #[Test]
    public function test_point_transactions_record_earn_type(): void
    {
        $this->loyaltyService->addPoints($this->customer->id, 50000);

        $transaction = PointTransaction::where('user_id', $this->customer->id)->first();
        $this->assertNotNull($transaction);
        $this->assertSame('earn', $transaction->type);
    }
    #[Test]
    public function test_point_transactions_record_redeem_type(): void
    {
        $this->customer->update(['poin' => 20]);
        $this->loyaltyService->redeemPoints($this->customer->id, 10);

        $transaction = PointTransaction::where('user_id', $this->customer->id)->first();
        $this->assertNotNull($transaction);
        $this->assertSame('redeem', $transaction->type);
    }
    #[Test]
    public function test_point_transactions_record_balance_after_for_earn(): void
    {
        $this->customer->update(['poin' => 10]);
        $this->loyaltyService->addPoints($this->customer->id, 50000);

        $transaction = PointTransaction::where('user_id', $this->customer->id)
            ->where('type', 'earn')
            ->first();

        $this->assertNotNull($transaction);
        $this->assertSame(15, $transaction->balance_after); // 10 + 5
    }
    #[Test]
    public function test_point_transactions_record_balance_after_for_redeem(): void
    {
        $this->customer->update(['poin' => 20]);
        $this->loyaltyService->redeemPoints($this->customer->id, 8);

        $transaction = PointTransaction::where('user_id', $this->customer->id)
            ->where('type', 'redeem')
            ->first();

        $this->assertNotNull($transaction);
        $this->assertSame(12, $transaction->balance_after); // 20 - 8
    }

    // =========================================================================
    // 11.6 — DB Transaction atomicity
    // =========================================================================
    #[Test]
    public function test_add_points_is_atomic_user_balance_and_transaction_created_together(): void
    {
        $order = $this->createOrder($this->customer, 50000);

        $this->loyaltyService->addPoints($this->customer->id, 50000, $order->id);

        // Both the user balance update and the point_transaction record must exist
        $this->customer->refresh();
        $this->assertSame(5, $this->customer->poin);

        $this->assertDatabaseHas('point_transaction', [
            'user_id'       => $this->customer->id,
            'order_id'      => $order->id,
            'type'          => 'earn',
            'points'        => 5,
            'balance_after' => 5,
        ]);
    }
    #[Test]
    public function test_redeem_points_is_atomic_user_balance_and_transaction_created_together(): void
    {
        $this->customer->update(['poin' => 20]);
        $order = $this->createOrder($this->customer, 50000);

        $this->loyaltyService->redeemPoints($this->customer->id, 10, $order->id);

        // Both the user balance update and the point_transaction record must exist
        $this->customer->refresh();
        $this->assertSame(10, $this->customer->poin);

        $this->assertDatabaseHas('point_transaction', [
            'user_id'       => $this->customer->id,
            'order_id'      => $order->id,
            'type'          => 'redeem',
            'points'        => 10,
            'balance_after' => 10,
        ]);
    }

    // =========================================================================
    // getPointBalance
    // =========================================================================
    #[Test]
    public function test_get_point_balance_returns_correct_balance(): void
    {
        $this->customer->update(['poin' => 77]);

        $balance = $this->loyaltyService->getPointBalance($this->customer->id);

        $this->assertSame(77, $balance);
    }
    #[Test]
    public function test_get_point_balance_returns_zero_for_new_user(): void
    {
        $balance = $this->loyaltyService->getPointBalance($this->customer->id);
        $this->assertSame(0, $balance);
    }
    #[Test]
    public function test_get_point_balance_returns_zero_for_nonexistent_user(): void
    {
        $balance = $this->loyaltyService->getPointBalance(99999);
        $this->assertSame(0, $balance);
    }

    // =========================================================================
    // getPointHistory
    // =========================================================================
    #[Test]
    public function test_get_point_history_returns_transactions_ordered_by_most_recent(): void
    {
        $this->customer->update(['poin' => 0]);

        $this->loyaltyService->addPoints($this->customer->id, 50000);
        $this->loyaltyService->addPoints($this->customer->id, 30000);

        $history = $this->loyaltyService->getPointHistory($this->customer->id);

        $this->assertCount(2, $history);
        // Most recent first — second addPoints call should be first in history
        $this->assertGreaterThanOrEqual(
            $history->last()->created_at,
            $history->first()->created_at
        );
    }
    #[Test]
    public function test_get_point_history_returns_empty_collection_for_new_user(): void
    {
        $history = $this->loyaltyService->getPointHistory($this->customer->id);
        $this->assertCount(0, $history);
    }
}
