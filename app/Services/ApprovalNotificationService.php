<?php

namespace App\Services;

use App\Models\ApprovalNotification;
use Illuminate\Support\Facades\Schema;

class ApprovalNotificationService
{
    public function create(string $module, string $actionRequired, string $reference, array $metadata = []): void
    {
        if (!Schema::hasTable('approval_notifications')) {
            return;
        }

        ApprovalNotification::create([
            'role_name' => 'Manager',
            'module' => $module,
            'action_required' => $actionRequired,
            'reference' => $reference,
            'metadata' => $metadata,
            'status' => 'UNREAD',
        ]);
    }
}

