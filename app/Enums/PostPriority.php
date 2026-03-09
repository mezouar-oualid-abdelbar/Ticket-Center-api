<?php

namespace App\Enums;

enum PostPriority: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public static function values(): array
    {
        return array_map(fn($priority) => $priority->value, self::cases());
    }

}
