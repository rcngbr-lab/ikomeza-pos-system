<?php

namespace App\Services;

use App\Models\SyncInbox;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OfflineSyncService
{
    public function receive(array $event): SyncInbox
    {
        foreach (['device_id', 'idempotency_key', 'event_type'] as $required) {
            if (blank($event[$required] ?? null)) {
                throw new \InvalidArgumentException($required . ' is required for sync events.');
            }
        }

        return DB::transaction(function () use ($event) {
            $existing = SyncInbox::where('idempotency_key', $event['idempotency_key'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $conflict = $this->detectConflict($event);

            return SyncInbox::create([
                'device_id' => $event['device_id'],
                'branch_id' => $event['branch_id'] ?? null,
                'idempotency_key' => $event['idempotency_key'],
                'event_type' => strtoupper($event['event_type']),
                'model_type' => $event['model_type'] ?? null,
                'model_id' => $event['model_id'] ?? null,
                'payload' => $event['payload'] ?? [],
                'sync_status' => $conflict ? 'CONFLICT' : 'PENDING',
                'conflict_status' => $conflict ? 'OPEN' : 'NONE',
                'conflict_payload' => $conflict,
            ]);
        });
    }

    private function detectConflict(array $event): ?array
    {
        $modelType = $event['model_type'] ?? null;
        $modelId = $event['model_id'] ?? null;

        if (!$modelType || !$modelId || !is_subclass_of($modelType, Model::class)) {
            return null;
        }

        $model = $modelType::query()->find($modelId);

        if (!$model) {
            return null;
        }

        $incomingBranch = $event['branch_id'] ?? null;
        $currentBranch = $model->branch_id ?? null;

        if ($incomingBranch && $currentBranch && (int) $incomingBranch !== (int) $currentBranch) {
            return [
                'rule' => 'BRANCH_MISMATCH',
                'current_branch_id' => $currentBranch,
                'incoming_branch_id' => $incomingBranch,
            ];
        }

        $incomingUpdatedAt = data_get($event, 'payload.updated_at');

        if ($incomingUpdatedAt && $model->updated_at && strtotime($incomingUpdatedAt) < $model->updated_at->getTimestamp()) {
            return [
                'rule' => 'STALE_UPDATE',
                'current_updated_at' => $model->updated_at->toDateTimeString(),
                'incoming_updated_at' => $incomingUpdatedAt,
            ];
        }

        return null;
    }
}
