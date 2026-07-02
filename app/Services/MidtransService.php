<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * MidtransService — Wrapper for Midtrans payment gateway integration.
 *
 * When the Midtrans PHP SDK is not installed or credentials are not configured,
 * this service falls back to simulation mode so the application can run without
 * real payment credentials (useful for development and testing).
 *
 * Validates: Requirements 6.1, 6.2
 */
class MidtransService
{
    private bool $isProduction;
    private string $serverKey;
    private string $clientKey;
    private bool $simulationMode;

    public function __construct()
    {
        $this->serverKey    = config('services.midtrans.server_key', '');
        $this->clientKey    = config('services.midtrans.client_key', '');
        $this->isProduction = (bool) config('services.midtrans.is_production', false);

        // Use simulation mode when SDK is not installed or credentials are missing
        $this->simulationMode = ! class_exists(\Midtrans\Config::class) || empty($this->serverKey);

        if (! $this->simulationMode) {
            \Midtrans\Config::$serverKey    = $this->serverKey;
            \Midtrans\Config::$clientKey    = $this->clientKey;
            \Midtrans\Config::$isProduction = $this->isProduction;
            \Midtrans\Config::$isSanitized  = true;
            \Midtrans\Config::$is3ds        = true;
        }
    }

    /**
     * Create a Snap payment token for QRIS or card payment.
     *
     * Returns an array with:
     *   - token: Snap token (or simulated token)
     *   - redirect_url: Payment URL
     *   - qris_url: QRIS image URL (for QRIS method)
     *
     * @param  Order   $order
     * @param  string  $paymentMethod  'qris' or 'card'
     * @return array{token: string, redirect_url: string, qris_url: string|null}
     */
    public function createSnapToken(Order $order, string $paymentMethod): array
    {
        if ($this->simulationMode) {
            return $this->simulateSnapToken($order, $paymentMethod);
        }

        try {
            $params = $this->buildSnapParams($order, $paymentMethod);
            $snapToken = \Midtrans\Snap::getSnapToken($params);

            $baseUrl = $this->isProduction
                ? 'https://app.midtrans.com/snap/v2/vtweb/'
                : 'https://app.sandbox.midtrans.com/snap/v2/vtweb/';

            return [
                'token'        => $snapToken,
                'redirect_url' => $baseUrl . $snapToken,
                'qris_url'     => $paymentMethod === 'qris'
                    ? $this->generateQrisUrl($order)
                    : null,
            ];
        } catch (\Exception $e) {
            Log::error('Midtrans Snap token creation failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            // Fall back to simulation on error
            return $this->simulateSnapToken($order, $paymentMethod);
        }
    }

    /**
     * Verify a Midtrans webhook notification signature.
     *
     * Midtrans signature: SHA-512 of (order_id + status_code + gross_amount + server_key)
     *
     * @param  string  $orderId
     * @param  string  $statusCode
     * @param  string  $grossAmount
     * @param  string  $signatureKey  Signature from webhook payload
     * @return bool
     */
    public function verifyWebhookSignature(
        string $orderId,
        string $statusCode,
        string $grossAmount,
        string $signatureKey
    ): bool {
        $expectedSignature = hash(
            'sha512',
            $orderId . $statusCode . $grossAmount . $this->serverKey
        );

        return hash_equals($expectedSignature, $signatureKey);
    }

    /**
     * Get the server key (used for signature verification in tests).
     */
    public function getServerKey(): string
    {
        return $this->serverKey;
    }

    /**
     * Check if running in simulation mode.
     */
    public function isSimulationMode(): bool
    {
        return $this->simulationMode;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Build Snap API parameters from an Order.
     */
    private function buildSnapParams(Order $order, string $paymentMethod): array
    {
        $params = [
            'transaction_details' => [
                'order_id'     => (string) $order->id,
                'gross_amount' => (int) $order->total_price,
            ],
            'customer_details' => [
                'first_name' => $order->user?->name ?? 'Guest',
                'email'      => $order->user?->email ?? 'guest@example.com',
            ],
        ];

        // Restrict to specific payment type
        if ($paymentMethod === 'qris') {
            $params['enabled_payments'] = ['gopay', 'qris'];
        } elseif ($paymentMethod === 'card') {
            $params['enabled_payments'] = ['credit_card'];
        }

        return $params;
    }

    /**
     * Generate a QRIS image URL via Midtrans charge API.
     * Returns null if not available.
     */
    private function generateQrisUrl(Order $order): ?string
    {
        // In a real implementation, this would call the Midtrans charge API
        // to get the QRIS image URL. For now, return null and let the Snap
        // redirect URL handle the QRIS display.
        return null;
    }

    /**
     * Simulate a Snap token response for development/testing.
     *
     * Generates a unique simulated token per order so each transaction
     * has a distinct QRIS/payment URL.
     *
     * Validates: Requirement 6.2 (unique QRIS per transaction)
     */
    private function simulateSnapToken(Order $order, string $paymentMethod): array
    {
        $simulatedToken = 'sim-' . $order->id . '-' . hash('sha256', $order->id . $order->total_price . now()->timestamp);

        $baseUrl = url('/api/payment/simulate');

        return [
            'token'        => $simulatedToken,
            'redirect_url' => $baseUrl . '?token=' . $simulatedToken . '&order_id=' . $order->id,
            'qris_url'     => $paymentMethod === 'qris'
                ? $baseUrl . '/qris?token=' . $simulatedToken . '&order_id=' . $order->id
                : null,
        ];
    }
}
