<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when an order's status is updated.
 *
 * Broadcast to `orders` channel (Admin, Chef) and `customer.{id}` (Customer).
 * Also broadcast to `waiter` channel when status becomes `Selesai`.
 *
 * Validates: Requirements 7.2, 8.3, 17.2
 */
class OrderStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly string $previousStatus,
        public readonly string $newStatus,
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('orders'),
        ];

        // Notify the customer on their private channel
        if ($this->order->user_id) {
            $channels[] = new PrivateChannel('customer.' . $this->order->user_id);
        }

        // Notify waiter when order is ready (Selesai)
        if ($this->newStatus === 'Selesai') {
            $channels[] = new PrivateChannel('waiter');
        }

        return $channels;
    }

    /**
     * The event name used on the client side.
     */
    public function broadcastAs(): string
    {
        return 'order.status.updated';
    }

    /**
     * Whether the Customer_App should show the rating prompt.
     *
     * Returns true when the new status is `Disajikan` and the order
     * has not yet been rated (Requirement 16.1).
     */
    public function showRatingPrompt(): bool
    {
        return $this->newStatus === 'Disajikan'
            && ! $this->order->rating()->exists();
    }

    /**
     * Data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order' => [
                'id'                 => $this->order->id,
                'order_number'       => $this->order->order_number,
                'order_status'       => $this->order->order_status,
                'previous_status'    => $this->previousStatus,
                'new_status'         => $this->newStatus,
                'order_type'         => $this->order->order_type,
                'show_rating_prompt' => $this->showRatingPrompt(),
                'table'              => $this->order->table ? [
                    'id'           => $this->order->table->id,
                    'table_number' => $this->order->table->table_number,
                ] : null,
                'updated_at'         => $this->order->updated_at,
            ],
        ];
    }
}
