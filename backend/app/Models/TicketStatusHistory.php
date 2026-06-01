<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketStatusHistory extends Model
{
    protected $table = 'TicketStatusHistory';
    protected $primaryKey = 'TicketStatusHistoryNumber';
    public $timestamps = false;

    protected $fillable = [
        'TicketNumber',
        'PreviousStatusNumber',
        'NewStatusNumber',
        'ChangedByUserNumber',
        'CreatedDate',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'TicketNumber', 'TicketNumber');
    }

    public function previousStatus()
    {
        return $this->belongsTo(Status::class, 'PreviousStatusNumber', 'StatusNumber');
    }

    public function newStatus()
    {
        return $this->belongsTo(Status::class, 'NewStatusNumber', 'StatusNumber');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'ChangedByUserNumber', 'UserNumber');
    }
}
