<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    /** @use HasFactory<\Database\Factories\MessageFactory> */

    protected $connection = 'mysql';
    protected $table = 'messages';
    public $timestamps = true;
    // protected $dates = ['deleted_at'];
    //  protected $appends = [];
    // protected $with = [];
    protected $fillable = ['message'];
    protected $visible = ['message'];

    public function ticket()
    {
        return $this->belongsTo(User::class, 'ticket_id');
    }
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

}
