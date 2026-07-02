<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promo extends Model
{
    protected $table = 'promo';

    protected $fillable = [
        'name',
        'code',
        'type',
        'value',
        'min_purchase',
        'max_discount',
        'start_date',
        'end_date',
        'is_active',
        'usage_limit',
        'usage_count',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_purchase' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'usage_limit' => 'integer',
            'usage_count' => 'integer',
        ];
    }

    /**
     * Get all voucher usages for this promo.
     */
    public function voucherUsages(): HasMany
    {
        return $this->hasMany(VoucherUsage::class);
    }

    /**
     * Check if this promo is currently valid (active, within date range, usage not exceeded).
     */
    public function isValid(): bool
    {
        $now = now()->toDateString();

        return $this->is_active
            && $this->start_date <= $now
            && $this->end_date >= $now
            && ($this->usage_limit === null || $this->usage_count < $this->usage_limit);
    }
}
