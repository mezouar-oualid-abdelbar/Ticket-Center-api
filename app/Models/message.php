<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class message extends Model
{
    use HasFactory;
    
    /** @use HasFactory<\Database\Factories\MessageFactory> */
    protected $fillable = [
        'msg_content',
    ];
    protected $visible = ['chat_id'];

    
}
