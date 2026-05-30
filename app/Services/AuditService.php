<?php

namespace App\Services;

class AuditService
{
    public static function log(
        string $event,
        ?string $model = null,
        ?string $description = null,
        ?int $modelId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $severity = 'INFO',
        array $context = []
    ): void {
        AuditLogService::record(array_merge($context, [
            'action' => $event,
            'event' => strtoupper($event),
            'model' => $model,
            'model_id' => $modelId,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'severity' => $severity,
        ]));
    }
}
