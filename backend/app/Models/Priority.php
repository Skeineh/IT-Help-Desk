<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Priority extends Model
{
    protected $table = 'Priority';
    protected $primaryKey = 'PriorityNumber';
    public $timestamps = false;

    protected $fillable = [
        'PriorityName',
        'PriorityLevel',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'PriorityNumber', 'PriorityNumber');
    }
}
