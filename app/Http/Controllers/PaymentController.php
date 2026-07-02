<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * PaymentController — HTTP layer for the Payment_Gateway module.
 *
 * Endpoints:
 *   POST /api/customer/orders/{order}/payment/initiate  → initiate()
 *   POST /api/payment/webhook                           → webhook()
 *   POST /api/staff/orders/{order}/payment/confirm-cash → confirmCash()
 *   GET  /api/admin/payments                            → history()
 *
 * Validates: Requirements 6.1, 6.2, 6.3, 6.4, 6.5, 6.6
 */
class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    // -------------------------------------------------------------------------
    // 7.1 — initiate
    // -------------------------------------------------------------------------

    /**
     * Initiate a payment for an order.
     *
     * Request body:
     *   - payment_method: 'cash' | 'qris' | 'card'  (required)
     *
     * Returns payment instruction (snap_token, redirect_url, qris_url, or cash total).
     *
     * Validates: Requirements 6.1, 6.2, 6.5
     */
    public function initiate(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'payment_method' => ['required', 'in:cash,qris,card'],
        ]);

        $user = $request->user();

        if (! $user || ! $user->hasRole('customer') || $order->user_id !== $user->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        // Prevent re-initiating a paid order
        if ($order->payment_status === 'paid') {
            return response()->json([
                'message' => 'Pesanan ini sudah dibayar.',
            ], 422);
        }

        $instruction = $this->paymentService->initiatePayment($order, $request->input('payment_method'));

        return response()->json([
            'message' => 'Instruksi pembayaran berhasil dibuat.',
            'data'    => $instruction,
        ]);
    }

    // -------------------------------------------------------------------------
    // 7.4 — webhook
    // -------------------------------------------------------------------------

    /**
     * Handle webhook notification from Midtrans.
     *
     * This endpoint must be excluded from CSRF protection.
     * API routes in Laravel do not have CSRF middleware by default.
     *
     * Validates: Requirements 6.3, 6.4
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();

        try {
            $this->paymentService->handleWebhook($payload);

            return response()->json(['message' => 'Webhook diproses.']);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::warning('Invalid webhook signature', [
                'payload' => collect($payload)->except('signature_key')->toArray(),
            ]);

            return response()->json(['message' => 'Signature tidak valid.'], 403);
        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'error'   => $e->getMessage(),
                'payload' => $payload,
            ]);

            return response()->json(['message' => 'Terjadi kesalahan saat memproses webhook.'], 500);
        }
    }

    // -------------------------------------------------------------------------
    // 7.5 — confirmCash
    // -------------------------------------------------------------------------

    /**
     * Confirm cash payment by a waiter.
     *
     * Updates payment_status to 'paid', order_status to 'Diproses',
     * and triggers loyalty points accumulation.
     *
     * Validates: Requirements 6.3, 6.5, 13.1
     */
    public function confirm(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'in:cash,qris,card'],
        ]);

        if ($order->payment_status === 'paid') {
            return response()->json([
                'message' => 'Pesanan ini sudah dibayar.',
            ], 422);
        }

        $this->paymentService->confirmManualPayment($order, $validated['payment_method'], $request->user()->id);

        $order->refresh();

        return response()->json([
            'message' => 'Pembayaran berhasil dikonfirmasi.',
            'data'    => [
                'order_id'       => $order->id,
                'order_number'   => $order->order_number,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'order_status'   => $order->order_status,
            ],
        ]);
    }

    public function confirmCash(Request $request, Order $order): JsonResponse
    {
        $request->merge(['payment_method' => 'cash']);

        return $this->confirm($request, $order);
    }

    // -------------------------------------------------------------------------
    // 7.7 — history
    // -------------------------------------------------------------------------

    /**
     * Get payment transaction history (admin only).
     *
     * Query parameters (all optional):
     *   - date_from:      YYYY-MM-DD
     *   - date_to:        YYYY-MM-DD
     *   - payment_method: cash | qris | card
     *   - payment_status: pending | paid | failed
     *
     * Validates: Requirement 6.6
     */
    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'date_from'      => ['nullable', 'date'],
            'date_to'        => ['nullable', 'date', 'after_or_equal:date_from'],
            'payment_method' => ['nullable', 'in:cash,qris,card'],
            'payment_status' => ['nullable', 'in:pending,paid,failed'],
        ]);

        $transactions = $this->paymentService->getTransactionHistory($request->only([
            'date_from',
            'date_to',
            'payment_method',
            'payment_status',
        ]));

        return response()->json([
            'message' => 'Riwayat transaksi berhasil diambil.',
            'data'    => $transactions,
        ]);
    }
}
