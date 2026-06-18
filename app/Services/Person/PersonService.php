<?php

namespace App\Services\Person;

use App\Domain\Enums\EmploymentType;
use App\Domain\Enums\PersonLifecycleStatus;
use App\Domain\Events\PersonStatusChanged;
use App\DTOs\PersonData;
use App\Models\EmploymentRecord;
use App\Models\Person;
use App\Models\User;
use App\Services\Calendar\CalendarService;
use Illuminate\Support\Facades\DB;

class PersonService
{
    public function __construct(
        private readonly CalendarService $calendarService,
    ) {}

    public function create(PersonData $data, ?int $createdBy = null): Person
    {
        $person = Person::query()->create($data->toArray());

        $this->calendarService->syncPersonBirthday($person, $createdBy);

        return $person->fresh();
    }

    public function update(Person $person, PersonData $data): Person
    {
        $person->update($data->toArray());
        $person = $person->fresh();

        $this->calendarService->syncPersonBirthday($person);

        return $person;
    }

    public function delete(Person $person): void
    {
        $person->delete();
    }

    public function find(int $id): ?Person
    {
        return Person::query()->find($id);
    }

    public function convertApplicantToEmployee(
        Person $person,
        array $employmentData,
        ?User $changedBy = null,
    ): Person {
        return DB::transaction(function () use ($person, $employmentData, $changedBy) {
            $oldStatus = $person->lifecycle_status;
            $startDate = \Carbon\Carbon::parse($employmentData['start_date']);
            $employmentType = $employmentData['employment_type'];

            $person->update([
                'lifecycle_status' => PersonLifecycleStatus::Employee,
            ]);

            $record = EmploymentRecord::query()->create([
                'person_id' => $person->id,
                'department_id' => $employmentData['department_id'] ?? null,
                'employee_code' => $employmentData['employee_code'],
                'employment_type' => $employmentType,
                'status' => $employmentData['status'],
                'start_date' => $startDate->toDateString(),
                'end_date' => $employmentData['end_date'] ?? null,
                'probation_end_date' => $employmentData['probation_end_date']
                    ?? $startDate->copy()->addMonths(3)->toDateString(),
                'contract_end_date' => $employmentData['contract_end_date']
                    ?? match ($employmentType) {
                        EmploymentType::Contract, EmploymentType::Intern => $startDate->copy()->addYear()->toDateString(),
                        default => null,
                    },
                'salary' => $employmentData['salary'] ?? null,
                'position_title' => $employmentData['position_title'] ?? null,
                'notes' => $employmentData['notes'] ?? null,
            ]);

            $person = $person->fresh();

            $this->calendarService->syncPersonBirthday($person, $changedBy?->id);
            $this->calendarService->syncEmploymentRecordEvents($record->fresh('person'), $changedBy?->id);

            if ($oldStatus !== PersonLifecycleStatus::Employee) {
                event(new PersonStatusChanged(
                    person: $person,
                    oldStatus: $oldStatus,
                    newStatus: PersonLifecycleStatus::Employee,
                    changedBy: $changedBy,
                ));
            }

            return $person;
        });
    }
}
