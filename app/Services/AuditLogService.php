<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogService
{
    public static function log(
        $action,
        $model = null,
        $description = null
    )
    {
        AuditLog::create([

            'user_id' => auth()->id(),

            'action' => $action,

            'model_type' => $model
                ? get_class($model)
                : null,

            'model_id' => $model->id ?? null,

            'description' => $description

        ]);
    }
}