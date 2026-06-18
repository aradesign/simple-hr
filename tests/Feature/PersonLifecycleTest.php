<?php

namespace Tests\Feature;

use App\Domain\Enums\EmploymentStatus;
use App\Domain\Enums\EmploymentType;
use App\Domain\Enums\PersonLifecycleStatus;
use App\Models\Department;
use App\Models\EmploymentRecord;
use App\Models\Person;
use App\Models\User;
use App\Services\Person\PersonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_applicant_can_be_converted_to_employee(): void
    {
        $person = Person::factory()->applicant()->create();
        $department = Department::factory()->create();
        $hrUser = User::factory()->hr()->create();

        $person = app(PersonService::class)->convertApplicantToEmployee(
            $person,
            [
                'department_id' => $department->id,
                'employee_code' => 'EMP-00001',
                'employment_type' => EmploymentType::FullTime,
                'status' => EmploymentStatus::Active,
                'start_date' => now()->toDateString(),
                'position_title' => 'توسعه‌دهنده نرم‌افزار',
            ],
            $hrUser,
        );

        $this->assertEquals(PersonLifecycleStatus::Employee, $person->lifecycle_status);

        $this->assertDatabaseHas('employment_records', [
            'person_id' => $person->id,
            'department_id' => $department->id,
            'employee_code' => 'EMP-00001',
            'status' => EmploymentStatus::Active->value,
        ]);

        $this->assertEquals(1, EmploymentRecord::query()->where('person_id', $person->id)->count());
    }

    public function test_conversion_creates_employment_record_with_department(): void
    {
        $person = Person::factory()->create([
            'lifecycle_status' => PersonLifecycleStatus::Accepted,
        ]);
        $department = Department::factory()->create(['name' => 'فناوری اطلاعات']);

        app(PersonService::class)->convertApplicantToEmployee($person, [
            'department_id' => $department->id,
            'employee_code' => 'EMP-00002',
            'employment_type' => EmploymentType::Contract,
            'status' => EmploymentStatus::Active,
            'start_date' => now()->toDateString(),
            'contract_end_date' => now()->addYear()->toDateString(),
            'position_title' => 'تحلیلگر داده',
        ]);

        $record = EmploymentRecord::query()->where('person_id', $person->id)->first();

        $this->assertNotNull($record);
        $this->assertEquals($department->id, $record->department_id);
        $this->assertEquals(EmploymentType::Contract, $record->employment_type);
    }
}
