<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function inventory(): BelongsToMany
    {
        return $this->belongsToMany(Inventory::class, 'inventory_supplier')
            ->withPivot(['last_price', 'lead_time_days', 'is_primary'])
            ->withTimestamps();
    }
}
