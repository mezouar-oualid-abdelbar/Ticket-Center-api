<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a manager assigns a ticket to technicians.
 * Each technician receives a notification on their private channel.
 */
class TicketAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $data;
    public array $recipientIds; // technician user IDs

    public function __construct(array $data, array $recipientIds)
    {
        $this->data         = $data;
        $this->recipientIds = $recipientIds;
    }

    /**
     * Broadcast on each technician's private channel.
     */
    public function broadcastOn(): array
    {
        return array_map(
            fn($id) => new PrivateChannel("users.{$id}"),
            $this->recipientIds
        );
    }

    public function broadcastAs(): string
    {
        return 'ticket.assigned';
    }

    public function broadcastWith(): array
    {
        return $this->data;
    }
}
