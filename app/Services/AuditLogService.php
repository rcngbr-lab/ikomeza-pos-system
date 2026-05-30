<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AuditLogService
{
    public static function log(
        string $action,
        Model|string|null $model = null,
        ?string $description = null,
        array $context = []
    ): ?AuditLog {
        return self::record(array_merge($context, [
            'action' => $action,
            'model' => $model,
            'description' => $description,
        ]));
    }

    public static function record(array $data): ?AuditLog
    {
        if (!Schema::hasTable('audit_logs')) {
            return null;
        }

        $user = auth()->user();
        $request = request();
        $model = $data['model'] ?? null;
        $action = strtoupper((string) ($data['action'] ?? $data['event'] ?? 'ACTIVITY'));
        $module = self::normalizeModule($data['module'] ?? null, $model, $action);
        $modelType = $data['model_type'] ?? null;
        $modelId = $data['model_id'] ?? null;

        if ($model instanceof Model) {
            $modelType = $modelType ?: $model::class;
            $modelId = $modelId ?: $model->getKey();
            $model = class_basename($model);
        } elseif (is_string($model) && str_contains($model, '\\')) {
            $modelType = $modelType ?: $model;
            $model = class_basename($model);
        }

        $payload = [
            'user_id' => $data['user_id'] ?? $user?->id,
            'role_name' => $data['role_name'] ?? $user?->roleLabel(),
            'department_id' => $data['department_id'] ?? $user?->department_id,
            'branch_id' => $data['branch_id'] ?? $user?->branch_id,
            'event_type' => strtoupper((string) ($data['event_type'] ?? self::eventTypeFor($module, $action))),
            'event' => $data['event'] ?? $action,
            'action' => $action,
            'module' => $module,
            'model' => is_string($model) ? class_basename($model) : null,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'reference' => $data['reference'] ?? null,
            'description' => $data['description'] ?? null,
            'old_values' => $data['old_values'] ?? null,
            'new_values' => $data['new_values'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'amount' => $data['amount'] ?? null,
            'quantity_before' => $data['quantity_before'] ?? null,
            'quantity_changed' => $data['quantity_changed'] ?? null,
            'quantity_after' => $data['quantity_after'] ?? null,
            'severity' => strtoupper((string) ($data['severity'] ?? self::severityFor($module, $action))),
            'ip_address' => $data['ip_address'] ?? $request?->ip(),
            'user_agent' => $data['user_agent'] ?? $request?->userAgent(),
            'device' => $data['device'] ?? self::deviceName($request?->userAgent()),
        ];

        return AuditLog::create(self::onlyExistingColumns($payload));
    }

    private static function onlyExistingColumns(array $data): array
    {
        return collect($data)
            ->filter(fn ($value, $column) => Schema::hasColumn('audit_logs', $column))
            ->all();
    }

    private static function normalizeModule(?string $module, mixed $model, string $action): string
    {
        if ($module) {
            return str($module)->replace('_', ' ')->title()->toString();
        }

        $source = strtoupper(is_string($model) ? $model : ($model instanceof Model ? class_basename($model) : $action));

        return match (true) {
            str_contains($source, 'LOGIN'),
            str_contains($source, 'LOGOUT'),
            str_contains($source, 'PASSWORD'),
            str_contains($source, 'UNAUTHORIZED'),
            str_contains($source, 'SECURITY') => 'Security',
            str_contains($source, 'SALE'),
            str_contains($source, 'RECEIPT'),
            str_contains($source, 'PAYMENT') => 'Sales',
            str_contains($source, 'REFUND') => 'Refunds',
            str_contains($source, 'STOCK'),
            str_contains($source, 'INVENTORY') => 'Inventory',
            str_contains($source, 'REQUISITION') => 'Requisitions',
            str_contains($source, 'PURCHASE') => 'Purchases',
            str_contains($source, 'SUPPLIER') => 'Suppliers',
            str_contains($source, 'SHIFT') => 'Shifts',
            str_contains($source, 'ROLE') => 'Roles',
            str_contains($source, 'PERMISSION') => 'Permissions',
            str_contains($source, 'USER') => 'Users',
            str_contains($source, 'PRODUCT'),
            str_contains($source, 'PRICE') => 'Products',
            default => 'System',
        };
    }

    private static function eventTypeFor(string $module, string $action): string
    {
        $module = strtoupper($module);

        return match (true) {
            $module === 'SECURITY' => 'SECURITY',
            str_contains($action, 'SALE'),
            str_contains($action, 'PAYMENT'),
            str_contains($action, 'REFUND') => 'FINANCIAL',
            str_contains($action, 'STOCK'),
            str_contains($action, 'INVENTORY') => 'STOCK',
            str_contains($action, 'APPROV'),
            str_contains($action, 'REJECT') => 'APPROVAL',
            default => 'AUDIT',
        };
    }

    private static function severityFor(string $module, string $action): string
    {
        $action = strtoupper($action);
        $module = strtoupper($module);

        return match (true) {
            $module === 'SECURITY',
            str_contains($action, 'LOGIN_FAILED'),
            str_contains($action, 'UNAUTHORIZED') => 'SECURITY',
            str_contains($action, 'DELETE'),
            str_contains($action, 'PRICE_CHANGED'),
            str_contains($action, 'PERMISSION'),
            str_contains($action, 'ROLE') => 'WARNING',
            str_contains($action, 'CRITICAL') => 'CRITICAL',
            default => 'INFO',
        };
    }

    private static function deviceName(?string $userAgent): ?string
    {
        if (!$userAgent) {
            return null;
        }

        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Browser',
        };

        $platform = match (true) {
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone'),
            str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Macintosh') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Unknown OS',
        };

        return $browser . ' on ' . $platform;
    }
}
