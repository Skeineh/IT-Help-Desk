<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    protected $table = 'Status';
    protected $primaryKey = 'StatusNumber';
    public $timestamps = false;

    protected $fillable = [
        'StatusName',
        'Description',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'StatusNumber', 'StatusNumber');
    }

    public function previousStatusHistories()
    {
        return $this->hasMany(TicketStatusHistory::class, 'PreviousStatusNumber', 'StatusNumber');
    }

    public function newStatusHistories()
    {
        return $this->hasMany(TicketStatusHistory::class, 'NewStatusNumber', 'StatusNumber');
    }
}
