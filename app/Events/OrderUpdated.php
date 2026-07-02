<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Order $order)
    {
    }

    /**
     * @return array<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('orders'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->order->load(['orderItems.menu', 'table']);

        return [
            'order' => [
                'id'              => $this->order->id,
                'order_number'    => $this->order->order_number,
                'order_status'    => $this->order->order_status,
                'order_type'      => $this->order->order_type,
                'total_price'     => $this->order->total_price,
                'discount_amount' => $this->order->discount_amount,
                'tax_amount'      => $this->order->tax_amount,
                'service_charge'  => $this->order->service_charge,
                'payment_status'  => $this->order->payment_status,
                'payment_method'  => $this->order->payment_method,
                'notes'           => $this->order->notes,
                'table'           => $this->order->table ? [
                    'id'           => $this->order->table->id,
                    'table_number' => $this->order->table->table_number,
                    'status'       => $this->order->table->status,
                ] : null,
                'items'           => $this->order->orderItems->map(fn ($item) => [
                    'id'               => $item->id,
                    'menu_id'          => $item->menu_id,
                    'menu_name'        => $item->menu?->name,
                    'menu'             => $item->menu ? [
                        'id'   => $item->menu->id,
                        'name' => $item->menu->name,
                    ] : null,
                    'quantity'         => $item->quantity,
                    'price_at_time'    => $item->price_at_time,
                    'variant_selected' => $item->variant_selected,
                    'note'             => $item->note,
                    'subtotal'         => round((float) $item->price_at_time * $item->quantity, 2),
                ])->values(),
                'items_count'     => $this->order->orderItems->count(),
                'created_at'      => $this->order->created_at,
                'updated_at'      => $this->order->updated_at,
            ],
        ];
    }
}
