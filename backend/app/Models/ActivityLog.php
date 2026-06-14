<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'ActivityLog';
    protected $primaryKey = 'ActivityLogNumber';
    public $timestamps = false;

    protected $fillable = [
        'UserNumber',
        'ActionType',
        'EntityType',
        'EntityReferenceNumber',
        'ActionDescription',
        'OldValue',
        'NewValue',
        'IpAddress',
        'CreatedDate',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'UserNumber', 'UserNumber');
    }
}
