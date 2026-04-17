<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a technician marks an intervention complete.
 * Notifies:
 *   • The ticket reporter (their ticket is fixed)
 *   • The dispatcher/manager (ticket they assigned is done)
 */
class TicketResolved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $data;
    public array $recipientIds; // [reporter_id, dispatcher_id, ...]

    public function __construct(array $data, array $recipientIds)
    {
        $this->data         = $data;
        $this->recipientIds = array_values(array_unique($recipientIds));
    }

    public function broadcastOn(): array
    {
        return array_map(
            fn($id) => new PrivateChannel("users.{$id}"),
            $this->recipientIds
        );
    }

    public function broadcastAs(): string
    {
        return 'ticket.resolved';
    }

    public function broadcastWith(): array
    {
        return $this->data;
    }
}
