<?php 

namespace App\Services;

use App\Events\NotificationSent;
use App\Models\Notification;

class NotificationService
{
    /**
     * Create and broadcast a notification to a user.
     */
    public static function send(
        int    $userId,
        string $title,
        string $message,
        string $type        = 'info',
        mixed  $related     = null,
    ): Notification {
        $notification = Notification::create([
            'user_id'      => $userId,
            'title'        => $title,
            'message'      => $message,
            'type'         => $type,
            'related_id'   => $related?->id,
            'related_type' => $related ? get_class($related) : null,
        ]);

        broadcast(new NotificationSent($notification))->toOthers();

        return $notification;
    }
}