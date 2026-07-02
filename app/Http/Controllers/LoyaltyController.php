<?php

namespace App\Http\Controllers;

use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * LoyaltyController — HTTP layer for the Loyalty_Engine module.
 *
 * Endpoints (all require auth:sanctum + check.role:customer):
 *   GET  /api/customer/loyalty/balance  → balance()
 *   GET  /api/customer/loyalty/history  → history()
 *   POST /api/customer/loyalty/redeem   → redeem()
 *
 * Validates: Requirements 13.2, 13.3, 13.4, 13.5
 */
class LoyaltyController extends Controller
{
    public function __construct(
        private readonly LoyaltyService $loyaltyService,
    ) {}

    // -------------------------------------------------------------------------
    // GET /api/customer/loyalty/balance
    // -------------------------------------------------------------------------

    /**
     * Return the authenticated customer's current point balance.
     *
     * Validates: Requirement 13.2
     */
    public function balance(Request $request): JsonResponse
    {
        $userId  = $request->user()->id;
        $balance = $this->loyaltyService->getPointBalance($userId);

        return response()->json([
            'message' => 'Saldo poin berhasil diambil.',
            'data'    => [
                'user_id' => $userId,
                'balance' => $balance,
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/customer/loyalty/history
    // -------------------------------------------------------------------------

    /**
     * Return the authenticated customer's point transaction history.
     *
     * Validates: Requirement 13.5
     */
    public function history(Request $request): JsonResponse
    {
        $userId      = $request->user()->id;
        $transactions = $this->loyaltyService->getPointHistory($userId);

        return response()->json([
            'message' => 'Riwayat poin berhasil diambil.',
            'data'    => $transactions,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/customer/loyalty/redeem
    // -------------------------------------------------------------------------

    /**
     * Redeem points as a discount.
     *
     * Request body:
     *   points_to_redeem: int (required, min:1)
     *   order_id: int (optional)
     *
     * Validates: Requirements 13.3, 13.4
     */
    public function redeem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'points_to_redeem' => ['required', 'integer', 'min:1'],
            'order_id'         => ['nullable', 'integer', 'exists:order,id'],
        ]);

        $userId  = $request->user()->id;
        $result  = $this->loyaltyService->redeemPoints(
            userId:         $userId,
            pointsToRedeem: $validated['points_to_redeem'],
            orderId:        $validated['order_id'] ?? null,
        );

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'],
                'data'    => [
                    'available_balance' => $result['available_balance'] ?? $result['new_balance'],
                ],
            ], 422);
        }

        return response()->json([
            'message' => $result['message'],
            'data'    => [
                'discount_amount' => $result['discount_amount'],
                'new_balance'     => $result['new_balance'],
            ],
        ]);
    }
}
