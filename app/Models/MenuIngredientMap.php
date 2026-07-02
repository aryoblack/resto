<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class MenuIngredientMap extends Pivot
{
    protected $table = 'menu_ingredient_map';

    public $timestamps = false;

    protected $fillable = [
        'menu_id',
        'ingredient_id',
        'quantity_used',
    ];

    protected function casts(): array
    {
        return [
            'quantity_used' => 'decimal:3',
        ];
    }

    /**
     * Get the menu associated with this mapping.
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * Get the ingredient (inventory item) associated with this mapping.
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'ingredient_id');
    }
}
