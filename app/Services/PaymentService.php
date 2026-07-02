<?php

namespace App\Services;

use App\Models\Order;
use App\Services\LoyaltyService;
use App\Services\MidtransService;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PaymentService — Core business logic for the Payment_Gateway module.
 *
 * Responsibilities:
 *  - Initiate payment (QRIS, card via Midtrans; cash internally)
 *  - Handle webhook from Midtrans (verify signature, update order status)
 *  - Confirm cash payment by waiter
 *  - Trigger loyalty points accumulation after successful payment
 *  - Record payment transaction history
 *
 * All state-changing operations (update order + add points + record point_transaction)
 * are wrapped in a DB::transaction() to ensure atomicity.
 *
 * Validates: Requirements 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 13.1, 13.5
 */
class PaymentService
{
    public function __construct(
        private readonly MidtransService $midtrans,
        private readonly OrderService $orderService,
        private readonly LoyaltyService $loyaltyService,
    ) {}

    // -------------------------------------------------------------------------
    // Initiate Payment
    // -------------------------------------------------------------------------

    /**
     * Initiate a payment for an order.
     *
     * For QRIS/card: calls Midtrans Snap API (or simulation).
     * For cash: returns the total amount for the waiter to collect.
     *
     * @param  Order   $order
     * @param  string  $paymentMethod  'cash' | 'qris' | 'card'
     * @return array   Payment instruction data
     *
     * @throws \InvalidArgumentException  for unsupported payment methods
     * Validates: Requirements 6.1, 6.2, 6.5
     */
    public function initiatePayment(Order $order, string $paymentMethod): array
    {
        // Update payment_method on the order
        $order->update(['payment_method' => $paymentMethod]);

        if ($paymentMethod === 'cash') {
            return $this->initiateCashPayment($order);
        }

        if (in_array($paymentMethod, ['qris', 'card'], true)) {
            return $this->initiateOnlinePayment($order, $paymentMethod);
        }

        throw new \InvalidArgumentException("Metode pembayaran '{$paymentMethod}' tidak didukung.");
    }

    // -------------------------------------------------------------------------
    // Webhook Handler
    // -------------------------------------------------------------------------

    /**
     * Handle a webhook notification from Midtrans.
     *
     * Verifies the signature, then updates order payment_status and order_status
     * based on the transaction status. Triggers loyalty points on success.
     *
     * @param  array  $payload  Webhook payload from Midtrans
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException  on invalid signature
     * Validates: Requirements 6.3, 6.4
     */
    public function handleWebhook(array $payload): void
    {
        $orderId      = $payload['order_id'] ?? '';
        $statusCode   = $payload['status_code'] ?? '';
        $grossAmount  = $payload['gross_amount'] ?? '';
        $signatureKey = $payload['signature_key'] ?? '';
        $transStatus  = $payload['transaction_status'] ?? '';
        $fraudStatus  = $payload['fraud_status'] ?? null;

        // Verify signature
        if (! $this->midtrans->verifyWebhookSignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Signature webhook tidak valid.');
        }

        $order = Order::find((int) $orderId);

        if (! $order) {
            Log::warning('Webhook received for unknown order', ['order_id' => $orderId]);
            return;
        }

        // Determine new payment status based on Midtrans transaction_status
        if ($this->isPaymentSuccess($transStatus, $fraudStatus)) {
            $this->confirmPayment($order);
        } elseif ($this->isPaymentFailed($transStatus)) {
            $this->markPaymentFailed($order);
        }
        // Other statuses (pending, etc.) are ignored — no state change
    }

    // -------------------------------------------------------------------------
    // Cash Payment Confirmation
    // -------------------------------------------------------------------------

    /**
     * Confirm cash payment by a waiter.
     *
     * Updates payment_status to 'paid', order_status to 'Diproses',
     * and triggers loyalty points accumulation — all inside a DB transaction.
     *
     * @param  Order  $order
     * @param  int    $waiterId  ID of the waiter confirming the payment
     * @return void
     *
     * Validates: Requirements 6.3, 6.5, 13.1
     */
    public function confirmCashPayment(Order $order, int $waiterId): void
    {
        $this->confirmManualPayment($order, 'cash', $waiterId);
    }

    /**
     * Confirm a manual payment by a waiter/admin.
     *
     * This is used by cashier-side payments that are verified outside Midtrans,
     * such as cash, QRIS terminal, or card EDC.
     */
    public function confirmManualPayment(Order $order, string $paymentMethod, int $waiterId): void
    {
        if ($order->payment_status === 'paid') {
            throw new \LogicException('Pesanan ini sudah dibayar.');
        }

        if (! in_array($paymentMethod, ['cash', 'qris', 'card'], true)) {
            throw new \InvalidArgumentException("Metode pembayaran '{$paymentMethod}' tidak didukung.");
        }

        $this->confirmPayment($order, $paymentMethod);

        Log::info('Manual payment confirmed', [
            'order_id'       => $order->id,
            'payment_method' => $paymentMethod,
            'waiter_id'      => $waiterId,
        ]);
    }

