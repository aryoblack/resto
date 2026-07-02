<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $table = 'reservation';

    protected $fillable = [
        'user_id',
        'table_id',
        'date',
        'time',
        'number_of_people',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'number_of_people' => 'integer',
        ];
    }

    /**
     * Get the user who made this reservation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the table reserved.
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }
}
