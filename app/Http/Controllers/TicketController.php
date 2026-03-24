<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;

class TicketController extends Controller
{
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
    protected $connection = 'mysql';
    protected $table = 'tickets';
    public $timestamps = true;
    protected $dates = ['deleted_at' ,'completed_at'];
    //  protected $appends = [];
    // protected $with = [];
    protected $fillable = [
        'title',
        'reporter_id',
        'description',
        'status',
        'priority',
        'completed_at',
    ];
    protected $visible = [
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

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assigments()
    {
        return $this->hasmany(Assignment::class, );
    }

    public function masseges()
    {
        return $this->hasmany(Massege::class, );
    }

}
}