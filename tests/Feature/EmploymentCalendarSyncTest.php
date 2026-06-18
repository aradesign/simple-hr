<?php

namespace Tests\Feature;

use App\Domain\Enums\CalendarEventType;
use App\Domain\Enums\EmploymentStatus;
use App\Domain\Enums\EmploymentType;
use App\Domain\Enums\PersonLifecycleStatus;
use App\Models\EmploymentRecord;
use App\Models\Person;
use App\Services\Calendar\CalendarService;
use App\Services\Person\PersonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmploymentCalendarSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_employment_record_creates_contract_and_probation_calendar_events(): void
    {
        $person = Person::factory()->applicant()->create();
        $contractEnd = now()->addMonths(6)->toDateString();
        $probationEnd = now()->addMonths(3)->toDateString();

        app(PersonService::class)->convertApplicantToEmployee($person, [
            'employee_code' => 'EMP-TEST-01',
            'employment_type' => EmploymentType::Contract,
            'status' => EmploymentStatus::Active,
            'start_date' => now()->toDateString(),
            'probation_end_date' => $probationEnd,
            'contract_end_date' => $contractEnd,
            'position_title' => 'کارمند',
        ]);

        $record = EmploymentRecord::query()->where('person_id', $person->id)->first();

        $this->assertDatabaseHas('calendar_events', [
            'employment_record_id' => $record->id,
            'event_type' => CalendarEventType::ProbationEnd->value,
            'person_id' => $person->id,
        ]);

        $this->assertDatabaseHas('calendar_events', [
            'employment_record_id' => $record->id,
            'event_type' => CalendarEventType::ContractEnd->value,
        ]);

        $this->assertDatabaseHas('calendar_events', [
            'employment_record_id' => $record->id,
            'event_type' => CalendarEventType::ContractRenewal->value,
        ]);
    }

    public function test_updating_employment_record_updates_calendar_event_dates(): void
    {
        $person = Person::factory()->employee()->create();
        $record = EmploymentRecord::factory()->create([
            'person_id' => $person->id,
            'contract_end_date' => now()->addMonths(4),
            'probation_end_date' => now()->addMonths(2),
        ]);

        $newContractEnd = now()->addYear()->toDateString();

        $record->update(['contract_end_date' => $newContractEnd]);

        $event = $person->calendarEvents()
            ->where('event_type', CalendarEventType::ContractEnd)
            ->first();

        $this->assertNotNull($event);
        $this->assertEquals($newContractEnd, $event->starts_at->toDateString());
    }

    public function test_terminated_employment_record_removes_calendar_events(): void
    {
        $person = Person::factory()->employee()->create();
        $record = EmploymentRecord::factory()->create([
            'person_id' => $person->id,
            'contract_end_date' => now()->addMonths(6),
            'probation_end_date' => now()->addMonths(2),
        ]);

        $this->assertGreaterThan(0, $record->calendarEvents()->count());

        $record->update(['status' => EmploymentStatus::Terminated]);

        $this->assertEquals(0, $record->calendarEvents()->count());
    }

    public function test_sync_all_command_processes_records(): void
    {
        EmploymentRecord::factory()->count(2)->create([
            'contract_end_date' => now()->addMonths(5),
            'probation_end_date' => now()->addMonths(2),
        ]);

        $this->artisan('calendar:sync-employment')
            ->assertSuccessful();

        $this->assertDatabaseHas('calendar_events', [
            'event_type' => CalendarEventType::ContractEnd->value,
        ]);
    }

    public function test_contract_employee_gets_default_dates_on_conversion(): void
    {
        $person = Person::factory()->applicant()->create();

        app(PersonService::class)->convertApplicantToEmployee($person, [
            'employee_code' => 'EMP-TEST-02',
            'employment_type' => EmploymentType::Contract,
            'status' => EmploymentStatus::Active,
            'start_date' => now()->toDateString(),
            'position_title' => 'تحلیلگر',
        ]);

        $record = EmploymentRecord::query()->where('person_id', $person->id)->first();

        $this->assertNotNull($record->probation_end_date);
        $this->assertNotNull($record->contract_end_date);

        $this->assertDatabaseHas('calendar_events', [
            'employment_record_id' => $record->id,
            'event_type' => CalendarEventType::ProbationEnd->value,
        ]);
    }
}
