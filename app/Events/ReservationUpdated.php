<?php

namespace App\Events;

use App\Models\Reservation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a reservation's status is updated (confirmed or cancelled).
 *
 * Broadcast on the private `admin` channel (for admin dashboard) and
 * on the private `customer.{userId}` channel (for the customer who made the reservation).
 *
 * Validates: Requirements 14.3, 14.4, 17.2
 */
class ReservationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Reservation $reservation,
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
            new PrivateChannel('admin'),
        ];

        // Notify the customer on their private channel
        if ($this->reservation->user_id) {
            $channels[] = new PrivateChannel('customer.' . $this->reservation->user_id);
        }

        return $channels;
    }

    /**
     * The event name used on the client side.
     */
    public function broadcastAs(): string
    {
        return 'reservation.updated';
    }

    /**
     * Data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->reservation->load(['table', 'user']);

        return [
            'reservation' => [
                'id'              => $this->reservation->id,
                'status'          => $this->reservation->status,
                'previous_status' => $this->previousStatus,
                'new_status'      => $this->newStatus,
                'date'            => $this->reservation->date?->toDateString(),
                'time'            => $this->reservation->time,
                'number_of_people' => $this->reservation->number_of_people,
                'notes'           => $this->reservation->notes,
                'table'           => $this->reservation->table ? [
                    'id'           => $this->reservation->table->id,
                    'table_number' => $this->reservation->table->table_number,
                ] : null,
                'user'            => $this->reservation->user ? [
                    'id'   => $this->reservation->user->id,
                    'name' => $this->reservation->user->name,
                ] : null,
                'updated_at'      => $this->reservation->updated_at,
            ],
        ];
    }
}
