<?php

namespace App\Listeners;

use App\Domain\Enums\ApplicationStatus;
use App\Domain\Events\ApplicationStatusChanged;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendApplicationStatusNotification implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(ApplicationStatusChanged $event): void
    {
        $application = $event->application->loadMissing('person.user');
        $person = $application->person;

        if (! $person) {
            return;
        }

        $vars = [
            'number' => $application->application_number,
            'status' => $event->newStatus->label(),
            'old_status' => $event->oldStatus->label(),
            'name' => $person->full_name,
        ];

        $actionKey = match ($event->newStatus) {
            ApplicationStatus::Submitted => 'application_submitted',
            ApplicationStatus::Accepted => 'application_accepted',
            ApplicationStatus::Rejected => 'application_rejected',
            default => 'application_status',
        };

        $subject = 'تغییر وضعیت درخواست استخدام';
        $body = sprintf(
            'وضعیت درخواست %s از «%s» به «%s» تغییر یافت.',
            $vars['number'],
            $vars['old_status'],
            $vars['status'],
        );

        if ($person->user) {
            $this->notificationService->sendInApp($person->user, $subject, $body);
        }

        if ($person->mobile) {
            $this->notificationService->sendActionSms($person->user, $person->mobile, $actionKey, $vars);
        }
    }
}
