<?php

namespace App\Observers;

use App\Models\EmploymentRecord;
use App\Services\Calendar\CalendarService;

class EmploymentRecordObserver
{
    public function __construct(
        private readonly CalendarService $calendarService,
    ) {}

    public function created(EmploymentRecord $record): void
    {
        $this->calendarService->syncEmploymentRecordEvents($record);
    }

    public function updated(EmploymentRecord $record): void
    {
        $this->calendarService->syncEmploymentRecordEvents($record);
    }

    public function deleted(EmploymentRecord $record): void
    {
        $this->calendarService->removeEmploymentRecordEvents($record);
    }
}
