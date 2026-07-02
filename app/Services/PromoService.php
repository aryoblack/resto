<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Promo;
use App\Models\VoucherUsage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * PromoService — Core business logic for the Promo_Engine module.
 *
 * Responsibilities:
 *  - Validate voucher codes (active, date range, usage limit, min purchase)
 *  - Calculate discount amounts (percentage with max_discount cap, nominal)
 *  - Apply voucher to an order (update discount_amount, increment usage_count)
 *  - Record voucher usage in voucher_usages table
 *  - Retrieve active promos for banner display
 *
 * Validates: Requirements 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 12.7
 */
class PromoService
{
    // -------------------------------------------------------------------------
    // Validate Voucher
    // -------------------------------------------------------------------------

    /**
     * Validate a voucher code against a given cart total.
     *
     * Checks (in order):
     *  1. Promo exists with the given code
     *  2. is_active = true
     *  3. Current date is within [start_date, end_date]
     *  4. usage_count < usage_limit (if usage_limit is not null)
     *  5. cartTotal >= min_purchase
     *
     * @param  string  $code
     * @param  float   $cartTotal
     * @return array{valid: bool, discount_amount: float, message: string, promo: Promo|null}
     *
     * Validates: Requirements 12.3, 12.4, 12.5
     */
    public function validateVoucher(string $code, float $cartTotal): array
    {
        $promo = Promo::where('code', $code)->first();

        if (! $promo) {
            return [
                'valid'           => false,
                'discount_amount' => 0.0,
                'message'         => 'Kode voucher tidak ditemukan.',
                'promo'           => null,
            ];
        }

        if (! $promo->is_active) {
            return [
                'valid'           => false,
                'discount_amount' => 0.0,
                'message'         => 'Voucher tidak aktif.',
                'promo'           => null,
            ];
        }

        $today = now()->toDateString();

        if ($promo->start_date > $today || $promo->end_date < $today) {
            return [
                'valid'           => false,
                'discount_amount' => 0.0,
                'message'         => 'Voucher sudah kedaluwarsa atau belum berlaku.',
                'promo'           => null,
            ];
        }

        if ($promo->usage_limit !== null && $promo->usage_count >= $promo->usage_limit) {
            return [
                'valid'           => false,
                'discount_amount' => 0.0,
                'message'         => 'Voucher sudah mencapai batas penggunaan.',
                'promo'           => null,
            ];
        }

        if ($cartTotal < (float) $promo->min_purchase) {
            return [
                'valid'           => false,
                'discount_amount' => 0.0,
                'message'         => 'Total belanja minimum untuk voucher ini adalah Rp ' . number_format((float) $promo->min_purchase, 0, ',', '.') . '.',
                'promo'           => $promo,
            ];
        }

        $discountAmount = $this->calculateDiscount($promo, $cartTotal);

        return [
            'valid'           => true,
            'discount_amount' => $discountAmount,
            'message'         => 'Voucher berhasil diterapkan.',
            'promo'           => $promo,
        ];
    }

    // -------------------------------------------------------------------------
    // Calculate Discount
    // -------------------------------------------------------------------------

    /**
     * Calculate the discount amount for a given promo and cart total.
     *
     * Rules:
     *  - percentage: min(cartTotal × value/100, max_discount ?? PHP_FLOAT_MAX)
     *  - nominal:    min(value, cartTotal)  — discount cannot exceed cart total
     *
     * @param  Promo  $promo
     * @param  float  $cartTotal
     * @return float
     *
     * Validates: Requirements 12.1, 12.3
     */
    public function calculateDiscount(Promo $promo, float $cartTotal): float
    {
        if ($promo->type === 'percentage') {
            $discount = $cartTotal * ((float) $promo->value / 100);

            if ($promo->max_discount !== null) {
                $discount = min($discount, (float) $promo->max_discount);
            }

            return round($discount, 2);
        }

        // nominal
        return round(min((float) $promo->value, $cartTotal), 2);
    }

    // -------------------------------------------------------------------------
    // Apply Voucher
    // -------------------------------------------------------------------------

    /**
     * Apply a voucher to an order.
     *
     * Steps (inside a DB transaction):
     *  1. Validate the voucher
     *  2. Calculate discount
     *  3. Update order.discount_amount
     *  4. Increment promo.usage_count
     *  5. Create VoucherUsage record
     *
     * @param  string  $code
     * @param  Order   $order
     * @param  int     $userId
     * @return float   The discount amount applied
     *
     * @throws \Illuminate\Validation\ValidationException  when voucher is invalid
     *
     * Validates: Requirements 12.3, 12.7
     */
    public function applyVoucher(string $code, Order $order, int $userId): float
    {
        $cartTotal = (float) $order->total_price + (float) $order->discount_amount;

        $discountAmount = 0.0;

        DB::transaction(function () use ($code, $cartTotal, $order, &$discountAmount, $userId) {
            $promo = Promo::where('code', $code)->lockForUpdate()->first();

            if (! $promo) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'code' => 'Kode voucher tidak ditemukan.',
                ]);
            }

            $result = $this->validateVoucher($code, $cartTotal);

            if (! $result['valid']) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'code' => $result['message'],
                ]);
            }

            if ($promo->usage_limit !== null && $promo->usage_count >= $promo->usage_limit) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'code' => 'Voucher sudah mencapai batas penggunaan.',
                ]);
            }

            $discountAmount = $result['discount_amount'];

            // Update order discount
            $order->update(['discount_amount' => $discountAmount]);

            // Increment usage count
            $promo->increment('usage_count');

            // Record usage
            VoucherUsage::create([
                'promo_id'         => $promo->id,
                'user_id'          => $userId,
                'order_id'         => $order->id,
                'discount_applied' => $discountAmount,
            ]);
        });

        return $discountAmount;
    }

    // -------------------------------------------------------------------------
    // Get Active Promos
    // -------------------------------------------------------------------------

    /**
     * Retrieve all currently active promos (for banner display on Customer_App).
     *
     * A promo is active when:
     *  - is_active = true
     *  - start_date <= today
     *  - end_date >= today
     *
     * @return Collection<int, Promo>
     *
     * Validates: Requirement 12.6
     */
    public function getActivePromos(): Collection
    {
        $today = now()->toDateString();

        return Promo::where('is_active', true)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where(function ($query) {
                $query->whereNull('usage_limit')
                    ->orWhereColumn('usage_count', '<', 'usage_limit');
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // -------------------------------------------------------------------------
    // Record Voucher Usage (standalone)
    // -------------------------------------------------------------------------

    /**
     * Record a voucher usage entry directly (without applying to order).
     *
     * Useful when the discount has already been applied externally.
     *
     * @param  string  $code
     * @param  int     $orderId
     * @param  int     $userId
     * @return void
     *
     * Validates: Requirement 12.7
     */
    public function recordVoucherUsage(string $code, int $orderId, int $userId): void
    {
        $promo = Promo::where('code', $code)->firstOrFail();
        $order = Order::findOrFail($orderId);

        VoucherUsage::create([
            'promo_id'         => $promo->id,
            'user_id'          => $userId,
            'order_id'         => $orderId,
            'discount_applied' => (float) $order->discount_amount,
        ]);
    }
}
