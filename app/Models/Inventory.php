<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    protected $table = 'inventory';

    protected $fillable = [
        'ingredient_name',
        'unit',
        'current_stock',
        'min_stock',
        'supplier',
    ];

    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:3',
            'min_stock' => 'decimal:3',
        ];
    }

    /**
     * Get all stock movements for this ingredient.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'ingredient_id');
    }

    /**
     * Get all menus that use this ingredient (via menu_ingredient_map).
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'menu_ingredient_map', 'ingredient_id', 'menu_id')
            ->withPivot('quantity_used')
            ->using(MenuIngredientMap::class);
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'inventory_supplier')
            ->withPivot(['last_price', 'lead_time_days', 'is_primary'])
            ->withTimestamps();
    }

    public function primarySupplier(): ?Supplier
    {
        return $this->suppliers->firstWhere('pivot.is_primary', true) ?? $this->suppliers->first();
    }

    /**
     * Check if this ingredient is at critical stock level.
     */
    public function isCriticalStock(): bool
    {
        return $this->current_stock <= $this->min_stock;
    }
}
