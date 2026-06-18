<?php

namespace Tests\Feature;

use App\Domain\Enums\ApplicationStatus;
use App\Domain\Enums\PersonLifecycleStatus;
use App\Models\EmploymentApplication;
use App\Models\Person;
use App\Models\User;
use App\Services\Person\PersonMobileService;
use App\Services\Recruitment\ApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonMobileSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_acceptance_reclaims_mobile_from_stale_draft_applicant(): void
    {
        $hrUser = User::factory()->hr()->create();
        $mobile = '09120000099';

        $ghost = Person::factory()->applicant()->create([
            'first_name' => 'متقاضی',
            'last_name' => $mobile,
            'mobile' => $mobile,
        ]);

        EmploymentApplication::factory()->create([
            'person_id' => $ghost->id,
            'status' => ApplicationStatus::Draft,
            'form_data' => [],
        ]);

        $person = Person::factory()->applicant()->create([
            'mobile' => '09800000007',
            'managed_by_mobile' => $mobile,
            'first_name' => 'محمد',
            'last_name' => 'تستی',
        ]);

        $application = EmploymentApplication::factory()->create([
            'person_id' => $person->id,
            'contact_mobile' => $mobile,
            'status' => ApplicationStatus::UnderReview,
            'form_data' => [
                'first_name' => 'محمد',
                'last_name' => 'تستی',
                'mobile' => $mobile,
            ],
        ]);

        app(ApplicationService::class)->updateStatus(
            $application,
            ApplicationStatus::Accepted,
            $hrUser,
        );

        $person->refresh();
        $ghost->refresh();

        $this->assertEquals($mobile, $person->mobile);
        $this->assertTrue($ghost->usesTemporaryMobile());
        $this->assertEquals(PersonLifecycleStatus::Employee, $person->lifecycle_status);
    }

    public function test_display_mobile_uses_managed_by_when_temporary_mobile_is_set(): void
    {
        $person = Person::factory()->employee()->create([
            'mobile' => '09800000007',
            'managed_by_mobile' => '09120000099',
        ]);

        $this->assertEquals('09120000099', $person->display_mobile);
    }

    public function test_assign_real_mobile_fixes_existing_employee_record(): void
    {
        $mobile = '09120000099';

        $ghost = Person::factory()->applicant()->create([
            'first_name' => 'متقاضی',
            'last_name' => 'قدیمی',
            'mobile' => $mobile,
        ]);

        EmploymentApplication::factory()->create([
            'person_id' => $ghost->id,
            'status' => ApplicationStatus::Draft,
        ]);

        $person = Person::factory()->employee()->create([
            'mobile' => '09800000007',
            'managed_by_mobile' => $mobile,
        ]);

        $application = EmploymentApplication::factory()->create([
            'person_id' => $person->id,
            'contact_mobile' => $mobile,
            'status' => ApplicationStatus::Accepted,
        ]);

        app(PersonMobileService::class)->assignRealMobile($person, $mobile, $application);

        $person->refresh();

        $this->assertEquals($mobile, $person->mobile);
    }
}
