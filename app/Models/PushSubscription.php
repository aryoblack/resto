<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a Web Push API subscription for a customer device/browser.
 *
 * @property int    $id
 * @property int    $user_id
 * @property string $endpoint
 * @property array  $keys      { p256dh: string, auth: string }
 */
class PushSubscription extends Model
{
    protected $table = 'push_subscription';

    protected $fillable = [
        'user_id',
        'endpoint',
        'keys',
    ];

    protected function casts(): array
    {
        return [
            'keys' => 'array',
        ];
    }

    /**
     * Get the user who owns this push subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
