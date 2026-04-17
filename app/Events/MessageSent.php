<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int   $ticketId;
    public array $message;

    public function __construct(int $ticketId, array $message)
    {
        $this->ticketId = $ticketId;
        $this->message  = $message;
    }

    /**
     * All participants of the ticket share one private channel.
     * Channel auth is handled in routes/channels.php
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("ticket.{$this->ticketId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return ['message' => $this->message];
    }
}
