<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a new order is created.
 *
 * Broadcast to the `orders` channel so Admin and Chef are notified.
 *
 * Validates: Requirements 5.7, 17.1
 */
class OrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Order $order)
    {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('orders'),
        ];
    }

    /**
     * The event name used on the client side.
     */
    public function broadcastAs(): string
    {
        return 'order.created';
    }

    /**
     * Data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->order->load(['orderItems.menu', 'table']);

        return [
            'order' => [
                'id'           => $this->order->id,
                'order_number' => $this->order->order_number,
                'order_status' => $this->order->order_status,
                'order_type'   => $this->order->order_type,
                'total_price'  => $this->order->total_price,
                'payment_status' => $this->order->payment_status,
                'payment_method' => $this->order->payment_method,
                'table'        => $this->order->table ? [
                    'id'           => $this->order->table->id,
                    'table_number' => $this->order->table->table_number,
                ] : null,
                'items'        => $this->order->orderItems->map(fn ($item) => [
                    'id'               => $item->id,
                    'menu_id'          => $item->menu_id,
                    'menu_name'        => $item->menu?->name,
                    'menu'             => $item->menu ? [
                        'id'   => $item->menu->id,
                        'name' => $item->menu->name,
                    ] : null,
                    'quantity'         => $item->quantity,
                    'variant_selected' => $item->variant_selected,
                    'note'             => $item->note,
                ])->values(),
                'items_count'  => $this->order->orderItems->count(),
                'created_at'   => $this->order->created_at,
            ],
        ];
    }
}
