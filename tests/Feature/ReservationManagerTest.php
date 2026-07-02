<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use App\Events\ReservationUpdated;
use App\Mail\ReservationCancelled;
use App\Mail\ReservationConfirmed;
use App\Models\Reservation;
use App\Models\Table;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Feature tests for the Reservation_Manager module.
 *
 * Covers:
 *   - Customer can create reservation
 *   - Reservation creation fails when table is already booked (conflict)
 *   - Reservation creation fails with missing required fields
 *   - Admin can confirm reservation
 *   - Admin can cancel reservation
 *   - Customer can cancel own reservation
 *   - Customer cannot cancel another customer's reservation
 *   - Check availability returns true when no conflict
 *   - Check availability returns false when conflict exists
 *   - Admin can list all reservations
 *   - Admin can filter reservations by date
 *   - Admin can filter reservations by status
 *   - ReservationUpdated event is broadcast on confirm
 *   - ReservationUpdated event is broadcast on cancel
 *   - Email is sent on confirmation (Mail::fake())
 *   - Email is sent on cancellation (Mail::fake())
 *
 * Validates: Requirements 14.1, 14.2, 14.3, 14.4, 14.5
 */
class ReservationManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $otherCustomer;
    private User $admin;
    private Table $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->customer->assignRole('customer');

        $this->otherCustomer = User::factory()->create(['role' => 'customer']);
        $this->otherCustomer->assignRole('customer');

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->admin->assignRole('admin');

        $this->table = Table::create([
            'table_number' => 'T01',
            'qr_code'      => 'qr-test-t01',
            'status'       => 'available',
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function authHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer ' . $user->createToken('test')->plainTextToken];
    }

    private function createReservation(array $overrides = []): Reservation
    {
        return Reservation::create(array_merge([
            'user_id'          => $this->customer->id,
            'table_id'         => $this->table->id,
            'date'             => now()->addDays(3)->toDateString(),
            'time'             => '18:00:00',
            'number_of_people' => 2,
            'status'           => 'pending',
            'notes'            => null,
        ], $overrides));
    }

    // =========================================================================
    // 12.2 — Create Reservation
    // =========================================================================
    #[Test]
    public function test_customer_can_create_reservation(): void
    {
        $date = now()->addDays(5)->toDateString();

        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson('/api/customer/reservations', [
                'table_id'         => $this->table->id,
                'date'             => $date,
                'time'             => '19:00',
                'number_of_people' => 4,
                'notes'            => 'Meja dekat jendela',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.table_id', $this->table->id)
            ->assertJsonPath('data.number_of_people', 4)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'user_id', 'table_id', 'date', 'time', 'number_of_people', 'status', 'notes'],
            ]);

        $this->assertDatabaseHas('reservation', [
            'user_id'          => $this->customer->id,
            'table_id'         => $this->table->id,
            'date'             => $date,
            'status'           => 'pending',
            'number_of_people' => 4,
        ]);
    }
    #[Test]
    public function test_reservation_creation_fails_when_table_already_booked(): void
    {
        $date = now()->addDays(5)->toDateString();

        // Create an existing reservation
        $this->createReservation([
            'date'   => $date,
            'time'   => '19:00:00',
            'status' => 'pending',
        ]);

        // Try to book the same table at the same date/time
        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson('/api/customer/reservations', [
                'table_id'         => $this->table->id,
                'date'             => $date,
                'time'             => '19:00',
                'number_of_people' => 2,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['table_id']);
    }
    #[Test]
    public function test_reservation_creation_succeeds_when_existing_reservation_is_cancelled(): void
    {
        $date = now()->addDays(5)->toDateString();

        // Create a cancelled reservation — should NOT block new bookings
        $this->createReservation([
            'date'   => $date,
            'time'   => '19:00:00',
            'status' => 'cancelled',
        ]);

        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson('/api/customer/reservations', [
                'table_id'         => $this->table->id,
                'date'             => $date,
                'time'             => '19:00',
                'number_of_people' => 2,
            ]);

        $response->assertStatus(201);
    }
    #[Test]
    public function test_reservation_creation_fails_with_missing_required_fields(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson('/api/customer/reservations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['table_id', 'date', 'time', 'number_of_people']);
    }
    #[Test]
    public function test_reservation_creation_fails_with_past_date(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson('/api/customer/reservations', [
                'table_id'         => $this->table->id,
                'date'             => now()->subDays(1)->toDateString(),
                'time'             => '19:00',
                'number_of_people' => 2,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }
    #[Test]
    public function test_reservation_creation_requires_authentication(): void
    {
        $response = $this->postJson('/api/customer/reservations', [
            'table_id'         => $this->table->id,
            'date'             => now()->addDays(5)->toDateString(),
            'time'             => '19:00',
            'number_of_people' => 2,
        ]);

        $response->assertStatus(401);
    }

    // =========================================================================
    // 12.3 — Confirm Reservation (admin)
    // =========================================================================
    #[Test]
    public function test_admin_can_confirm_reservation(): void
    {
        Mail::fake();

        $reservation = $this->createReservation();

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->postJson("/api/admin/reservations/{$reservation->id}/confirm");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('reservation', [
            'id'     => $reservation->id,
            'status' => 'confirmed',
        ]);
    }
    #[Test]
    public function test_confirm_reservation_fails_when_already_confirmed(): void
    {
        $reservation = $this->createReservation(['status' => 'confirmed']);

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->postJson("/api/admin/reservations/{$reservation->id}/confirm");

        $response->assertStatus(422);
    }
    #[Test]
    public function test_confirm_reservation_requires_admin_role(): void
    {
        $reservation = $this->createReservation();

        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson("/api/admin/reservations/{$reservation->id}/confirm");

        $response->assertStatus(403);
    }

    // =========================================================================
    // 12.4 — Cancel Reservation (admin)
    // =========================================================================
    #[Test]
    public function test_admin_can_cancel_reservation(): void
    {
        Mail::fake();

        $reservation = $this->createReservation();

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->postJson("/api/admin/reservations/{$reservation->id}/cancel", [
                'reason' => 'Restoran tutup pada tanggal tersebut.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('reservation', [
            'id'     => $reservation->id,
            'status' => 'cancelled',
        ]);
    }
    #[Test]
    public function test_cancel_reservation_fails_when_already_cancelled(): void
    {
        $reservation = $this->createReservation(['status' => 'cancelled']);

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->postJson("/api/admin/reservations/{$reservation->id}/cancel");

        $response->assertStatus(422);
    }

    // =========================================================================
    // Customer cancel own reservation
    // =========================================================================
    #[Test]
    public function test_customer_can_cancel_own_reservation(): void
    {
        Mail::fake();

        $reservation = $this->createReservation(['user_id' => $this->customer->id]);

        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->deleteJson("/api/customer/reservations/{$reservation->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    }
    #[Test]
    public function test_customer_cannot_cancel_another_customers_reservation(): void
    {
        $reservation = $this->createReservation(['user_id' => $this->otherCustomer->id]);

        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->deleteJson("/api/customer/reservations/{$reservation->id}");

        $response->assertStatus(403);
    }

    // =========================================================================
    // 12.5 — Check Availability
    // =========================================================================
    #[Test]
    public function test_check_availability_returns_true_when_no_conflict(): void
    {
        $date = now()->addDays(7)->toDateString();

        $response = $this->getJson("/api/tables/{$this->table->id}/availability?date={$date}&time=20:00");

        $response->assertStatus(200)
            ->assertJsonPath('data.available', true)
            ->assertJsonPath('data.table_id', $this->table->id);
    }
    #[Test]
    public function test_check_availability_returns_false_when_conflict_exists(): void
    {
        $date = now()->addDays(7)->toDateString();

        // Create a confirmed reservation
        $this->createReservation([
            'date'   => $date,
            'time'   => '20:00:00',
            'status' => 'confirmed',
        ]);

        $response = $this->getJson("/api/tables/{$this->table->id}/availability?date={$date}&time=20:00");

        $response->assertStatus(200)
            ->assertJsonPath('data.available', false);
    }
    #[Test]
    public function test_check_availability_returns_true_when_only_cancelled_reservation_exists(): void
    {
        $date = now()->addDays(7)->toDateString();

        // Cancelled reservations should not block availability
        $this->createReservation([
            'date'   => $date,
            'time'   => '20:00:00',
            'status' => 'cancelled',
        ]);

        $response = $this->getJson("/api/tables/{$this->table->id}/availability?date={$date}&time=20:00");

        $response->assertStatus(200)
            ->assertJsonPath('data.available', true);
    }
    #[Test]
    public function test_check_availability_validates_required_params(): void
    {
        $response = $this->getJson("/api/tables/{$this->table->id}/availability");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date', 'time']);
    }

    // =========================================================================
    // 12.1 — Admin index with filters
    // =========================================================================
    #[Test]
    public function test_admin_can_list_all_reservations(): void
    {
        $this->createReservation(['date' => now()->addDays(1)->toDateString()]);
        $this->createReservation(['date' => now()->addDays(2)->toDateString()]);

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->getJson('/api/admin/reservations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertGreaterThanOrEqual(2, $response->json('meta.total'));
    }
    #[Test]
    public function test_admin_can_filter_reservations_by_date(): void
    {
        $targetDate = now()->addDays(10)->toDateString();
        $otherDate  = now()->addDays(11)->toDateString();

        $this->createReservation(['date' => $targetDate]);
        $this->createReservation(['date' => $otherDate]);

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->getJson("/api/admin/reservations?date={$targetDate}");

        $response->assertStatus(200);

        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertSame($targetDate, $item['date']);
        }
    }
    #[Test]
    public function test_admin_can_filter_reservations_by_status(): void
    {
        $this->createReservation(['status' => 'pending']);
        $this->createReservation(['status' => 'confirmed']);
        $this->createReservation(['status' => 'cancelled']);

        $response = $this->withHeaders($this->authHeaders($this->admin))
            ->getJson('/api/admin/reservations?status=pending');

        $response->assertStatus(200);

        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertSame('pending', $item['status']);
        }
    }
    #[Test]
    public function test_admin_reservations_index_requires_admin_role(): void
    {
        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->getJson('/api/admin/reservations');

        $response->assertStatus(403);
    }

    // =========================================================================
    // Customer list own reservations
    // =========================================================================
    #[Test]
    public function test_customer_can_list_own_reservations(): void
    {
        $this->createReservation(['user_id' => $this->customer->id]);
        $this->createReservation(['user_id' => $this->otherCustomer->id]);

        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->getJson('/api/customer/reservations');

        $response->assertStatus(200);

        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertSame($this->customer->id, $item['user_id']);
        }
    }

    // =========================================================================
    // 12.3 & 12.4 — ReservationUpdated event broadcast
    // =========================================================================
    #[Test]
    public function test_reservation_updated_event_is_broadcast_on_confirm(): void
    {
        Event::fake([ReservationUpdated::class]);
        Mail::fake();

        $reservation = $this->createReservation();

        $this->withHeaders($this->authHeaders($this->admin))
            ->postJson("/api/admin/reservations/{$reservation->id}/confirm");

        Event::assertDispatched(ReservationUpdated::class, function (ReservationUpdated $event) use ($reservation) {
            return $event->reservation->id === $reservation->id
                && $event->previousStatus === 'pending'
                && $event->newStatus === 'confirmed';
        });
    }
    #[Test]
    public function test_reservation_updated_event_is_broadcast_on_cancel(): void
    {
        Event::fake([ReservationUpdated::class]);
        Mail::fake();

        $reservation = $this->createReservation();

        $this->withHeaders($this->authHeaders($this->admin))
            ->postJson("/api/admin/reservations/{$reservation->id}/cancel");

        Event::assertDispatched(ReservationUpdated::class, function (ReservationUpdated $event) use ($reservation) {
            return $event->reservation->id === $reservation->id
                && $event->previousStatus === 'pending'
                && $event->newStatus === 'cancelled';
        });
    }

    // =========================================================================
    // 12.6 — Email notifications
    // =========================================================================
    #[Test]
    public function test_email_is_sent_on_confirmation(): void
    {
        Mail::fake();

        $reservation = $this->createReservation();

        $this->withHeaders($this->authHeaders($this->admin))
            ->postJson("/api/admin/reservations/{$reservation->id}/confirm");

        Mail::assertQueued(ReservationConfirmed::class, function (ReservationConfirmed $mail) use ($reservation) {
            return $mail->reservation->id === $reservation->id;
        });
    }
    #[Test]
    public function test_email_is_sent_on_cancellation(): void
    {
        Mail::fake();

        $reservation = $this->createReservation();

        $this->withHeaders($this->authHeaders($this->admin))
            ->postJson("/api/admin/reservations/{$reservation->id}/cancel", [
                'reason' => 'Alasan pembatalan.',
            ]);

        Mail::assertQueued(ReservationCancelled::class, function (ReservationCancelled $mail) use ($reservation) {
            return $mail->reservation->id === $reservation->id
                && $mail->reason === 'Alasan pembatalan.';
        });
    }
    #[Test]
    public function test_email_is_sent_when_customer_cancels_own_reservation(): void
    {
        Mail::fake();

        $reservation = $this->createReservation(['user_id' => $this->customer->id]);

        $this->withHeaders($this->authHeaders($this->customer))
            ->deleteJson("/api/customer/reservations/{$reservation->id}");

        Mail::assertQueued(ReservationCancelled::class, function (ReservationCancelled $mail) use ($reservation) {
            return $mail->reservation->id === $reservation->id;
        });
    }

    // =========================================================================
    // Conflict detection — confirmed reservation also blocks
    // =========================================================================
    #[Test]
    public function test_conflict_detected_for_confirmed_reservation(): void
    {
        $date = now()->addDays(5)->toDateString();

        $this->createReservation([
            'date'   => $date,
            'time'   => '19:00:00',
            'status' => 'confirmed',
        ]);

        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson('/api/customer/reservations', [
                'table_id'         => $this->table->id,
                'date'             => $date,
                'time'             => '19:00',
                'number_of_people' => 2,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['table_id']);
    }
    #[Test]
    public function test_different_table_does_not_conflict(): void
    {
        $date = now()->addDays(5)->toDateString();

        $anotherTable = Table::create([
            'table_number' => 'T02',
            'qr_code'      => 'qr-test-t02',
            'status'       => 'available',
        ]);

        // Book table T01
        $this->createReservation([
            'table_id' => $this->table->id,
            'date'     => $date,
            'time'     => '19:00:00',
            'status'   => 'pending',
        ]);

        // Book table T02 at the same time — should succeed
        $response = $this->withHeaders($this->authHeaders($this->customer))
            ->postJson('/api/customer/reservations', [
                'table_id'         => $anotherTable->id,
                'date'             => $date,
                'time'             => '19:00',
                'number_of_people' => 2,
            ]);

        $response->assertStatus(201);
    }
}
