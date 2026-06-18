<?php

namespace App\Listeners;

use App\Domain\Events\InterviewScheduled;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Morilog\Jalali\Jalalian;

class SendInterviewNotification implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(InterviewScheduled $event): void
    {
        $interview = $event->interview->loadMissing(['person.user', 'interviewer']);
        $person = $interview->person;

        if (! $person) {
            return;
        }

        $date = $interview->scheduled_at
            ? Jalalian::fromDateTime($interview->scheduled_at)->format('Y/m/d')
            : 'نامشخص';
        $time = $interview->scheduled_at
            ? Jalalian::fromDateTime($interview->scheduled_at)->format('H:i')
            : 'نامشخص';
        $location = $interview->location ?: ($interview->meeting_url ?: 'اعلام می‌شود');

        $candidateVars = [
            'date' => $date,
            'time' => $time,
            'name' => $person->full_name,
            'location' => $location,
        ];

        $subject = 'زمان‌بندی مصاحبه';
        $body = sprintf('مصاحبه شما برای تاریخ %s ساعت %s برنامه‌ریزی شد.', $date, $time);

        if ($person->user) {
            $this->notificationService->sendInApp($person->user, $subject, $body);
        }

        if ($person->mobile) {
            $this->notificationService->sendActionSms(
                $person->user,
                $person->mobile,
                'interview_scheduled_candidate',
                $candidateVars,
            );
        }

        if ($interview->interviewer) {
            $interviewerBody = sprintf(
                'مصاحبه با %s در تاریخ %s ساعت %s برنامه‌ریزی شد.',
                $person->full_name,
                $date,
                $time,
            );

            $this->notificationService->sendInApp(
                $interview->interviewer,
                'مصاحبه جدید',
                $interviewerBody,
            );

            if ($interview->interviewer->mobile) {
                $this->notificationService->sendActionSms(
                    $interview->interviewer,
                    $interview->interviewer->mobile,
                    'interview_scheduled_interviewer',
                    [
                        'date' => $date,
                        'time' => $time,
                        'candidate_name' => $person->full_name,
                        'location' => $location,
                    ],
                );
            }
        }
    }
}
