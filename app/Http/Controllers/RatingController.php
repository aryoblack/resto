<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Rating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * RatingController — handles customer rating submission and admin rating summary.
 *
 * Routes:
 *   POST /api/customer/orders/{order}/rating  → store  (customer)
 *   GET  /api/admin/ratings                   → index  (admin)
 *
 * Validates: Requirements 16.1, 16.2, 16.3, 16.4
 */
class RatingController extends Controller
{
    // -------------------------------------------------------------------------
    // 14.1 / 14.2 / 14.3 — Customer submits a rating for an order
    // -------------------------------------------------------------------------

    /**
     * POST /api/customer/orders/{order}/rating
     *
     * Body params:
     *   rating  int     required, 1-5
     *   review  string  optional
     *
     * Rules:
     *   - Order must belong to the authenticated customer
     *   - One rating per order (idempotent — returns existing rating if already rated)
     *
     * Validates: Requirements 16.2, 16.3
     */
    public function store(Request $request, Order $order): JsonResponse
    {
        // Ensure the order belongs to the authenticated customer
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Pesanan tidak ditemukan atau bukan milik Anda.',
            ], 403);
        }

        // Idempotency check — Requirement 16.3
        $existing = Rating::where('order_id', $order->id)->first();
        if ($existing) {
            return response()->json([
                'message' => 'Pesanan ini sudah pernah diberi rating.',
                'data'    => $existing,
            ], 422);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        $rating = Rating::create([
            'order_id' => $order->id,
            'user_id'  => $request->user()->id,
            'rating'   => $validated['rating'],
            'review'   => $validated['review'] ?? null,
        ]);

        return response()->json([
            'message' => 'Rating berhasil disimpan.',
            'data'    => $rating,
        ], 201);
    }

    // -------------------------------------------------------------------------
    // 14.5 — Admin views rating summary and recent reviews
    // -------------------------------------------------------------------------

    /**
     * GET /api/admin/ratings
     *
     * Query params:
     *   per_page  int  optional, default 10
     *
     * Returns:
     *   - average_rating: float (rounded to 2 decimal places)
     *   - total_ratings:  int
     *   - reviews:        paginated list of recent ratings with order info and customer name
     *
     * Validates: Requirement 16.4
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $averageRating = Rating::avg('rating');
        $totalRatings  = Rating::count();

        $reviews = Rating::with([
                'order:id,order_status,created_at,order_type',
                'user:id,name,email',
            ])
            ->latest()
            ->latest('id')
            ->paginate($request->integer('per_page', 10));

        return response()->json([
            'message' => 'Ringkasan rating berhasil diambil.',
            'data'    => [
                'average_rating' => $averageRating !== null ? round((float) $averageRating, 2) : null,
                'total_ratings'  => $totalRatings,
                'reviews'        => $reviews,
            ],
        ]);
    }
}
