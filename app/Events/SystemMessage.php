<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $message;

    /**
     * @param int    $ticketId
     * @param string $text       The human-readable system notice
     * @param int    $dbId       The real Message::id from the DB (for frontend dedup)
     */
    public function __construct(int $ticketId, string $text, int $dbId)
    {
        $this->message = [
            'id'         => $dbId,             // real DB id — no duplicates on refresh
            'type'       => 'system',
            'message'    => $text,
            'ticket_id'  => $ticketId,
            'created_at' => now()->toISOString(),
        ];
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("ticket.{$this->message['ticket_id']}");
    }

    public function broadcastAs(): string
    {
        return 'system.message';
    }

    public function broadcastWith(): array
    {
        return ['message' => $this->message];
    }
}