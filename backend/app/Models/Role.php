<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'Role';
    protected $primaryKey = 'RoleNumber';
    public $timestamps = false;

    protected $fillable = [
        'RoleName',
        'Description',
        'CreatedDate',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'RoleNumber', 'RoleNumber');
    }
}
