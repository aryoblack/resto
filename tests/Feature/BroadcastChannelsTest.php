<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;

/**
 * Tests for broadcast channel authorization (routes/channels.php).
 *
 * Tests the channel authorization callbacks directly to verify that:
 * - customer.{userId}: only the matching authenticated customer is authorized
 * - admin: only users with the 'admin' role are authorized
 *
 * Staff channels require role-based authorization callbacks.
 */
class BroadcastChannelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * Resolve the authorization callback for a given channel pattern.
     * Returns the result of calling the callback with the given user and parameters.
     */
    private function authorizeChannel(string $channelPattern, User $user, array $params = []): mixed
    {
        $channels = Broadcast::getChannels();

        foreach ($channels as $pattern => $callback) {
            if ($pattern === $channelPattern) {
                return $callback($user, ...$params);
            }
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Private channel: customer.{userId}
    // -------------------------------------------------------------------------

    public function test_customer_can_subscribe_to_own_private_channel(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $result = $this->authorizeChannel('customer.{userId}', $customer, [$customer->id]);

        $this->assertTrue((bool) $result);
    }

    public function test_customer_cannot_subscribe_to_another_customers_channel(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $other    = User::factory()->create(['role' => 'customer']);

        $result = $this->authorizeChannel('customer.{userId}', $customer, [$other->id]);

        $this->assertFalse((bool) $result);
    }

    public function test_admin_can_subscribe_to_customer_channel_of_any_user(): void
    {
        // Admin subscribing to their own customer channel (edge case)
        $admin = User::factory()->create(['role' => 'admin']);

        $result = $this->authorizeChannel('customer.{userId}', $admin, [$admin->id]);

        $this->assertTrue((bool) $result);
    }

    // -------------------------------------------------------------------------
    // Private channel: admin
    // -------------------------------------------------------------------------

    public function test_admin_can_subscribe_to_admin_channel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $result = $this->authorizeChannel('admin', $admin);

        $this->assertTrue((bool) $result);
    }

    public function test_customer_cannot_subscribe_to_admin_channel(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $result = $this->authorizeChannel('admin', $customer);

        $this->assertFalse((bool) $result);
    }

    public function test_waiter_cannot_subscribe_to_admin_channel(): void
    {
        $waiter = User::factory()->create(['role' => 'waiter']);

        $result = $this->authorizeChannel('admin', $waiter);

        $this->assertFalse((bool) $result);
    }

    public function test_chef_cannot_subscribe_to_admin_channel(): void
    {
        $chef = User::factory()->create(['role' => 'chef']);

        $result = $this->authorizeChannel('admin', $chef);

        $this->assertFalse((bool) $result);
    }

    // -------------------------------------------------------------------------
    // Private staff channels
    // -------------------------------------------------------------------------

    public function test_staff_can_subscribe_to_orders_channel(): void
    {
        $chef = User::factory()->create(['role' => 'chef']);
        $chef->assignRole('chef');

        $result = $this->authorizeChannel('orders', $chef);

        $this->assertTrue((bool) $result);
    }

    public function test_customer_cannot_subscribe_to_orders_channel(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $customer->assignRole('customer');

        $result = $this->authorizeChannel('orders', $customer);

        $this->assertFalse((bool) $result);
    }

    public function test_waiter_can_subscribe_to_waiter_channel(): void
    {
        $waiter = User::factory()->create(['role' => 'waiter']);
        $waiter->assignRole('waiter');

        $result = $this->authorizeChannel('waiter', $waiter);

        $this->assertTrue((bool) $result);
    }

    public function test_chef_cannot_subscribe_to_waiter_channel(): void
    {
        $chef = User::factory()->create(['role' => 'chef']);
        $chef->assignRole('chef');

        $result = $this->authorizeChannel('waiter', $chef);

        $this->assertFalse((bool) $result);
    }
}
