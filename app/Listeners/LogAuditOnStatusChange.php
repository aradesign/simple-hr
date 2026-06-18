<?php

namespace App\Listeners;

use App\Domain\Events\ApplicationStatusChanged;
use App\Domain\Events\PersonStatusChanged;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogAuditOnStatusChange implements ShouldQueue
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function handle(ApplicationStatusChanged|PersonStatusChanged $event): void
    {
        if ($event instanceof ApplicationStatusChanged) {
            $this->auditService->logStatusChange(
                auditable: $event->application,
                oldStatus: $event->oldStatus->value,
                newStatus: $event->newStatus->value,
                user: $event->changedBy,
            );

            return;
        }

        $this->auditService->logStatusChange(
            auditable: $event->person,
            oldStatus: $event->oldStatus->value,
            newStatus: $event->newStatus->value,
            user: $event->changedBy,
        );
    }
}
