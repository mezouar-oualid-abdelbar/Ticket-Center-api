<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\PostStatus;
use App\Enums\PostPriority;

class Ticket extends Model
{
    /** @use HasFactory<\Database\Factories\TicketFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'reporter_id',
        'description',
        'status',
        'priority',
        'completed_at',
    ];

    // --- Add enum casts ---
    protected $casts = [
        'status' => PostStatus::class,
        'priority' => PostPriority::class,
        'completed_at' => 'datetime',
    ];
}
