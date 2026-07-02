<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class SanctumConfigurationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that Sanctum is properly configured.
     */
    public function test_sanctum_configuration_is_loaded(): void
    {
        // Check that sanctum config is loaded
        $this->assertNotNull(config('sanctum.stateful'));
        $this->assertNotNull(config('sanctum.guard'));
        $this->assertNotNull(config('sanctum.expiration'));
        
        // Check that token expiration is set to 1440 minutes (24 hours)
        $this->assertEquals(1440, config('sanctum.expiration'));
        
        // Check that the guard includes 'web'
        $this->assertContains('web', config('sanctum.guard'));
    }

    /**
     * Test that API guard uses Sanctum driver.
     */
    public function test_api_guard_uses_sanctum(): void
    {
        $this->assertEquals('sanctum', config('auth.guards.api.driver'));
    }

    /**
     * Test that User model has HasApiTokens trait.
     */
    public function test_user_model_has_api_tokens_trait(): void
    {
        $user = new User();
        $this->assertTrue(method_exists($user, 'createToken'));
        $this->assertTrue(method_exists($user, 'tokens'));
    }

    /**
     * Test that a user can create a token.
     */
    public function test_user_can_create_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $token = $user->createToken('test-token');

        $this->assertNotNull($token);
        $this->assertNotNull($token->plainTextToken);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'test-token',
        ]);
    }

    /**
     * Test that authenticated requests work with Sanctum.
     */
    public function test_authenticated_request_with_sanctum(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'role' => 'customer',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200);
        $response->assertJson([
            'email' => 'test@example.com',
        ]);
    }

    /**
     * Test that unauthenticated requests are rejected.
     */
    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    /**
     * Test that stateful domains are configured for PWA.
     */
    public function test_stateful_domains_include_pwa_domains(): void
    {
        $statefulDomains = config('sanctum.stateful');
        
        // Check that localhost variants are included for development
        $this->assertContains('localhost', $statefulDomains);
        $this->assertContains('127.0.0.1', $statefulDomains);
        
        // Check that common development ports are included
        $domainString = implode(',', $statefulDomains);
        $this->assertStringContainsString('localhost:81', $domainString);
        $this->assertStringContainsString('localhost:5173', $domainString);
    }
}
