<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use SoftDeletes;

    protected $table = 'menu';

    protected $fillable = [
        'name',
        'category_id',
        'price',
        'stock',
        'description',
        'image_url',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'is_available' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the category this menu belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all variants for this menu.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }

    /**
     * Get all ingredients mapped to this menu (via menu_ingredient_map).
     */
    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Inventory::class, 'menu_ingredient_map', 'menu_id', 'ingredient_id')
            ->withPivot('quantity_used')
            ->using(MenuIngredientMap::class);
    }
}