    // -------------------------------------------------------------------------
    // Transaction History
    // -------------------------------------------------------------------------

    /**
     * Get payment transaction history with optional filters.
     *
     * Uses the orders table which already contains payment_method,
     * payment_status, total_price, and created_at.
     *
     * @param  array  $filters  Optional: date_from, date_to, payment_method, payment_status
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * Validates: Requirement 6.6
     */
    public function getTransactionHistory(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Order::with(['user:id,name,email', 'table:id,table_number'])
            ->whereNotNull('payment_method')
            ->orderByDesc('created_at');

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        return $query->get([
            'id',
            'user_id',
            'table_id',
            'total_price',
            'payment_method',
            'payment_status',
            'order_status',
            'order_type',
            'created_at',
        ]);
    }

    // -------------------------------------------------------------------------
    // Private: Core payment confirmation (shared by cash + webhook)
    // -------------------------------------------------------------------------

    /**
     * Confirm a payment: update order statuses and accumulate loyalty points.
     *
     * Wrapped in a DB transaction to ensure atomicity:
     *   1. Update order payment_status → 'paid'
     *   2. Update order order_status → 'Diproses'
     *   3. Add loyalty points to user
     *   4. Record point_transaction
     *
     * Validates: Requirements 6.3, 13.1, 13.5
     */
    private function confirmPayment(Order $order, ?string $paymentMethod = null): void
    {
        DB::transaction(function () use ($order, $paymentMethod) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->payment_status === 'paid') {
                return;
            }

            // 1 & 2: Update order statuses
            $lockedOrder->update([
                'payment_status' => 'paid',
                'payment_method' => $paymentMethod ?: ($lockedOrder->payment_method ?: 'cash'),
                'order_status'   => 'Diproses',
            ]);

            // 3 & 4: Accumulate loyalty points (only for authenticated customers)
            if ($lockedOrder->user_id) {
                $this->accumulateLoyaltyPoints($lockedOrder);
            }
        });
    }

    /**
     * Mark a payment as failed.
     *
     * Validates: Requirement 6.4
     */
    private function markPaymentFailed(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->payment_status === 'paid') {
                return;
            }

            $lockedOrder->update(['payment_status' => 'failed']);
        });
    }

    // -------------------------------------------------------------------------
    // Private: Loyalty Points Accumulation
    // -------------------------------------------------------------------------

    /**
     * Delegate loyalty point accumulation to LoyaltyService after a successful payment.
     *
     * LoyaltyService is the single source of truth for all point operations.
     * It handles the DB transaction, lockForUpdate, and PointTransaction record creation.
     *
     * Validates: Requirements 13.1, 13.5
     */
    private function accumulateLoyaltyPoints(Order $order): void
    {
        $this->loyaltyService->addPoints(
            userId:            $order->user_id,
            transactionAmount: (float) $order->total_price,
            orderId:           $order->id,
        );
    }

    // -------------------------------------------------------------------------
    // Private: Payment initiation helpers
    // -------------------------------------------------------------------------

    private function initiateCashPayment(Order $order): array
    {
        return [
            'payment_method' => 'cash',
            'total_amount'   => (float) $order->total_price,
            'order_id'       => $order->id,
            'message'        => 'Tunjukkan total tagihan kepada pelayan untuk pembayaran tunai.',
        ];
    }

    private function initiateOnlinePayment(Order $order, string $paymentMethod): array
    {
        $snapData = $this->midtrans->createSnapToken($order, $paymentMethod);

        $result = [
            'payment_method'  => $paymentMethod,
            'order_id'        => $order->id,
            'total_amount'    => (float) $order->total_price,
            'snap_token'      => $snapData['token'],
            'redirect_url'    => $snapData['redirect_url'],
            'simulation_mode' => $this->midtrans->isSimulationMode(),
        ];

        if ($paymentMethod === 'qris' && ! empty($snapData['qris_url'])) {
            $result['qris_url'] = $snapData['qris_url'];
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Private: Midtrans status helpers
    // -------------------------------------------------------------------------

    private function isPaymentSuccess(string $transactionStatus, ?string $fraudStatus): bool
    {
        if ($transactionStatus === 'capture') {
            return $fraudStatus === 'accept';
        }

        return in_array($transactionStatus, ['settlement', 'success'], true);
    }

    private function isPaymentFailed(string $transactionStatus): bool
    {
        return in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'], true);
    }
}
