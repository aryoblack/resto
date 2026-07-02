<?php

namespace App\Services;

use App\Events\ReservationUpdated;
use App\Mail\ReservationCancelled;
use App\Mail\ReservationConfirmed;
use App\Models\Reservation;
use App\Models\Table;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * ReservationService — Core business logic for the Reservation_Manager module.
 *
 * Responsibilities:
 *  - Create reservations with conflict detection
 *  - Confirm reservations (admin) and notify customer
 *  - Cancel reservations (admin or customer) and notify customer
 *  - Check table availability for a given date and time
 *  - List reservations with optional filters
 *
 * Validates: Requirements 14.1, 14.2, 14.3, 14.4, 14.5
 */
class ReservationService
{
    // -------------------------------------------------------------------------
    // Create Reservation
    // -------------------------------------------------------------------------

    /**
     * Create a new reservation for a customer.
     *
     * Steps:
     *  1. Check for conflicts (same table_id + date + time, status != cancelled)
     *  2. If conflict → throw ValidationException with alternative suggestions
     *  3. Create reservation with status `pending`
     *  4. Send confirmation email to customer
     *
     * @param  array<string, mixed>  $data    Validated reservation data
     * @param  int                   $userId  Authenticated customer ID
     * @return Reservation
     *
     * @throws ValidationException  when a conflicting reservation exists
     *
     * Validates: Requirements 14.1, 14.2
     */
    public function createReservation(array $data, int $userId): Reservation
    {
        // --- 1. Check for conflicts ---
        $conflict = Reservation::where('table_id', $data['table_id'])
            ->where('date', $data['date'])
            ->where('time', $data['time'])
            ->whereNotIn('status', ['cancelled'])
            ->exists();

        if ($conflict) {
            // --- 2. Suggest alternatives ---
            $alternatives = $this->suggestAlternatives(
                (int) $data['table_id'],
                $data['date'],
                $data['time'],
            );

            throw ValidationException::withMessages([
                'table_id' => 'Meja sudah dipesan pada tanggal dan waktu tersebut. Silakan pilih meja atau waktu lain.',
                'alternatives' => $alternatives,
            ]);
        }

        // --- 3. Create reservation ---
        $reservation = Reservation::create([
            'user_id'          => $userId,
            'table_id'         => $data['table_id'],
            'date'             => $data['date'],
            'time'             => $data['time'],
            'number_of_people' => $data['number_of_people'],
            'status'           => 'pending',
            'notes'            => $data['notes'] ?? null,
        ]);

        $reservation->load(['user', 'table']);

        return $reservation;
    }

    // -------------------------------------------------------------------------
    // Confirm Reservation (admin)
    // -------------------------------------------------------------------------

