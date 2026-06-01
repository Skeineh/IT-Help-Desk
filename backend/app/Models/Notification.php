<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'Notification';
    protected $primaryKey = 'NotificationNumber';
    public $timestamps = false;

    protected $fillable = [
        'UserNumber',
        'TicketNumber',
        'NotificationType',
        'Title',
        'Message',
        'IsRead',
        'ReadDate',
        'CreatedDate',
    ];

    protected $casts = [
        'IsRead' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'UserNumber', 'UserNumber');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'TicketNumber', 'TicketNumber');
    }
}
