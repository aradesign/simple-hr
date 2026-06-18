<?php

namespace App\Services\Interview;

use App\Domain\Enums\ApplicationStatus;
use App\Domain\Enums\CalendarEventType;
use App\Domain\Enums\InterviewResult;
use App\Domain\Enums\InterviewStatus;
use App\Domain\Events\InterviewScheduled;
use App\DTOs\InterviewData;
use App\Models\Interview;
use App\Services\Calendar\CalendarService;
use App\Services\Recruitment\ApplicationService;
use Illuminate\Support\Facades\DB;

class InterviewService
{
    public function __construct(
        private readonly CalendarService $calendarService,
        private readonly ApplicationService $applicationService,
    ) {}

    public function schedule(InterviewData $data): Interview
    {
        return DB::transaction(function () use ($data) {
            $interview = Interview::query()->create(array_merge($data->toArray(), [
                'status' => InterviewStatus::Scheduled,
            ]));

            $this->syncCalendarEvent($interview);

            if ($interview->employment_application_id) {
                $application = $interview->employmentApplication;
                if ($application) {
                    $this->applicationService->updateStatus(
                        $application,
                        ApplicationStatus::InterviewScheduled,
                    );
                }
            }

            event(new InterviewScheduled($interview));

            return $interview->fresh(['person', 'interviewer', 'employmentApplication']);
        });
    }

    public function complete(Interview $interview, InterviewResult $result, ?string $feedback = null): Interview
    {
        return DB::transaction(function () use ($interview, $result, $feedback) {
            $interview->update([
                'status' => InterviewStatus::Completed,
                'result' => $result,
                'feedback' => $feedback,
            ]);

            if ($interview->employment_application_id) {
                $application = $interview->employmentApplication;
                if ($application) {
                    $newStatus = match ($result) {
                        InterviewResult::Passed => ApplicationStatus::Accepted,
                        InterviewResult::Failed => ApplicationStatus::Rejected,
                        default => ApplicationStatus::Interviewed,
                    };

                    $this->applicationService->updateStatus($application, $newStatus);
                }
            }

            return $interview->fresh();
        });
    }

    public function syncCalendarEvent(Interview $interview): void
    {
        $startsAt = $interview->scheduled_at;
        $endsAt = $startsAt?->copy()->addMinutes($interview->duration_minutes ?? 60);

        $personName = $interview->person?->full_name ?? 'متقاضی';

        $payload = [
            'title' => "مصاحبه — {$personName}",
            'description' => $interview->notes,
            'event_type' => CalendarEventType::Interview,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'all_day' => false,
            'person_id' => $interview->person_id,
            'interview_id' => $interview->id,
            'created_by' => $interview->interviewer_id,
        ];

        $existingEvent = $interview->calendarEvents()->first();

        if ($existingEvent) {
            $this->calendarService->update($existingEvent, $payload);
        } else {
            $this->calendarService->create($payload);
        }
    }

    public function delete(Interview $interview): void
    {
        $interview->calendarEvents()->each(
            fn ($event) => $this->calendarService->delete($event),
        );

        $interview->delete();
    }
}
