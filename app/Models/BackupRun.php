<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupRun extends Model
{
    protected $fillable = [
        'backup_name',
        'branch_id',
        'path',
        'size_bytes',
        'status',
        'created_by',
        'verified_at',
        'failure_type',
        'disk',
        'notes',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];
}
