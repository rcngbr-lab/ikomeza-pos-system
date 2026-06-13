<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalNotification extends Model
{
    protected $fillable = [
        'user_id',
        'role_name',
        'module',
        'action_required',
        'reference',
        'status',
        'metadata',
        'read_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'read_at' => 'datetime',
    ];
}

