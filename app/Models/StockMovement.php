<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $table = 'stock_movement';

    public $timestamps = false;

    protected $fillable = [
        'ingredient_id',
        'quantity_change',
        'type',
        'note',
        'order_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity_change' => 'decimal:3',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the inventory ingredient this movement belongs to.
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'ingredient_id');
    }

    /**
     * Get the order that triggered this stock movement (if any).
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who created this stock movement.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
