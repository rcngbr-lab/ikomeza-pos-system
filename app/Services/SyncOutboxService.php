<?php

namespace App\Services;

use App\Models\SyncOutbox;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SyncOutboxService
{
    public function push(string $eventType, ?Model $model = null, array $payload = []): void
    {
        if (!Schema::hasTable('sync_outbox')) {
            return;
        }

        $deviceId = $payload['device_id'] ?? request()?->header('X-Device-Id') ?? config('app.name') . '-server';
        $branchId = $payload['branch_id'] ?? $model?->branch_id ?? auth()->user()?->branch_id;
        $idempotencyKey = $payload['idempotency_key'] ?? hash('sha256', implode('|', [
            strtoupper($eventType),
            $model ? $model::class : 'system',
            $model?->getKey() ?? Str::uuid()->toString(),
            $branchId ?? 'global',
            $payload['created_at'] ?? now()->toIso8601String(),
        ]));

        SyncOutbox::firstOrCreate([
            'idempotency_key' => $idempotencyKey,
        ], [
            'event_type' => strtoupper($eventType),
            'model_type' => $model ? $model::class : null,
            'model_id' => $model?->getKey(),
            'device_id' => $deviceId,
            'branch_id' => $branchId,
            'idempotency_key' => $idempotencyKey,
            'payload' => $payload,
            'status' => 'PENDING',
            'sync_status' => 'PENDING',
            'next_attempt_at' => now(),
            'conflict_status' => 'NONE',
        ]);
    }
}
