<?php

namespace App\Enums;

enum PostStatus: string
{
    case OPEN = 'open';
    case ASSIGNED = 'assigned';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    public static function values(): array
    {
        return array_map(fn($status) => $status->value, self::cases());
    }

}
