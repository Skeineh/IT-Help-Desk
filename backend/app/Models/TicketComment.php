<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketComment extends Model
{
    protected $table = 'TicketComment';
    protected $primaryKey = 'TicketCommentNumber';
    public $timestamps = false;

    protected $fillable = [
        'TicketNumber',
        'UserNumber',
        'CommentText',
        'IsInternalNote',
        'CreatedDate',
    ];

    protected $casts = [
        'IsInternalNote' => 'boolean',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'TicketNumber', 'TicketNumber');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'UserNumber', 'UserNumber');
    }
}
