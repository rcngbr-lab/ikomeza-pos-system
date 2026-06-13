<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncOutbox extends Model
{
    protected $table = 'sync_outbox';

    protected $fillable = [
        'event_type',
        'model_type',
        'model_id',
        'payload',
        'status',
        'attempts',
        'last_attempt_at',
        'last_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'last_attempt_at' => 'datetime',
    ];
}

