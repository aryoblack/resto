<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Table extends Model
{
    protected $table = 'table';

    protected $fillable = [
        'table_number',
        'qr_code',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    /**
     * Get all orders for this table.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all reservations for this table.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Check if the table is available.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    /**
     * Check if the table is occupied.
     */
    public function isOccupied(): bool
    {
        return $this->status === 'occupied';
    }

    /**
     * Automatically release the table to `available` if all active orders
     * on this table are both `Disajikan` (served) and `paid`.
     *
     * Called by Order_Manager after updating an order's status or payment.
     *
     * Validates: Requirement 10.3 — "WHEN all orders on a table are Disajikan
     * and payment is complete, THE Order_Manager SHALL update table status to available."
     *
     * @return bool  true if the table was released, false if it remains occupied.
     */
    public function releaseIfAllOrdersComplete(): bool
    {
        // Only act on occupied tables
        if (! $this->isOccupied()) {
            return false;
        }

        $hasIncompleteOrders = $this->orders()
            ->where(function ($query) {
                $query->where('order_status', '!=', 'Disajikan')
                      ->orWhere('payment_status', '!=', 'paid');
            })
            ->whereNotIn('order_status', ['Dibatalkan'])
            ->exists();

        if (! $hasIncompleteOrders) {
            $this->update(['status' => 'available']);
            return true;
        }

        return false;
    }
}
