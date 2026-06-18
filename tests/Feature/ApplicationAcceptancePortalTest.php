<?php

namespace Tests\Feature;

use App\Domain\Enums\ApplicationStatus;
use App\Domain\Enums\EmploymentStatus;
use App\Domain\Enums\OtpPurpose;
use App\Domain\Enums\PersonLifecycleStatus;
use App\Http\Middleware\EnsurePortalAuth;
use App\Http\Middleware\EnsureRecruitmentAuth;
use App\Models\EmploymentApplication;
use App\Models\Person;
use App\Models\User;
use App\Services\Otp\OtpService;
use App\Services\Recruitment\ApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationAcceptancePortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepted_application_promotes_person_to_employee(): void
    {
        $hrUser = User::factory()->hr()->create();
        $person = Person::factory()->applicant()->create([
            'mobile' => '09800000001',
            'managed_by_mobile' => '09120000099',
            'first_name' => 'محمد',
            'last_name' => 'تستی',
        ]);

        $application = EmploymentApplication::factory()->create([
            'person_id' => $person->id,
            'contact_mobile' => '09120000099',
            'status' => ApplicationStatus::UnderReview,
            'form_data' => [
                'first_name' => 'محمد',
                'last_name' => 'تستی',
                'mobile' => '09120000099',
                'national_id' => '0019689497',
                'gender' => 'آقا',
                'education_level' => 'کارشناسی',
                'preferred_department' => 'فروش',
            ],
        ]);

        app(ApplicationService::class)->updateStatus(
            $application,
            ApplicationStatus::Accepted,
            $hrUser,
        );

        $person->refresh();

        $this->assertEquals(PersonLifecycleStatus::Employee, $person->lifecycle_status);
        $this->assertEquals('09120000099', $person->mobile);
        $this->assertEquals('0019689497', $person->national_id);

        $this->assertDatabaseHas('employment_records', [
            'person_id' => $person->id,
            'status' => EmploymentStatus::Active->value,
            'position_title' => 'فروش',
        ]);
    }

    public function test_accepted_application_with_empty_work_rows_promotes_person(): void
    {
        $hrUser = User::factory()->hr()->create();
        $person = Person::factory()->applicant()->create([
            'mobile' => '09800000002',
            'managed_by_mobile' => '09120000088',
        ]);

        $application = EmploymentApplication::factory()->create([
            'person_id' => $person->id,
            'contact_mobile' => '09120000088',
            'status' => ApplicationStatus::UnderReview,
            'form_data' => [
                'first_name' => 'علی',
                'last_name' => 'رضایی',
                'mobile' => '09120000088',
                'has_work_experience' => 'خیر',
                'work_experience' => [
                    ['company_name' => '', 'position' => '', 'duration_years' => ''],
                ],
            ],
        ]);

        app(ApplicationService::class)->updateStatus(
            $application,
            ApplicationStatus::Accepted,
            $hrUser,
        );

        $person->refresh();

        $this->assertEquals(PersonLifecycleStatus::Employee, $person->lifecycle_status);
        $this->assertDatabaseCount('person_work_experiences', 0);
    }

    public function test_employee_logging_in_via_recruitment_is_redirected_to_portal(): void
    {
        $mobile = '09120000099';

        $person = Person::factory()->create([
            'mobile' => $mobile,
            'lifecycle_status' => PersonLifecycleStatus::Employee,
        ]);

        $code = app(OtpService::class)->generate(new \App\DTOs\OtpRequestData(
            mobile: $mobile,
            purpose: OtpPurpose::Recruitment,
        ));

        $response = $this->post(route('recruitment.otp.verify'), [
            'mobile' => $mobile,
            'code' => $code,
        ]);

        $response->assertRedirect(route('portal.dashboard'));
        $this->assertEquals($person->id, session(EnsurePortalAuth::SESSION_KEY));
        $this->assertNull(session(EnsureRecruitmentAuth::CONTACT_MOBILE_KEY));
    }

    public function test_existing_recruitment_session_redirects_employee_to_portal(): void
    {
        $mobile = '09120000099';

        $person = Person::factory()->create([
            'mobile' => $mobile,
            'managed_by_mobile' => $mobile,
            'lifecycle_status' => PersonLifecycleStatus::Employee,
        ]);

        $response = $this->withSession([
            EnsureRecruitmentAuth::CONTACT_MOBILE_KEY => $mobile,
        ])->get(route('recruitment.dashboard'));

        $response->assertRedirect(route('portal.dashboard'));
        $this->assertEquals($person->id, session(EnsurePortalAuth::SESSION_KEY));
    }
}
