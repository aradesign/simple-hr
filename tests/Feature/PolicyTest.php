<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Document;
use App\Models\EmploymentApplication;
use App\Models\Interview;
use App\Models\Person;
use App\Models\User;
use App\Policies\DepartmentPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\EmploymentApplicationPolicy;
use App\Policies\InterviewPolicy;
use App\Policies\PersonPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_user_can_access_hr_panel_resources(): void
    {
        $hrUser = User::factory()->hr()->create();
        $person = Person::factory()->create();
        $application = EmploymentApplication::factory()->create(['person_id' => $person->id]);
        $interview = Interview::factory()->create(['person_id' => $person->id]);
        $document = Document::factory()->create(['person_id' => $person->id]);
        $department = Department::factory()->create();

        $this->assertTrue(app(PersonPolicy::class)->viewAny($hrUser));
        $this->assertTrue(app(PersonPolicy::class)->view($hrUser, $person));
        $this->assertTrue(app(PersonPolicy::class)->convertToEmployee($hrUser, $person));

        $this->assertTrue(app(EmploymentApplicationPolicy::class)->viewAny($hrUser));
        $this->assertTrue(app(EmploymentApplicationPolicy::class)->updateStatus($hrUser, $application));

        $this->assertTrue(app(InterviewPolicy::class)->create($hrUser));
        $this->assertTrue(app(InterviewPolicy::class)->complete($hrUser, $interview));

        $this->assertTrue(app(DocumentPolicy::class)->view($hrUser, $document));
        $this->assertTrue(app(DepartmentPolicy::class)->viewAny($hrUser));
        $this->assertTrue(app(DepartmentPolicy::class)->update($hrUser, $department));
    }

    public function test_employee_cannot_access_hr_panel_resources(): void
    {
        $employee = User::factory()->employee()->create();
        $otherPerson = Person::factory()->create();
        $application = EmploymentApplication::factory()->create(['person_id' => $otherPerson->id]);
        $department = Department::factory()->create();

        $this->assertFalse(app(PersonPolicy::class)->viewAny($employee));
        $this->assertFalse(app(PersonPolicy::class)->view($employee, $otherPerson));
        $this->assertFalse(app(EmploymentApplicationPolicy::class)->viewAny($employee));
        $this->assertFalse(app(EmploymentApplicationPolicy::class)->updateStatus($employee, $application));
        $this->assertFalse(app(DepartmentPolicy::class)->viewAny($employee));
        $this->assertFalse(app(DepartmentPolicy::class)->create($employee));
    }

    public function test_employee_can_view_own_person_record(): void
    {
        $person = Person::factory()->employee()->create();
        $employee = User::factory()->employee($person)->create();

        $this->assertTrue(app(PersonPolicy::class)->view($employee, $person));
        $this->assertTrue(app(PersonPolicy::class)->update($employee, $person));
    }

    public function test_hr_manager_can_manage_users_and_delete_resources(): void
    {
        $hrManager = User::factory()->hrManager()->create();
        $targetUser = User::factory()->hr()->create();
        $person = Person::factory()->create();
        $application = EmploymentApplication::factory()->create(['person_id' => $person->id]);
        $department = Department::factory()->create();

        $this->assertTrue(app(UserPolicy::class)->create($hrManager));
        $this->assertTrue(app(UserPolicy::class)->grantHrAccess($hrManager, $targetUser));
        $this->assertTrue(app(PersonPolicy::class)->delete($hrManager, $person));
        $this->assertTrue(app(EmploymentApplicationPolicy::class)->delete($hrManager, $application));
        $this->assertTrue(app(DepartmentPolicy::class)->delete($hrManager, $department));
    }

    public function test_regular_hr_cannot_delete_resources(): void
    {
        $hrUser = User::factory()->hr()->create();
        $person = Person::factory()->create();
        $application = EmploymentApplication::factory()->create(['person_id' => $person->id]);
        $department = Department::factory()->create();

        $this->assertFalse(app(PersonPolicy::class)->delete($hrUser, $person));
        $this->assertFalse(app(EmploymentApplicationPolicy::class)->delete($hrUser, $application));
        $this->assertFalse(app(DepartmentPolicy::class)->delete($hrUser, $department));
        $this->assertFalse(app(UserPolicy::class)->create($hrUser));
    }
}
