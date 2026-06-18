<?php

namespace App\Listeners;

use App\Domain\Enums\PersonLifecycleStatus;
use App\Domain\Events\PersonStatusChanged;
use App\Services\Notification\NotificationService;

class SendEmployeeWelcomeNotification
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(PersonStatusChanged $event): void
    {
        if ($event->newStatus !== PersonLifecycleStatus::Employee) {
            return;
        }

        $this->notificationService->sendInAppToPerson(
            $event->person,
            'خوش آمدید',
            'پروفایل پرسنلی شما فعال شد. می‌توانید اطلاعات خود را تکمیل کنید و از بخش تیکت‌ها با منابع انسانی در ارتباط باشید.',
        );
    }
}
