<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handles Web Push API subscription management for Customer PWA.
 *
 * Validates: Requirements 17.4 (push notification for Customer PWA)
 */
class NotificationController extends Controller
{
    /**
     * Store or update a push subscription for the authenticated customer.
     *
     * POST /api/customer/push-subscribe
     *
     * Expected body:
     * {
     *   "endpoint": "https://fcm.googleapis.com/...",
     *   "keys": {
     *     "p256dh": "...",
     *     "auth": "..."
     *   }
     * }
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint'      => ['required', 'string', 'url', 'max:2048'],
            'keys'          => ['required', 'array'],
            'keys.p256dh'   => ['required', 'string'],
            'keys.auth'     => ['required', 'string'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        // Upsert: update keys if endpoint already exists for this user,
        // otherwise create a new subscription record.
        $subscription = PushSubscription::updateOrCreate(
            ['endpoint' => $validated['endpoint']],
            [
                'user_id' => $user->id,
                'keys'    => $validated['keys'],
            ]
        );

        return response()->json([
            'message'      => 'Push subscription saved.',
            'subscription' => [
                'id'       => $subscription->id,
                'endpoint' => $subscription->endpoint,
            ],
        ], 201);
    }

    /**
     * Remove a push subscription (e.g., when the customer unsubscribes).
     *
     * DELETE /api/customer/push-subscribe
     *
     * Expected body:
     * {
     *   "endpoint": "https://fcm.googleapis.com/..."
     * }
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'url', 'max:2048'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $deleted = PushSubscription::where('user_id', $user->id)
            ->where('endpoint', $validated['endpoint'])
            ->delete();

        if ($deleted === 0) {
            return response()->json(['message' => 'Subscription not found.'], 404);
        }

        return response()->json(['message' => 'Push subscription removed.']);
    }

    /**
     * Return the VAPID public key so the client can subscribe.
     *
     * GET /api/push/vapid-public-key
     */
    public function vapidPublicKey(): JsonResponse
    {
        $publicKey = config('services.vapid.public_key', '');

        if (empty($publicKey)) {
            Log::warning('VAPID public key is not configured. Set VAPID_PUBLIC_KEY in .env');
        }

        return response()->json(['public_key' => $publicKey]);
    }
}
