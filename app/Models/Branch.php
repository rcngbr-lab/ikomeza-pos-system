<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [

        'name',
        'code',
        'location',
        'phone',
        'email',
        'manager_id',
        'status',
        'currency',
        'city',
        'country',

    ];
}