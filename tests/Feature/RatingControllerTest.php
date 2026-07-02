<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use App\Models\Order;
use App\Models\Rating;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for RatingController.
 *
 * Covers:
 *   - Customer can submit a rating (1-5) with optional review
 *   - Rating is rejected if out of range (< 1 or > 5)
 *   - Rating is rejected if order does not belong to the customer
 *   - Idempotency: second rating on same order is rejected and returns existing rating
 *   - Unauthenticated requests are rejected
 *   - Admin can view rating summary (average + recent reviews)
 *   - Admin index requires admin role
 *
 * Validates: Requirements 16.2, 16.3, 16.4
 */
class RatingControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $admin;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->customer = User::factory()->create(['role' => 'customer', 'poin' => 0]);
        $this->customer->assignRole('customer');

        $this->admin = User::factory()->create(['role' => 'admin', 'poin' => 0]);
        $this->admin->assignRole('admin');

        $this->order = Order::create([
            'user_id'         => $this->customer->id,
            'total_price'     => 50000,
            'discount_amount' => 0,
            'tax_amount'      => 0,
            'service_charge'  => 0,
            'payment_method'  => 'cash',
            'payment_status'  => 'paid',
            'order_status'    => 'Disajikan',
            'order_type'      => 'dine_in',
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function authHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer ' . $user->createToken('test')->plainTextToken];
    }

    // =========================================================================
    // store — customer submits rating
    // =========================================================================
    #[Test]
    public function test_customer_can_submit_rating_with_review(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson("/api/customer/orders/{$this->order->id}/rating", [
                'rating' => 5,
                'review' => 'Makanan enak sekali!',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.review', 'Makanan enak sekali!')
            ->assertJsonPath('data.order_id', $this->order->id)
            ->assertJsonPath('data.user_id', $this->customer->id);

        $this->assertDatabaseHas('rating', [
            'order_id' => $this->order->id,
            'user_id'  => $this->customer->id,
            'rating'   => 5,
            'review'   => 'Makanan enak sekali!',
        ]);
    }
    #[Test]
    public function test_customer_can_submit_rating_without_review(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson("/api/customer/orders/{$this->order->id}/rating", [
                'rating' => 3,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.rating', 3)
            ->assertJsonPath('data.review', null);

        $this->assertDatabaseHas('rating', [
            'order_id' => $this->order->id,
            'rating'   => 3,
            'review'   => null,
        ]);
    }
    #[Test]
    public function test_rating_validation_rejects_value_below_1(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson("/api/customer/orders/{$this->order->id}/rating", [
                'rating' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }
    #[Test]
    public function test_rating_validation_rejects_value_above_5(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson("/api/customer/orders/{$this->order->id}/rating", [
                'rating' => 6,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }
    #[Test]
    public function test_rating_validation_rejects_missing_rating(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson("/api/customer/orders/{$this->order->id}/rating", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }
    #[Test]
    public function test_rating_rejected_for_order_not_belonging_to_customer(): void
    {
        $otherCustomer = User::factory()->create(['role' => 'customer', 'poin' => 0]);
        $otherCustomer->assignRole('customer');

        $otherOrder = Order::create([
            'user_id'         => $otherCustomer->id,
            'total_price'     => 30000,
            'discount_amount' => 0,
            'tax_amount'      => 0,
            'service_charge'  => 0,
            'payment_method'  => 'cash',
            'payment_status'  => 'paid',
            'order_status'    => 'Disajikan',
            'order_type'      => 'dine_in',
        ]);

        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson("/api/customer/orders/{$otherOrder->id}/rating", [
                'rating' => 4,
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('rating', [
            'order_id' => $otherOrder->id,
            'user_id'  => $this->customer->id,
        ]);
    }
    #[Test]
    public function test_idempotency_second_rating_on_same_order_is_rejected(): void
    {
        // First rating
        Rating::create([
            'order_id' => $this->order->id,
            'user_id'  => $this->customer->id,
            'rating'   => 4,
            'review'   => 'Pertama',
        ]);

        // Second attempt
        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson("/api/customer/orders/{$this->order->id}/rating", [
                'rating' => 5,
                'review' => 'Kedua',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.rating', 4)
            ->assertJsonPath('data.review', 'Pertama');

        // Only one rating should exist
        $this->assertSame(1, Rating::where('order_id', $this->order->id)->count());
    }
    #[Test]
    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson("/api/customer/orders/{$this->order->id}/rating", [
            'rating' => 5,
        ]);

        $response->assertStatus(401);
    }
    #[Test]
    public function test_store_requires_customer_role(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->postJson("/api/customer/orders/{$this->order->id}/rating", [
                'rating' => 5,
            ]);

        $response->assertStatus(403);
    }

    // =========================================================================
    // index — admin views rating summary
    // =========================================================================
    #[Test]
    public function test_admin_can_view_rating_summary(): void
    {
        // Create some ratings
        Rating::create([
            'order_id' => $this->order->id,
            'user_id'  => $this->customer->id,
            'rating'   => 4,
            'review'   => 'Bagus',
        ]);

        $order2 = Order::create([
            'user_id'         => $this->customer->id,
            'total_price'     => 40000,
            'discount_amount' => 0,
            'tax_amount'      => 0,
            'service_charge'  => 0,
            'payment_method'  => 'cash',
            'payment_status'  => 'paid',
            'order_status'    => 'Disajikan',
            'order_type'      => 'dine_in',
        ]);

        Rating::create([
            'order_id' => $order2->id,
            'user_id'  => $this->customer->id,
            'rating'   => 2,
            'review'   => 'Kurang memuaskan',
        ]);

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->getJson('/api/admin/ratings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'average_rating',
                    'total_ratings',
                    'reviews' => ['data'],
                ],
            ])
            ->assertJsonPath('data.total_ratings', 2);

        // Average of 4 and 2 = 3
        $this->assertEquals(3, $response->json('data.average_rating'));
    }
    #[Test]
    public function test_admin_index_returns_null_average_when_no_ratings(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->getJson('/api/admin/ratings');

        $response->assertStatus(200)
            ->assertJsonPath('data.average_rating', null)
            ->assertJsonPath('data.total_ratings', 0);
    }
    #[Test]
    public function test_admin_index_reviews_include_order_and_customer_info(): void
    {
        Rating::create([
            'order_id' => $this->order->id,
            'user_id'  => $this->customer->id,
            'rating'   => 5,
            'review'   => 'Luar biasa!',
        ]);

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->getJson('/api/admin/ratings');

        $response->assertStatus(200);

        $review = $response->json('data.reviews.data.0');
        $this->assertArrayHasKey('order', $review);
        $this->assertArrayHasKey('user', $review);
        $this->assertSame($this->customer->name, $review['user']['name']);
    }
    #[Test]
    public function test_admin_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/admin/ratings');
        $response->assertStatus(401);
    }
    #[Test]
    public function test_admin_index_requires_admin_role(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->getJson('/api/admin/ratings');

        $response->assertStatus(403);
    }
    #[Test]
    public function test_admin_index_reviews_are_ordered_most_recent_first(): void
    {
        $earlier = now()->subMinutes(5);
        $later   = now();

        Rating::create([
            'order_id'   => $this->order->id,
            'user_id'    => $this->customer->id,
            'rating'     => 3,
            'review'     => 'Pertama',
            'created_at' => $earlier,
            'updated_at' => $earlier,
        ]);

        $order2 = Order::create([
            'user_id'         => $this->customer->id,
            'total_price'     => 40000,
            'discount_amount' => 0,
            'tax_amount'      => 0,
            'service_charge'  => 0,
            'payment_method'  => 'cash',
            'payment_status'  => 'paid',
            'order_status'    => 'Disajikan',
            'order_type'      => 'dine_in',
        ]);

        Rating::create([
            'order_id'   => $order2->id,
            'user_id'    => $this->customer->id,
            'rating'     => 5,
            'review'     => 'Kedua',
            'created_at' => $later,
            'updated_at' => $later,
        ]);

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->getJson('/api/admin/ratings');

        $response->assertStatus(200);

        $reviews = $response->json('data.reviews.data');
        // Most recent (Kedua) should be first
        $this->assertSame('Kedua', $reviews[0]['review']);
        $this->assertSame('Pertama', $reviews[1]['review']);
    }
}
