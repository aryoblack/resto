<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use App\Models\Order;
use App\Models\Table;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the QR_Scanner module (Task 5).
 *
 * Covers:
 *   - Admin CRUD for tables (5.1)
 *   - Unique QR code generation on table creation (5.2, 5.3)
 *   - QR code regeneration — old token invalidated (5.4)
 *   - GET /scan/{qrCode} endpoint — valid redirect and invalid error (5.5)
 *   - Automatic table status update based on scan and order completion (5.6)
 *   - Manual table status change by admin (5.7)
 *
 * Validates: Requirements 2.1, 2.2, 2.4, 2.5, 10.1, 10.2, 10.3, 10.4, 10.5
 */
class QRScannerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->admin->assignRole('admin');
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function adminToken(): string
    {
        return $this->admin->createToken('test')->plainTextToken;
    }

    // -------------------------------------------------------------------------
    // 5.1 — Admin CRUD: list tables
    // -------------------------------------------------------------------------
    #[Test]
    public function test_admin_can_list_tables(): void
    {
        Table::create([
            'table_number' => 'T01',
            'qr_code'      => hash('sha256', 'token-1'),
            'status'       => 'available',
        ]);

        Table::create([
            'table_number' => 'T02',
            'qr_code'      => hash('sha256', 'token-2'),
            'status'       => 'occupied',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken())
            ->getJson('/api/admin/tables');

        $response->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonCount(2, 'data');
    }
    #[Test]
    public function test_admin_can_view_single_table(): void
    {
        $table = Table::create([
            'table_number' => 'T01',
            'qr_code'      => hash('sha256', 'token-single'),
            'status'       => 'available',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken())
            ->getJson("/api/admin/tables/{$table->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.table_number', 'T01')
            ->assertJsonPath('data.status', 'available');
    }

    // -------------------------------------------------------------------------
    // 5.1 + 5.2 + 5.3 — Create table with unique QR code
    // -------------------------------------------------------------------------

    /**
     * Validates: Requirement 10.2 — admin creates table → unique QR code generated.
     */
    #[Test]
    public function test_admin_can_create_table_and_qr_code_is_generated(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken())
            ->postJson('/api/admin/tables', [
                'table_number' => 'T01',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['message' => 'Meja berhasil dibuat.'])
            ->assertJsonPath('data.table_number', 'T01')
            ->assertJsonPath('data.status', 'available');

        $this->assertDatabaseHas('table', ['table_number' => 'T01']);

        // QR code must be stored and non-empty
        $table = Table::where('table_number', 'T01')->first();
        $this->assertNotNull($table->qr_code);
        $this->assertNotEmpty($table->qr_code);
    }

    /**
     * Validates: Requirement 2.4 — QR code is unique per table.
     */
    #[Test]
    public function test_each_table_gets_a_unique_qr_code(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->adminToken())
            ->postJson('/api/admin/tables', ['table_number' => 'T01'])
            ->assertStatus(201);

        $this->withHeader('Authorization', 'Bearer ' . $this->adminToken())
            ->postJson('/api/admin/tables', ['table_number' => 'T02'])
            ->assertStatus(201);

        $this->withHeader('Authorization', 'Bearer ' . $this->adminToken())
            ->postJson('/api/admin/tables', ['table_number' => 'T03'])
            ->assertStatus(201);

        $tokens = Table::pluck('qr_code')->toArray();

        // All tokens must be unique
        $this->assertCount(count($tokens), array_unique($tokens));
    }

    /**
     * Validates: Requirement 2.3 — QR scan URL contains table_id.
     */
    #[Test]
    public function test_qr_scan_url_contains_table_id(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken())
            ->postJson('/api/admin/tables', ['table_number' => 'T01'])
            ->assertStatus(201);

        $tableId    = $response->json('data.id');
        $qrCode     = $response->json('data.qr_code');
        $qrScanUrl  = $response->json('data.qr_scan_url');

        // The scan URL must contain the QR token
        $this->assertStringContainsString($qrCode, $qrScanUrl);
        $this->assertStringContainsString('/scan/', $qrScanUrl);
    }
    #[Test]
    public function test_create_table_fails_without_table_number(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken())
            ->postJson('/api/admin/tables', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['table_number']);
    }
    #[Test]
    public function test_create_table_fails_with_duplicate_table_number(): void
    {
        Table::create([
            'table_number' => 'T01',
            'qr_code'      => hash('sha256', 'existing-token'),
            'status'       => 'available',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken())
            ->postJson('/api/admin/tables', ['table_number' => 'T01']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['table_number']);
    }

    // -------------------------------------------------------------------------
    // 5.4 — QR code regeneration
    // -------------------------------------------------------------------------

    /**
     * Validates: Requirement 2.5 — admin updates table → QR code regenerated, old one invalid.
     */
    #[Test]
    public function test_updating_table_regenerates_qr_code(): void
    {
        $table = Table::create([
            'table_number' => 'T01',
            'qr_code'      => hash('sha256', 'old-token'),
            'status'       => 'available',
        ]);

        $oldToken = $table->qr_code;

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken())
            ->putJson("/api/admin/tables/{$table->id}", [
                'table_number' => 'T01-Updated',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Meja berhasil diperbarui. QR code baru telah dibuat.']);

        $table->refresh();

        // New token must differ from old token
        $this->assertNotEquals($oldToken, $table->qr_code);

        // Old token must no longer exist in the database
        $this->assertDatabaseMissing('table', ['qr_code' => $oldToken]);
    }

    /**
     * Validates: Requirement 2.5 — explicit regenerate endpoint creates new token.
     */
    #[Test]
    public function test_regenerate_qr_endpoint_creates_new_unique_token(): void
    {
        $table = Table::create([
            'table_number' => 'T01',
            'qr_code'      => hash('sha256', 'original-token'),
            'status'       => 'available',
        ]);

        $oldToken = $table->qr_code;

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken())
            ->postJson("/api/admin/tables/{$table->id}/regenerate-qr");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'QR code berhasil diregenerasi. QR code lama tidak lagi valid.'])
            ->assertJsonPath('old_token', $oldToken);

        $table->refresh();
        $this->assertNotEquals($oldToken, $table->qr_code);
    }

    /**
     * Validates: Requirement 2.5 — old QR token is no longer valid after regeneration.
     */
    #[Test]
    public function test_old_qr_token_is_invalid_after_regeneration(): void
    {
        $table = Table::create([
            'table_number' => 'T01',
            'qr_code'      => hash('sha256', 'old-scan-token'),
            'status'       => 'available',
        ]);

        $oldToken = $table->qr_code;

        // Regenerate
        $this->withHeader('Authorization', 'Bearer ' . $this->adminToken())
            ->postJson("/api/admin/tables/{$table->id}/regenerate-qr")
            ->assertStatus(200);

        // Scanning the old token must return 404
        $this->get("/scan/{$oldToken}")
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // 5.5 — GET /scan/{qrCode} endpoint
    // -------------------------------------------------------------------------

    /**
     * Validates: Requirement 2.1 — valid QR scan redirects to Customer_App.
     */
    #[Test]
    public function test_valid_qr_scan_redirects_to_customer_app(): void
    {
        $token = hash('sha256', 'valid-scan-token');

        $table = Table::create([
            'table_number' => 'T01',
            'qr_code'      => $token,
            'status'       => 'available',
        ]);

        $response = $this->get("/scan/{$token}");

        // Should redirect (302) to the customer app URL with table_id
        $response->assertRedirect();

        $location = $response->headers->get('Location');
        $this->assertStringContainsString('table_id=' . $table->id, $location);
    }

    /**
     * Validates: Requirement 2.2 — invalid QR code returns error message.
     */
    #[Test]
    public function test_invalid_qr_scan_returns_404_with_error_message(): void
    {
        $response = $this->getJson('/scan/invalid-nonexistent-token-xyz');

        $response->assertStatus(404)
            ->assertJsonFragment([
                'message' => 'QR code tidak valid. Silakan pindai ulang atau hubungi pelayan.',
            ]);
    }

    // -------------------------------------------------------------------------
    // 5.6 — Automatic status update on scan
    // -------------------------------------------------------------------------

    /**
     * Validates: Requirement 10.4 — scanning QR code sets table status to occupied.
     */
    #[Test]
    public function test_scanning_qr_code_sets_table_status_to_occupied(): void
    {
        $token = hash('sha256', 'scan-occupied-token');

        $table = Table::create([
            'table_number' => 'T01',
            'qr_code'      => $token,
            'status'       => 'available',
        ]);

        $this->get("/scan/{$token}");

        $table->refresh();
        $this->assertEquals('occupied', $table->status);
    }

    /**
     * Validates: Requirement 10.3 — table becomes available when all orders are Disajikan + paid.
     */
    #[Test]
    public function test_table_becomes_available_when_all_orders_complete(): void
    {
        $table = Table::create([
            'table_number' => 'T01',
            'qr_code'      => hash('sha256', 'auto-release-token'),
            'status'       => 'occupied',
        ]);

        // Create a completed + paid order
        Order::create([
            'table_id'       => $table->id,
            'total_price'    => 50000,
            'order_status'   => 'Disajikan',
            'payment_status' => 'paid',
            'order_type'     => 'dine_in',
        ]);

        $released = $table->releaseIfAllOrdersComplete();

        $this->assertTrue($released);
        $table->refresh();
        $this->assertEquals('available', $table->status);
    }

    /**
     * Validates: Requirement 10.3 — table stays occupied if any order is not yet complete.
     */
    #[Test]
    public function test_table_stays_occupied_when_some_orders_incomplete(): void
    {
        $table = Table::create([
            'table_number' => 'T02',
            'qr_code'      => hash('sha256', 'partial-complete-token'),
            'status'       => 'occupied',
        ]);

        // One completed order
        Order::create([
            'table_id'       => $table->id,
            'total_price'    => 30000,
            'order_status'   => 'Disajikan',
            'payment_status' => 'paid',
            'order_type'     => 'dine_in',
        ]);

        // One still-in-progress order
        Order::create([
            'table_id'       => $table->id,
            'total_price'    => 20000,
            'order_status'   => 'Dimasak',
            'payment_status' => 'pending',
            'order_type'     => 'dine_in',
        ]);

        $released = $table->releaseIfAllOrdersComplete();

        $this->assertFalse($released);
        $table->refresh();
        $this->assertEquals('occupied', $table->status);
    }

    /**
     * Validates: Requirement 10.3 — cancelled orders are excluded from completion check.
     */
    #[Test]
    public function test_cancelled_orders_are_excluded_from_completion_check(): void
    {
        $table = Table::create([
            'table_number' => 'T03',
            'qr_code'      => hash('sha256', 'cancelled-order-token'),
            'status'       => 'occupied',
        ]);

        // One served + paid order
        Order::create([
            'table_id'       => $table->id,
            'total_price'    => 40000,
            'order_status'   => 'Disajikan',
            'payment_status' => 'paid',
            'order_type'     => 'dine_in',
        ]);

        // One cancelled order (should not block release)
        Order::create([
            'table_id'       => $table->id,
            'total_price'    => 15000,
            'order_status'   => 'Dibatalkan',
            'payment_status' => 'failed',
            'order_type'     => 'dine_in',
        ]);

        $released = $table->releaseIfAllOrdersComplete();

        $this->assertTrue($released);
        $table->refresh();
        $this->assertEquals('available', $table->status);
    }

    // -------------------------------------------------------------------------
    // 5.7 — Manual status change by admin
    // -------------------------------------------------------------------------

    /**
     * Validates: Requirement 10.5 — admin can manually change table status.
     */
    #[Test]
    public function test_admin_can_manually_set_table_status_to_occupied(): void
    {
        $table = Table::create([
            'table_number' => 'T01',
            'qr_code'      => hash('sha256', 'manual-status-token'),
            'status'       => 'available',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken())
            ->patchJson("/api/admin/tables/{$table->id}/status", [
                'status' => 'occupied',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'occupied');

        $this->assertDatabaseHas('table', ['id' => $table->id, 'status' => 'occupied']);
    }
    #[Test]
    public function test_admin_can_manually_set_table_status_to_available(): void
    {
        $table = Table::create([
            'table_number' => 'T01',
            'qr_code'      => hash('sha256', 'manual-available-token'),
            'status'       => 'occupied',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken())
            ->patchJson("/api/admin/tables/{$table->id}/status", [
                'status' => 'available',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'available');
    }
    #[Test]
    public function test_admin_cannot_set_invalid_status(): void
    {
        $table = Table::create([
            'table_number' => 'T01',
            'qr_code'      => hash('sha256', 'invalid-status-token'),
            'status'       => 'available',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken())
            ->patchJson("/api/admin/tables/{$table->id}/status", [
                'status' => 'broken',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    // -------------------------------------------------------------------------
    // Authorization checks
    // -------------------------------------------------------------------------
    #[Test]
    public function test_non_admin_cannot_access_table_management(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $customer->assignRole('customer');
        $token = $customer->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/tables')
            ->assertStatus(403);
    }
    #[Test]
    public function test_unauthenticated_user_cannot_access_table_management(): void
    {
        $this->getJson('/api/admin/tables')
            ->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // 5.1 — Delete table
    // -------------------------------------------------------------------------
    #[Test]
    public function test_admin_can_delete_table(): void
    {
        $table = Table::create([
            'table_number' => 'T99',
            'qr_code'      => hash('sha256', 'delete-token'),
            'status'       => 'available',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken())
            ->deleteJson("/api/admin/tables/{$table->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Meja berhasil dihapus.']);

        $this->assertDatabaseMissing('table', ['id' => $table->id]);
    }
}