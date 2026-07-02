<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherUsage extends Model
{
    protected $table = 'voucher_usage';

    public $timestamps = false;

    protected $fillable = [
        'promo_id',
        'user_id',
        'order_id',
        'discount_applied',
    ];

    protected function casts(): array
    {
        return [
            'discount_applied' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the promo associated with this usage.
     */
    public function promo(): BelongsTo
    {
        return $this->belongsTo(Promo::class);
    }

    /**
     * Get the user who used this voucher.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order this voucher was applied to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
