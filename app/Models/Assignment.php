<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    /** @use HasFactory<\Database\Factories\AssignmentFactory> */
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'assignments';
    public $timestamps = true;
    protected $dates = ['deleted_at'];
    //  protected $appends = [];
    // protected $with = [];
    // protected $fillable = [];
    // protected $visible = [];

    public function ticket()
    {
        return $this->belongsTo(User::class, 'ticket_id');
    }
    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }
    public function dispatcher()
    {
        return $this->belongsTo(User::class, 'dispatcher_id');
    }

}
