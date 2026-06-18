<?php

namespace App\Services\Audit;

use App\Domain\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public function log(
        AuditAction $action,
        ?Model $auditable = null,
        ?User $user = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => $user?->id ?? auth()->id(),
            'auditable_type' => $auditable ? $auditable->getMorphClass() : null,
            'auditable_id' => $auditable?->getKey(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public function logStatusChange(
        Model $auditable,
        string $oldStatus,
        string $newStatus,
        ?User $user = null,
    ): AuditLog {
        return $this->log(
            action: AuditAction::StatusChanged,
            auditable: $auditable,
            user: $user,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $newStatus],
        );
    }
}
