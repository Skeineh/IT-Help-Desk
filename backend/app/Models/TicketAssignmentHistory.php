<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketAssignmentHistory extends Model
{
    protected $table = 'TicketAssignmentHistory';
    protected $primaryKey = 'TicketAssignmentHistoryNumber';
    public $timestamps = false;

    protected $fillable = [
        'TicketNumber',
        'AssignedToUserNumber',
        'AssignedByUserNumber',
        'CreatedDate',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'TicketNumber', 'TicketNumber');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'AssignedToUserNumber', 'UserNumber');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'AssignedByUserNumber', 'UserNumber');
    }
}