    /**
     * Confirm a pending reservation.
     *
     * Steps:
     *  1. Update status to `confirmed`
     *  2. Broadcast ReservationUpdated event
     *  3. Send email notification to customer
     *
     * @param  int  $reservationId
     * @return Reservation
     *
     * @throws ValidationException  when reservation is not in `pending` status
     *
     * Validates: Requirement 14.3
     */
    public function confirmReservation(int $reservationId): Reservation
    {
        $reservation = Reservation::with(['user', 'table'])->findOrFail($reservationId);

        if ($reservation->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => "Reservasi tidak dapat dikonfirmasi karena statusnya adalah '{$reservation->status}'.",
            ]);
        }

        $previousStatus = $reservation->status;

        $reservation->update(['status' => 'confirmed']);
        $reservation->refresh();

        $this->broadcastReservationUpdated($reservation, $previousStatus, 'confirmed');

        // Send email notification
        if ($reservation->user && $reservation->user->email) {
            Mail::to($reservation->user->email)
                ->queue(new ReservationConfirmed($reservation));
        }

        return $reservation;
    }

    // -------------------------------------------------------------------------
    // Cancel Reservation (admin or customer)
    // -------------------------------------------------------------------------

    /**
     * Cancel a reservation.
     *
     * Steps:
     *  1. Update status to `cancelled`
     *  2. Broadcast ReservationUpdated event
     *  3. Send email notification to customer
     *
     * @param  int     $reservationId
     * @param  string  $reason         Optional cancellation reason
     * @return Reservation
     *
     * @throws ValidationException  when reservation is already cancelled
     *
     * Validates: Requirement 14.4
     */
    public function cancelReservation(int $reservationId, string $reason = ''): Reservation
    {
        $reservation = Reservation::with(['user', 'table'])->findOrFail($reservationId);

        if ($reservation->status === 'cancelled') {
            throw ValidationException::withMessages([
                'status' => 'Reservasi sudah dibatalkan sebelumnya.',
            ]);
        }

        $previousStatus = $reservation->status;

        $reservation->update(['status' => 'cancelled']);
        $reservation->refresh();

        $this->broadcastReservationUpdated($reservation, $previousStatus, 'cancelled');

        // Send email notification
        if ($reservation->user && $reservation->user->email) {
            Mail::to($reservation->user->email)
                ->queue(new ReservationCancelled($reservation, $reason));
        }

        return $reservation;
    }

    // -------------------------------------------------------------------------
    // Check Availability
    // -------------------------------------------------------------------------

    /**
     * Check if a table is available for a given date and time.
     *
     * Returns true if no conflicting (non-cancelled) reservation exists.
     *
     * @param  int     $tableId
     * @param  string  $date    Format: Y-m-d
     * @param  string  $time    Format: H:i or H:i:s
     * @return bool
     *
     * Validates: Requirement 14.2
     */
    public function checkAvailability(int $tableId, string $date, string $time): bool
    {
        return ! Reservation::where('table_id', $tableId)
            ->where('date', $date)
            ->where('time', $time)
            ->whereNotIn('status', ['cancelled'])
            ->exists();
    }

    // -------------------------------------------------------------------------
    // Get Reservations (admin)
    // -------------------------------------------------------------------------

    /**
     * Get reservations with optional filters.
     *
     * @param  array<string, mixed>  $filters  Supported keys: date, status, per_page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * Validates: Requirement 14.5
     */
    public function getReservations(array $filters = [])
    {
        $query = Reservation::with(['user', 'table'])->latest();

        if (! empty($filters['date'])) {
            $query->where('date', $filters['date']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search);
                }

                $q->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('table', fn ($table) => $table->where('table_number', 'like', "%{$search}%"));
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 10);

        return $query->paginate($perPage);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Suggest alternative tables or times when a conflict is detected.
     *
     * Returns up to 3 available tables at the same date/time,
     * or the same table at nearby times (+1h, +2h).
     *
     * @param  int     $requestedTableId
     * @param  string  $date
     * @param  string  $time
     * @return array<string, mixed>
     */
    private function suggestAlternatives(int $requestedTableId, string $date, string $time): array
    {
        // Alternative tables at the same date/time
        $bookedTableIds = Reservation::where('date', $date)
            ->where('time', $time)
            ->whereNotIn('status', ['cancelled'])
            ->pluck('table_id')
            ->toArray();

        $alternativeTables = Table::whereNotIn('id', $bookedTableIds)
            ->where('status', 'available')
            ->limit(3)
            ->get(['id', 'table_number'])
            ->map(fn (Table $t) => [
                'table_id'     => $t->id,
                'table_number' => $t->table_number,
            ])
            ->values()
            ->toArray();

        // Alternative times for the same table (+1h, +2h)
        $baseTime = \Carbon\Carbon::createFromFormat('H:i:s', strlen($time) === 5 ? $time . ':00' : $time);
        $alternativeTimes = [];

        foreach ([1, 2] as $hoursOffset) {
            $candidateTime = $baseTime->copy()->addHours($hoursOffset)->format('H:i:s');
            $available = $this->checkAvailability($requestedTableId, $date, $candidateTime);

            if ($available) {
                $alternativeTimes[] = substr($candidateTime, 0, 5); // H:i format
            }
        }

        return [
            'alternative_tables' => $alternativeTables,
            'alternative_times'  => $alternativeTimes,
        ];
    }

    private function broadcastReservationUpdated(Reservation $reservation, string $previousStatus, string $newStatus): void
    {
        try {
            broadcast(new ReservationUpdated($reservation, $previousStatus, $newStatus));
        } catch (Throwable $e) {
            Log::warning('Reservation realtime broadcast failed.', [
                'reservation_id' => $reservation->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
