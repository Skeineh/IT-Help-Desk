<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketAttachment extends Model
{
    protected $table = 'TicketAttachment';
    protected $primaryKey = 'TicketAttachmentNumber';
    public $timestamps = false;

    protected $fillable = [
        'TicketNumber',
        'UploadedByUserNumber',
        'FileName',
        'FilePath',
        'FileSize',
        'CreatedDate',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'TicketNumber', 'TicketNumber');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'UploadedByUserNumber', 'UserNumber');
    }
}
