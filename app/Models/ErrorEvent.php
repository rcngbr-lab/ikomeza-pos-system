<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorEvent extends Model
{
    protected $fillable = [
        'user_id',
        'branch_id',
        'source',
        'severity',
        'message',
        'context',
        'status',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];
}
