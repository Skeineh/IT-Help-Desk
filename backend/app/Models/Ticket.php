<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'Ticket';
    protected $primaryKey = 'TicketNumber';
    public $timestamps = false;

    protected $fillable = [
        'TicketReferenceNumber',
        'Title',
        'Description',
        'CategoryNumber',
        'PriorityNumber',
        'StatusNumber',
        'CreatedByUserNumber',
        'AssignedToUserNumber',
        'IsEscalated',
        'ResolutionNotes',
        'ResolvedDate',
        'CreatedDate',
    ];

    protected $casts = [
        'IsEscalated' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'CreatedByUserNumber', 'UserNumber');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'AssignedToUserNumber', 'UserNumber');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'CategoryNumber', 'CategoryNumber');
    }

    public function priority()
    {
        return $this->belongsTo(Priority::class, 'PriorityNumber', 'PriorityNumber');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'StatusNumber', 'StatusNumber');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class, 'TicketNumber', 'TicketNumber');
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class, 'TicketNumber', 'TicketNumber');
    }

    public function assignmentHistories()
    {
        return $this->hasMany(TicketAssignmentHistory::class, 'TicketNumber', 'TicketNumber');
    }

    public function statusHistories()
    {
        return $this->hasMany(TicketStatusHistory::class, 'TicketNumber', 'TicketNumber');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'TicketNumber', 'TicketNumber');
    }
}
