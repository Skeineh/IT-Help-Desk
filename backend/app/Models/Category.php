<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'Category';
    protected $primaryKey = 'CategoryNumber';
    public $timestamps = false;

    protected $fillable = [
        'CategoryName',
        'Description',
        'IsActive',
    ];

    protected $casts = [
        'IsActive' => 'boolean',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'CategoryNumber', 'CategoryNumber');
    }
}
