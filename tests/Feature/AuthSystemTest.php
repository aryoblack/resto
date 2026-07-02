<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Feature tests for the Auth_System module.
 *
 * Covers: login, logout, registration, account lockout, and RBAC.
 * Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5, 1.7, 1.8
 */
class AuthSystemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed roles before each test so Spatie role assignment works.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // -------------------------------------------------------------------------
    // Login tests
    // -------------------------------------------------------------------------
    #[Test]
    public function test_login_with_valid_credentials_returns_token(): void
    {
        $user = User::factory()->create([
            'email'    => 'customer@example.com',
            'password' => Hash::make('password123'),
            'role'     => 'customer',
            'is_active' => true,
        ]);
        $user->assignRole('customer');

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'customer@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'access_token',
                'token_type',
                'user' => ['id', 'name', 'email', 'role'],
            ]);

        $this->assertNotEmpty($response->json('access_token'));
    }
    #[Test]
    public function test_login_with_invalid_credentials_returns_401(): void
    {
        User::factory()->create([
            'email'    => 'customer@example.com',
            'password' => Hash::make('correct_password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'customer@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401)
            ->assertJsonFragment(['message' => 'Kredensial tidak valid.']);
    }
    #[Test]
    public function test_login_with_unknown_email_returns_401(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email'    => 'nobody@example.com',
            'password' => 'anypassword',
        ]);

        $response->assertStatus(401);
    }
    #[Test]
    public function test_account_locks_after_5_failed_attempts(): void
    {
        $user = User::factory()->create([
            'email'    => 'lockme@example.com',
            'password' => Hash::make('correct_password'),
        ]);

        // Make 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email'    => 'lockme@example.com',
                'password' => 'wrong_password',
            ]);
        }

        $user->refresh();

        $this->assertEquals(5, $user->failed_login_attempts);
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->locked_until->isFuture());
    }
    #[Test]
    public function test_locked_account_cannot_login(): void
    {
        $user = User::factory()->create([
            'email'              => 'locked@example.com',
            'password'           => Hash::make('correct_password'),
            'failed_login_attempts' => 5,
            'locked_until'       => now()->addMinutes(15),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'locked@example.com',
            'password' => 'correct_password',
        ]);

        $response->assertStatus(423);
    }
    #[Test]
    public function test_inactive_account_cannot_login(): void
    {
        User::factory()->create([
            'email'     => 'inactive@example.com',
            'password'  => Hash::make('password123'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }
    #[Test]
    public function test_successful_login_resets_failed_attempts(): void
    {
        $user = User::factory()->create([
            'email'                 => 'retry@example.com',
            'password'              => Hash::make('correct_password'),
            'failed_login_attempts' => 3,
        ]);

        $this->postJson('/api/auth/login', [
            'email'    => 'retry@example.com',
            'password' => 'correct_password',
        ])->assertStatus(200);

        $user->refresh();
        $this->assertEquals(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
    }

    // -------------------------------------------------------------------------
    // Logout tests
    // -------------------------------------------------------------------------
    #[Test]
    public function test_logout_invalidates_token(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('customer');

        // Create a token directly
        $tokenResult = $user->createToken('auth_token');
        $plainToken  = $tokenResult->plainTextToken;

        // Verify token exists in DB
        $this->assertDatabaseCount('personal_access_tokens', 1);

        // Logout using the token
        $this->withHeader('Authorization', "Bearer {$plainToken}")
            ->postJson('/api/auth/logout')
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Logout berhasil.']);

        // Token must be deleted from the database
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // A second user with no session should get 401 when using the deleted token
        $otherUser = User::factory()->create();
        // Use actingAs to ensure no session bleed, then verify the deleted token is gone
        $this->assertNull(\Laravel\Sanctum\PersonalAccessToken::findToken(explode('|', $plainToken)[1] ?? $plainToken));
    }

    // -------------------------------------------------------------------------
    // Registration tests
    // -------------------------------------------------------------------------
    #[Test]
    public function test_register_creates_customer_account(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'New Customer',
            'email'                 => 'newcustomer@example.com',
            'phone'                 => '081234567890',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'access_token',
                'token_type',
                'user' => ['id', 'name', 'email', 'phone', 'role'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newcustomer@example.com',
            'phone' => '081234567890',
            'role'  => 'customer',
        ]);

        // Verify Spatie role was assigned
        $user = User::where('email', 'newcustomer@example.com')->first();
        $this->assertTrue($user->hasRole('customer'));
    }
    #[Test]
    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Another User',
            'email'                 => 'existing@example.com',
            'phone'                 => '081234567891',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
    #[Test]
    public function test_register_rejects_password_shorter_than_8_chars(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Short Pass',
            'email'                 => 'shortpass@example.com',
            'phone'                 => '081234567892',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
    #[Test]
    public function test_register_rejects_mismatched_password_confirmation(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Mismatch User',
            'email'                 => 'mismatch@example.com',
            'phone'                 => '081234567893',
            'password'              => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    // -------------------------------------------------------------------------
    // RBAC tests
    // -------------------------------------------------------------------------
    #[Test]
    public function test_customer_cannot_access_admin_routes(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $customer->assignRole('customer');

        $token = $customer->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/staff', [
                'name'  => 'New Staff',
                'email' => 'staff@example.com',
                'role'  => 'waiter',
            ])
            ->assertStatus(403);
    }
    #[Test]
    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $token = $admin->createToken('test')->plainTextToken;

        // The endpoint itself may fail due to mail, but access should be granted (not 403)
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/staff', [
                'name'  => 'New Waiter',
                'email' => 'waiter@example.com',
                'role'  => 'waiter',
            ]);

        // Should not be 403 (forbidden) — admin has access
        $this->assertNotEquals(403, $response->status());
    }
    #[Test]
    public function test_waiter_cannot_access_admin_routes(): void
    {
        $waiter = User::factory()->create(['role' => 'waiter']);
        $waiter->assignRole('waiter');

        $token = $waiter->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/staff', [
                'name'  => 'New Staff',
                'email' => 'staff2@example.com',
                'role'  => 'chef',
            ])
            ->assertStatus(403);
    }
    #[Test]
    public function test_chef_can_access_kds_route(): void
    {
        $chef = User::factory()->create(['role' => 'chef']);
        $chef->assignRole('chef');

        $token = $chef->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/staff/kds')
            ->assertStatus(200);
    }
    #[Test]
    public function test_customer_cannot_access_kds_route(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $customer->assignRole('customer');

        $token = $customer->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/staff/kds')
            ->assertStatus(403);
    }
    #[Test]
    public function test_waiter_can_access_staff_orders_route(): void
    {
        $waiter = User::factory()->create(['role' => 'waiter']);
        $waiter->assignRole('waiter');

        $token = $waiter->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/staff/orders')
            ->assertStatus(200);
    }
    #[Test]
    public function test_customer_cannot_access_staff_orders_route(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $customer->assignRole('customer');

        $token = $customer->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/staff/orders')
            ->assertStatus(403);
    }
}
