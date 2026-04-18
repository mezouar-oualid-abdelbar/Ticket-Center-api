<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to each ticket participant's personal channel when a new message arrives.
 * Drives the nav-bar envelope badge and OS browser notifications.
 *
 * Frontend listens on:  echo.private(`users.${userId}`).listen('.message.received', ...)
 */
class NewMessageNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int   $userId;
    public array $data;

    public function __construct(int $userId, array $data)
    {
        $this->userId = $userId;
        $this->data   = $data;
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("users.{$this->userId}")];
    }

    public function broadcastAs(): string
    {
        return 'message.received';
    }

    public function broadcastWith(): array
    {
        return $this->data;
    }
}
