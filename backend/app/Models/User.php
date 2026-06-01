<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    protected $table = 'User';
    protected $primaryKey = 'UserNumber';
    public $timestamps = false;

    protected $fillable = [
        'RoleNumber',
        'FullName',
        'Email',
        'PasswordHash',
        'PhoneNumber',
        'Department',
        'IsActive',
        'LastLoginDate',
        'CreatedDate',
    ];

    protected $hidden = [
        'PasswordHash',
    ];

    protected $casts = [
        'IsActive' => 'boolean',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'RoleNumber', 'RoleNumber');
    }

    public function createdTickets()
    {
        return $this->hasMany(Ticket::class, 'CreatedByUserNumber', 'UserNumber');
    }

    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'AssignedToUserNumber', 'UserNumber');
    }

    public function ticketComments()
    {
        return $this->hasMany(TicketComment::class, 'UserNumber', 'UserNumber');
    }

    public function ticketAttachments()
    {
        return $this->hasMany(TicketAttachment::class, 'UploadedByUserNumber', 'UserNumber');
    }

    public function assignmentsMade()
    {
        return $this->hasMany(TicketAssignmentHistory::class, 'AssignedByUserNumber', 'UserNumber');
    }

    public function assignmentsReceived()
    {
        return $this->hasMany(TicketAssignmentHistory::class, 'AssignedToUserNumber', 'UserNumber');
    }

    public function statusChanges()
    {
        return $this->hasMany(TicketStatusHistory::class, 'ChangedByUserNumber', 'UserNumber');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'UserNumber', 'UserNumber');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'UserNumber', 'UserNumber');
    }
}
