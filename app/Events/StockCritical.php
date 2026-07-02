<?php

namespace App\Events;

use App\Models\Inventory;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when an ingredient reaches critical stock level (current_stock ≤ min_stock).
 *
 * Broadcast on the private `admin` channel so only admin users receive it.
 *
 * Validates: Requirements 9.4, 17.3
 */
class StockCritical implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Inventory $ingredient)
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
            new PrivateChannel('admin'),
        ];
    }

    /**
     * The event name used on the client side.
     */
    public function broadcastAs(): string
    {
        return 'stock.critical';
    }

    /**
     * Data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'ingredient' => [
                'id'            => $this->ingredient->id,
                'ingredient_name' => $this->ingredient->ingredient_name,
                'unit'          => $this->ingredient->unit,
                'current_stock' => $this->ingredient->current_stock,
                'min_stock'     => $this->ingredient->min_stock,
                'supplier'      => $this->ingredient->supplier,
            ],
        ];
    }
}
