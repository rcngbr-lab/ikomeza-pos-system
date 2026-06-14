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
        'device_id',
        'branch_id',
        'idempotency_key',
        'payload',
        'status',
        'sync_status',
        'attempts',
        'next_attempt_at',
        'last_attempt_at',
        'last_synced_at',
        'last_error',
        'conflict_status',
    ];

    protected $casts = [
        'payload' => 'array',
        'next_attempt_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];
}
