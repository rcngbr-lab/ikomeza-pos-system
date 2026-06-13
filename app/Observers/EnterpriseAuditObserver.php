<?php

namespace App\Observers;

use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;

class EnterpriseAuditObserver
{
    public function created(Model $model): void
    {
        $this->record('CREATED', $model, null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $changes = collect($model->getChanges())
            ->except(['updated_at'])
            ->all();

        if ($changes === []) {
            return;
        }

        $old = collect($changes)
            ->mapWithKeys(fn ($value, $key) => [$key => $model->getOriginal($key)])
            ->all();

        $this->record('UPDATED', $model, $old, $changes);
    }

    public function deleted(Model $model): void
    {
        $this->record('DELETED', $model, $model->getOriginal(), null);
    }

    private function record(string $action, Model $model, ?array $oldValues, ?array $newValues): void
    {
        AuditLogService::record([
            'action' => strtoupper(class_basename($model)) . '_' . $action,
            'module' => class_basename($model),
            'model' => $model,
            'description' => class_basename($model) . ' ' . strtolower($action) . ' #' . $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'reference' => $this->reference($model),
            'severity' => $action === 'DELETED' ? 'WARNING' : 'INFO',
        ]);
    }

    private function reference(Model $model): ?string
    {
        foreach (['receipt_no', 'product_code', 'purchase_number', 'count_number', 'customer_code', 'table_code', 'email', 'name'] as $column) {
            if (isset($model->{$column})) {
                return (string) $model->{$column};
            }
        }

        return null;
    }
}

