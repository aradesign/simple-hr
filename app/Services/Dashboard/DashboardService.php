<?php

namespace App\Services\Dashboard;

use App\Domain\Enums\ApplicationStatus;
use App\Domain\Enums\CalendarEventType;
use App\Domain\Enums\PersonLifecycleStatus;
use App\Models\Assignment;
use App\Models\CalendarEvent;
use App\Models\EmploymentApplication;
use App\Models\EmploymentRecord;
use App\Models\Interview;
use App\Models\Person;

class DashboardService
{
    public function getStats(): array
    {
        return [
            'applicants_count' => Person::query()
                ->where('lifecycle_status', PersonLifecycleStatus::Applicant)
                ->count(),
            'active_employees_count' => Person::query()
                ->where('lifecycle_status', PersonLifecycleStatus::Employee)
                ->count(),
            'new_applications_count' => EmploymentApplication::query()
                ->where('status', ApplicationStatus::Submitted)
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
            'pending_review_count' => EmploymentApplication::query()
                ->pendingReview()
                ->count(),
            'today_interviews_count' => Interview::query()->today()->count(),
            'today_birthdays_count' => CalendarEvent::query()
                ->today()
                ->byType(CalendarEventType::Birthday)
                ->count(),
            'contracts_expiring_soon_count' => EmploymentRecord::query()
                ->endingSoon(30)
                ->count(),
            'assigned_tasks_count' => Assignment::query()
                ->where('user_id', auth()->id())
                ->count(),
        ];
    }

    public function getTodayInterviews(): \Illuminate\Support\Collection
    {
        return Interview::query()
            ->today()
            ->with(['person', 'interviewer'])
            ->orderBy('scheduled_at')
            ->get();
    }

    public function getRecentApplications(int $limit = 10): \Illuminate\Support\Collection
    {
        return EmploymentApplication::query()
            ->with('person')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
