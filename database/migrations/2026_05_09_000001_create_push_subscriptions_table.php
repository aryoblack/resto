<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for storing Web Push API subscriptions.
 *
 * Each row represents a browser/device subscription for a customer.
 * A single user may have multiple subscriptions (multiple devices/browsers).
 *
 * Validates: Requirements 17.4 (push notification for Customer PWA)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscription', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            // The push endpoint URL provided by the browser's push service
            $table->string('endpoint', 500);
            // VAPID keys: { p256dh: string, auth: string }
            $table->json('keys');
            $table->timestamps();

            // Prevent duplicate subscriptions for the same endpoint
            $table->unique('endpoint', 'push_subscriptions_endpoint_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscription');
    }
};
