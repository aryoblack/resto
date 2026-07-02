<?php

namespace App\Services;

use App\Models\PointTransaction;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * LoyaltyService — Single source of truth for the Loyalty_Engine module.
 *
 * Responsibilities:
 *  - Calculate points earned from a transaction amount
 *  - Add points to a user's balance after successful payment
 *  - Redeem points as a discount at checkout
 *  - Validate point balance before redemption
 *  - Record all point earn/redeem transactions in point_transactions
 *  - Return point balance and history
 *
 * All state-changing operations use DB::transaction() with lockForUpdate()
 * to prevent race conditions.
 *
 * Validates: Requirements 13.1, 13.2, 13.3, 13.4, 13.5
 */
class LoyaltyService
{
    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Calculate how many points a transaction amount earns.
     *
     * Formula: floor(amount / point_conversion_rate)
     *
     * @param  float  $amount  Transaction total in Rupiah
     * @return int             Points earned (>= 0)
     *
     * Validates: Requirement 13.1
     */
    public function calculatePointsForTransaction(float $amount): int
    {
        $rate = $this->getConversionRate();

        if ($rate <= 0 || $amount <= 0) {
            return 0;
        }

        return (int) floor($amount / $rate);
    }

    /**
     * Add points to a user's balance after a successful payment.
     *
     * Wrapped in DB::transaction() with lockForUpdate() to prevent race conditions.
     * Creates a PointTransaction record of type 'earn'.
     *
     * @param  int        $userId             The customer's user ID
     * @param  float      $transactionAmount  The order total in Rupiah
     * @param  int|null   $orderId            Optional order reference
     * @return int                            New point balance after addition
     *
     * Validates: Requirements 13.1, 13.5
     */
    public function addPoints(int $userId, float $transactionAmount, ?int $orderId = null): int
    {
        $pointsToAdd = $this->calculatePointsForTransaction($transactionAmount);

        if ($pointsToAdd <= 0) {
            // No points to add — return current balance
            $user = User::find($userId);
            return $user ? $user->poin : 0;
        }

        return DB::transaction(function () use ($userId, $pointsToAdd, $orderId, $transactionAmount) {
            /** @var User $user */
            $user = User::lockForUpdate()->findOrFail($userId);

            $newBalance = $user->poin + $pointsToAdd;

            $user->update(['poin' => $newBalance]);

            PointTransaction::create([
                'user_id'       => $user->id,
                'order_id'      => $orderId,
                'type'          => 'earn',
                'points'        => $pointsToAdd,
                'balance_after' => $newBalance,
                'note'          => $orderId
                    ? "Poin dari pesanan #{$orderId}"
                    : "Poin dari transaksi Rp " . number_format($transactionAmount, 0, ',', '.'),
            ]);

            return $newBalance;
        });
    }

    /**
     * Redeem points as a discount at checkout.
     *
     * Validates that the user has sufficient balance before proceeding.
     * Wrapped in DB::transaction() with lockForUpdate().
     * Creates a PointTransaction record of type 'redeem'.
     *
     * Discount value: pointsToRedeem × point_value (from system_settings, default 1)
     * i.e. 1 point = Rp 1 discount by default.
     *
     * @param  int       $userId          The customer's user ID
     * @param  int       $pointsToRedeem  Number of points to redeem
     * @param  int|null  $orderId         Optional order reference
     * @return array{
     *   success: bool,
     *   discount_amount: float,
     *   new_balance: int,
     *   message: string,
     *   available_balance?: int
     * }
     *
     * Validates: Requirements 13.3, 13.4, 13.5
     */
    public function redeemPoints(int $userId, int $pointsToRedeem, ?int $orderId = null): array
    {
        if ($pointsToRedeem <= 0) {
            return [
                'success'         => false,
                'discount_amount' => 0.0,
                'new_balance'     => $this->getPointBalance($userId),
                'message'         => 'Jumlah poin yang ditukar harus lebih dari 0.',
            ];
        }

        return DB::transaction(function () use ($userId, $pointsToRedeem, $orderId) {
            /** @var User $user */
            $user = User::lockForUpdate()->findOrFail($userId);

            // Validate sufficient balance (Requirement 13.4)
            if ($user->poin < $pointsToRedeem) {
                return [
                    'success'           => false,
                    'discount_amount'   => 0.0,
                    'new_balance'       => $user->poin,
                    'available_balance' => $user->poin,
                    'message'           => "Saldo poin tidak mencukupi. Saldo Anda: {$user->poin} poin.",
                ];
            }

            $pointValue      = $this->getPointValue();
            $discountAmount  = $pointsToRedeem * $pointValue;
            $newBalance      = $user->poin - $pointsToRedeem;

            $user->update(['poin' => $newBalance]);

            PointTransaction::create([
                'user_id'       => $user->id,
                'order_id'      => $orderId,
                'type'          => 'redeem',
                'points'        => $pointsToRedeem,
                'balance_after' => $newBalance,
                'note'          => $orderId
                    ? "Penukaran poin untuk pesanan #{$orderId}"
                    : "Penukaran {$pointsToRedeem} poin sebagai diskon",
            ]);

            return [
                'success'         => true,
                'discount_amount' => (float) $discountAmount,
                'new_balance'     => $newBalance,
                'message'         => "Berhasil menukar {$pointsToRedeem} poin. Diskon: Rp " . number_format($discountAmount, 0, ',', '.'),
            ];
        });
    }

    /**
     * Get the current point balance for a user.
     *
     * @param  int  $userId
     * @return int  Current point balance (0 if user not found)
     *
     * Validates: Requirement 13.2
     */
    public function getPointBalance(int $userId): int
    {
        $user = User::find($userId);
        return $user ? $user->poin : 0;
    }

    /**
     * Get the full point transaction history for a user.
     *
     * Returns transactions ordered by most recent first.
     *
     * @param  int  $userId
     * @return Collection<PointTransaction>
     *
     * Validates: Requirement 13.5
     */
    public function getPointHistory(int $userId): Collection
    {
        return PointTransaction::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Get the point conversion rate from system settings.
     * Default: 10000 (Rp 10.000 = 1 point)
     */
    private function getConversionRate(): float
    {
        return (float) SystemSetting::getValue('point_conversion_rate', 10000);
    }

    /**
     * Get the point redemption value from system settings.
     * Default: 1 (1 point = Rp 1 discount)
     */
    private function getPointValue(): float
    {
        return (float) SystemSetting::getValue('point_value', 1);
    }
}
