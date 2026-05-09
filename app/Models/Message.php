<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table     = 'messages';
    public    $timestamps = true;

    protected $fillable = [
        'ticket_id',
        'sender_id',
        'message',
    ];

    protected $visible = [
        'id',
        'ticket_id',
        'sender_id',
        'message',
        'created_at',
        'sender',        // appended via relationship
    ];

    // ── Relations ──────────────────────────────────────────────

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
