<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupRun extends Model
{
    protected $fillable = [
        'backup_name',
        'path',
        'size_bytes',
        'status',
        'created_by',
        'notes',
    ];
}

