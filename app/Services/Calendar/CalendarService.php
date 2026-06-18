<?php

namespace App\Services\Calendar;

use App\Domain\Enums\CalendarEventType;
use App\Domain\Enums\EmploymentStatus;
use App\Domain\Enums\PersonLifecycleStatus;
use App\Models\CalendarEvent;
use App\Models\EmploymentRecord;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CalendarService
{
    private const CONTRACT_RENEWAL_REMINDER_DAYS = 30;

    public function create(array $data): CalendarEvent
    {
        return CalendarEvent::query()->create($data);
    }

    public function update(CalendarEvent $event, array $data): CalendarEvent
    {
        $event->update($data);

        return $event->fresh();
    }

    public function delete(CalendarEvent $event): void
    {
        $event->delete();
    }

    public function find(int $id): ?CalendarEvent
    {
        return CalendarEvent::query()->find($id);
    }

    public function getTodayEvents(): Collection
    {
        return CalendarEvent::query()
            ->today()
            ->orderBy('starts_at')
            ->get();
    }

    public function getMonthEvents(int $jalaliYear, int $jalaliMonth): Collection
    {
        [$start, $end] = \App\Helpers\JalaliHelper::monthRange($jalaliYear, $jalaliMonth);

        return CalendarEvent::query()
            ->with(['person', 'creator'])
            ->whereBetween('starts_at', [$start, $end])
            ->orderBy('starts_at')
            ->get();
    }

    public function syncPersonBirthday(Person $person, ?int $createdBy = null): void
    {
        $existing = $person->calendarEvents()
            ->where('event_type', CalendarEventType::Birthday)
            ->first();

        if (! $this->shouldShowBirthdayOnCalendar($person)) {
            if ($existing) {
                $this->delete($existing);
            }

            return;
        }

        if (! $person->birth_date) {
            if ($existing) {
                $this->delete($existing);
            }

            return;
        }

        $birthdayThisYear = $person->birth_date->copy()->year(now()->year);

        $payload = [
            'title' => "تولد {$person->full_name}",
            'description' => 'رویداد تولد پرسنل',
            'event_type' => CalendarEventType::Birthday,
            'starts_at' => $birthdayThisYear->startOfDay(),
            'ends_at' => $birthdayThisYear->endOfDay(),
            'all_day' => true,
            'person_id' => $person->id,
            'color' => '#f59e0b',
        ];

        if ($existing) {
            $this->update($existing, $payload);

            return;
        }

        $creatorId = $createdBy ?? auth()->id();
        $createPayload = $payload;

        if ($creatorId !== null) {
            $createPayload['created_by'] = $creatorId;
        }

        $this->create($createPayload);
    }

    public function syncEmploymentRecordEvents(EmploymentRecord $record, ?int $createdBy = null): void
    {
        $record->loadMissing('person');

        $this->syncEmploymentDateEvent(
            $record,
            CalendarEventType::ProbationEnd,
            $record->probation_end_date,
            "پایان دوره آزمایشی — {$record->person?->full_name}",
            'یادآوری پایان دوره آزمایشی پرسنل',
            '#f59e0b',
            $createdBy,
        );

        $this->syncEmploymentDateEvent(
            $record,
            CalendarEventType::ContractEnd,
            $record->contract_end_date,
            "پایان قرارداد — {$record->person?->full_name}",
            'یادآوری پایان قرارداد همکاری',
            '#ef4444',
            $createdBy,
        );

        $renewalDate = $record->contract_end_date?->copy()->subDays(self::CONTRACT_RENEWAL_REMINDER_DAYS);

        if ($renewalDate && $renewalDate->lt(now()->startOfDay())) {
            $renewalDate = null;
        }

        $this->syncEmploymentDateEvent(
            $record,
            CalendarEventType::ContractRenewal,
            $renewalDate,
            "موعد تمدید قرارداد — {$record->person?->full_name}",
            '۳۰ روز مانده به پایان قرارداد — پیگیری تمدید',
            '#22c55e',
            $createdBy,
        );
    }

    public function removeEmploymentRecordEvents(EmploymentRecord $record): void
    {
        CalendarEvent::query()
            ->where('employment_record_id', $record->id)
            ->each(fn (CalendarEvent $event) => $this->delete($event));
    }

    public function syncAllEmploymentRecordEvents(): int
    {
        $count = 0;

        EmploymentRecord::query()
            ->with('person')
            ->each(function (EmploymentRecord $record) use (&$count) {
                $this->syncEmploymentRecordEvents($record);
                $count++;
            });

        return $count;
    }

    public function shouldShowBirthdayOnCalendar(Person $person): bool
    {
        return in_array($person->lifecycle_status, [
            PersonLifecycleStatus::Employee,
            PersonLifecycleStatus::FormerEmployee,
        ], true);
    }

    private function syncEmploymentDateEvent(
        EmploymentRecord $record,
        CalendarEventType $type,
        ?Carbon $date,
        string $title,
        string $description,
        string $color,
        ?int $createdBy,
    ): void {
        $existing = CalendarEvent::query()
            ->where('employment_record_id', $record->id)
            ->where('event_type', $type)
            ->first();

        if (! $this->shouldShowEmploymentEventOnCalendar($record, $date)) {
            if ($existing) {
                $this->delete($existing);
            }

            return;
        }

        $payload = [
            'title' => $title,
            'description' => $description,
            'event_type' => $type,
            'starts_at' => $date->copy()->startOfDay(),
            'ends_at' => $date->copy()->endOfDay(),
            'all_day' => true,
            'person_id' => $record->person_id,
            'employment_record_id' => $record->id,
            'color' => $color,
        ];

        if ($existing) {
            $this->update($existing, $payload);

            return;
        }

        $creatorId = $createdBy ?? auth()->id();
        $createPayload = $payload;

        if ($creatorId !== null) {
            $createPayload['created_by'] = $creatorId;
        }

        $this->create($createPayload);
    }

    private function shouldShowEmploymentEventOnCalendar(EmploymentRecord $record, ?Carbon $date): bool
    {
        if ($date === null) {
            return false;
        }

        if (! in_array($record->status, [EmploymentStatus::Active, EmploymentStatus::OnLeave], true)) {
            return false;
        }

        return $record->person?->lifecycle_status === PersonLifecycleStatus::Employee;
    }
}
