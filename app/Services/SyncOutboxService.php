<?php

namespace App\Services;

use App\Models\SyncOutbox;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class SyncOutboxService
{
    public function push(string $eventType, ?Model $model = null, array $payload = []): void
    {
        if (!Schema::hasTable('sync_outbox')) {
            return;
        }

        SyncOutbox::create([
            'event_type' => strtoupper($eventType),
            'model_type' => $model ? $model::class : null,
            'model_id' => $model?->getKey(),
            'payload' => $payload,
            'status' => 'PENDING',
        ]);
    }
}

