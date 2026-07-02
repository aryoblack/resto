<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ReservationController — HTTP layer for the Reservation_Manager module.
 *
 * Routes:
 *   POST   /api/customer/reservations                        → store
 *   GET    /api/customer/reservations                        → myReservations
 *   DELETE /api/customer/reservations/{reservation}          → cancel (customer)
 *   GET    /api/admin/reservations                           → index
 *   POST   /api/admin/reservations/{reservation}/confirm     → confirm
 *   POST   /api/admin/reservations/{reservation}/cancel      → cancel (admin)
 *   GET    /api/tables/{table}/availability                  → checkAvailability
 *
 * Validates: Requirements 14.1, 14.2, 14.3, 14.4, 14.5
 */
class ReservationController extends Controller
{
    public function __construct(private readonly ReservationService $reservationService)
    {
    }

    // -------------------------------------------------------------------------
    // 12.1 — store: Customer creates a reservation
    // -------------------------------------------------------------------------

    /**
     * Create a new reservation (customer).
     *
     * POST /api/customer/reservations
     *
     * Body:
     *   table_id          (required, integer, exists:table,id)
     *   date              (required, date, after_or_equal:today)
     *   time              (required, date_format:H:i)
     *   number_of_people  (required, integer, min:1)
     *   notes             (optional, string)
     *
     * Validates: Requirements 14.1, 14.2
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'table_id'         => ['required', 'integer', 'exists:table,id'],
            'date'             => ['required', 'date', 'after_or_equal:today'],
            'time'             => ['required', 'date_format:H:i'],
            'number_of_people' => ['required', 'integer', 'min:1'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        $reservation = $this->reservationService->createReservation(
            $validated,
            $request->user()->id,
        );

        return response()->json([
            'message' => 'Reservasi berhasil dibuat dan sedang menunggu konfirmasi.',
            'data'    => $this->formatReservation($reservation),
        ], 201);
    }

    // -------------------------------------------------------------------------
    // 12.1 — index: Admin lists all reservations with filters
    // -------------------------------------------------------------------------

    /**
     * List all reservations (admin) with optional filters.
     *
     * GET /api/admin/reservations
     *
     * Query params:
     *   - date     (Y-m-d)
     *   - status   (pending|confirmed|cancelled)
     *   - per_page (integer, default 10)
     *
     * Validates: Requirement 14.5
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['date', 'status', 'per_page', 'search']);

        $paginator = $this->reservationService->getReservations($filters);

        return response()->json([
            'data' => collect($paginator->items())->map(fn (Reservation $r) => $this->formatReservation($r)),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // myReservations: Customer lists own reservations
    // -------------------------------------------------------------------------

    /**
     * List the authenticated customer's own reservations.
     *
     * GET /api/customer/reservations
     */
    public function myReservations(Request $request): JsonResponse
    {
        $reservations = Reservation::with(['table'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'data' => $reservations->map(fn (Reservation $r) => $this->formatReservation($r)),
        ]);
    }

    // -------------------------------------------------------------------------
    // 12.1 — confirm: Admin confirms a reservation
    // -------------------------------------------------------------------------

    /**
     * Confirm a reservation (admin).
     *
     * POST /api/admin/reservations/{reservation}/confirm
     *
     * Validates: Requirement 14.3
     */
    public function confirm(Reservation $reservation): JsonResponse
    {
        $reservation = $this->reservationService->confirmReservation($reservation->id);

        return response()->json([
            'message' => 'Reservasi berhasil dikonfirmasi.',
            'data'    => $this->formatReservation($reservation),
        ]);
    }

    // -------------------------------------------------------------------------
    // 12.1 — cancel: Admin or customer cancels a reservation
    // -------------------------------------------------------------------------

    /**
     * Cancel a reservation.
     *
     * Admin:    POST   /api/admin/reservations/{reservation}/cancel
     * Customer: DELETE /api/customer/reservations/{reservation}
     *
     * Body (optional):
     *   reason  (string)
     *
     * Validates: Requirement 14.4
     */
    public function cancel(Request $request, Reservation $reservation): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        // Customers can only cancel their own reservations
        $user = $request->user();
        if ($user->hasRole('customer') && $reservation->user_id !== $user->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki izin untuk membatalkan reservasi ini.',
            ], 403);
        }

        $reservation = $this->reservationService->cancelReservation(
            $reservation->id,
            $validated['reason'] ?? '',
        );

        return response()->json([
            'message' => 'Reservasi berhasil dibatalkan.',
            'data'    => $this->formatReservation($reservation),
        ]);
    }

    // -------------------------------------------------------------------------
    // 12.5 — checkAvailability: Public endpoint
    // -------------------------------------------------------------------------

    /**
     * Check if a table is available for a given date and time.
     *
     * GET /api/tables/{table}/availability?date=Y-m-d&time=H:i
     *
     * Validates: Requirement 14.2
     */
    public function checkAvailability(Request $request, int $table): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
        ]);

        $available = $this->reservationService->checkAvailability(
            $table,
            $validated['date'],
            $validated['time'],
        );

        return response()->json([
            'data' => [
                'table_id'  => $table,
                'date'      => $validated['date'],
                'time'      => $validated['time'],
                'available' => $available,
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Format a reservation for API responses.
     *
     * @return array<string, mixed>
     */
    private function formatReservation(Reservation $reservation): array
    {
        return [
            'id'               => $reservation->id,
            'user_id'          => $reservation->user_id,
            'table_id'         => $reservation->table_id,
            'date'             => $reservation->date?->toDateString(),
            'time'             => $reservation->time,
            'number_of_people' => $reservation->number_of_people,
            'status'           => $reservation->status,
            'notes'            => $reservation->notes,
            'table'            => $reservation->relationLoaded('table') && $reservation->table ? [
                'id'           => $reservation->table->id,
                'table_number' => $reservation->table->table_number,
                'status'       => $reservation->table->status,
            ] : null,
            'user'             => $reservation->relationLoaded('user') && $reservation->user ? [
                'id'   => $reservation->user->id,
                'name' => $reservation->user->name,
            ] : null,
            'created_at'       => $reservation->created_at,
            'updated_at'       => $reservation->updated_at,
        ];
    }
}
