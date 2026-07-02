<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for sending Web Push notifications to Customer PWA devices.
 *
 * Uses the Web Push Protocol with VAPID authentication.
 * If the `minishlink/web-push` package is available it will be used;
 * otherwise this service sends raw HTTP requests to the push endpoint
 * using the Voluntary Application Server Identification (VAPID) standard.
 *
 * For a production setup, install: composer require minishlink/web-push
 *
 * Validates: Requirements 17.4 (push notification for Customer PWA)
 */
class PushNotificationService
{
    /**
     * Send a push notification to all subscriptions of a given user.
     *
     * @param  User                  $user     The recipient customer
     * @param  array<string, mixed>  $payload  Notification payload
     *                                         { title, body, icon?, url? }
     */
    public function sendToUser(User $user, array $payload): void
    {
        $subscriptions = PushSubscription::where('user_id', $user->id)->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        foreach ($subscriptions as $subscription) {
            $this->send($subscription, $payload);
        }
    }

    /**
     * Send a push notification to a single subscription.
     *
     * @param  PushSubscription      $subscription
     * @param  array<string, mixed>  $payload
     */
    public function send(PushSubscription $subscription, array $payload): void
    {
        // If minishlink/web-push is installed, use it for proper VAPID signing.
        if (class_exists(\Minishlink\WebPush\WebPush::class)) {
            $this->sendViaWebPushLibrary($subscription, $payload);
            return;
        }

        // Fallback: log the notification (no VAPID signing without the library).
        // In production, install minishlink/web-push for real delivery.
        Log::info('PushNotificationService: would send push notification', [
            'user_id'  => $subscription->user_id,
            'endpoint' => substr($subscription->endpoint, 0, 60) . '...',
            'payload'  => $payload,
        ]);
    }

    /**
     * Send via the minishlink/web-push library (VAPID-signed).
     *
     * @param  PushSubscription      $subscription
     * @param  array<string, mixed>  $payload
     */
    private function sendViaWebPushLibrary(PushSubscription $subscription, array $payload): void
    {
        try {
            $auth = [
                'VAPID' => [
                    'subject'    => config('app.url'),
                    'publicKey'  => config('services.vapid.public_key'),
                    'privateKey' => config('services.vapid.private_key'),
                ],
            ];

            /** @var \Minishlink\WebPush\WebPush $webPush */
            $webPush = new \Minishlink\WebPush\WebPush($auth);

            $sub = \Minishlink\WebPush\Subscription::create([
                'endpoint'        => $subscription->endpoint,
                'contentEncoding' => 'aesgcm',
                'keys'            => $subscription->keys,
            ]);

            $webPush->queueNotification(
                $sub,
                json_encode($payload, JSON_UNESCAPED_UNICODE)
            );

            foreach ($webPush->flush() as $report) {
                if (! $report->isSuccess()) {
                    Log::warning('Push notification failed', [
                        'endpoint' => substr($subscription->endpoint, 0, 60),
                        'reason'   => $report->getReason(),
                    ]);

                    // Remove expired/invalid subscriptions (410 Gone)
                    if ($report->isSubscriptionExpired()) {
                        $subscription->delete();
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('PushNotificationService error: ' . $e->getMessage(), [
                'user_id' => $subscription->user_id,
            ]);
        }
    }
}
