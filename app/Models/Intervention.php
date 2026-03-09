<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Intervention extends Model
{
    /** @use HasFactory<\Database\Factories\InterventionsFactory> */
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'interventions';
    public $timestamps = true;
    protected $dates = ['deleted_at','appointment'];
    //  protected $appends = [];
    // protected $with = [];
    protected $fillable = ['note'];
    protected $visible = ['note','appointment'];

    public function ticket()
    {
        return $this->belongsTo(User::class, 'ticket_id');
    }
    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }
}
