<?php

namespace App\Providers;

use App\Domain\Events\ApplicationStatusChanged;
use App\Domain\Events\InterviewScheduled;
use App\Domain\Events\PersonStatusChanged;
use App\Listeners\LogAuditOnStatusChange;
use App\Listeners\SendApplicationStatusNotification;
use App\Listeners\SendEmployeeWelcomeNotification;
use App\Listeners\SendInterviewNotification;
use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\Document;
use App\Models\EmploymentApplication;
use App\Models\Interview;
use App\Models\Person;
use App\Models\User;
use App\Policies\CalendarEventPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\EmploymentApplicationPolicy;
use App\Policies\InterviewPolicy;
use App\Policies\PersonPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        InterviewScheduled::class => [
            SendInterviewNotification::class,
        ],
        ApplicationStatusChanged::class => [
            SendApplicationStatusNotification::class,
            LogAuditOnStatusChange::class,
        ],
        PersonStatusChanged::class => [
            LogAuditOnStatusChange::class,
            SendEmployeeWelcomeNotification::class,
        ],
    ];

    protected $policies = [
        Person::class => PersonPolicy::class,
        EmploymentApplication::class => EmploymentApplicationPolicy::class,
        Interview::class => InterviewPolicy::class,
        Document::class => DocumentPolicy::class,
        Department::class => DepartmentPolicy::class,
        CalendarEvent::class => CalendarEventPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        parent::boot();

        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }
}
