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
        'PreviousAssignedToUserNumber',
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

    public function previousAssignedTo()
    {
        return $this->belongsTo(User::class, 'PreviousAssignedToUserNumber', 'UserNumber');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'AssignedByUserNumber', 'UserNumber');
    }
}
