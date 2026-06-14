<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncInbox extends Model
{
    protected $fillable = [
        'device_id',
        'branch_id',
        'idempotency_key',
        'event_type',
        'model_type',
        'model_id',
        'payload',
        'sync_status',
        'conflict_status',
        'conflict_payload',
        'attempts',
        'last_attempt_at',
        'last_synced_at',
        'last_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'conflict_payload' => 'array',
        'last_attempt_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];
}
