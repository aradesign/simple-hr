<?php

namespace Tests\Feature;

use App\Domain\Enums\ApplicationStatus;
use App\Domain\Enums\OtpPurpose;
use App\Http\Middleware\EnsureRecruitmentAuth;
use App\Models\EmploymentApplication;
use App\Services\Otp\OtpService;
use App\Services\Recruitment\ApplicationService;
use App\Services\Recruitment\GravityFormsImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruitmentPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(GravityFormsImportService::class)->importFromFile(
            database_path('seeders/data/gravity-employment-form.json'),
        );
    }

    public function test_otp_login_redirects_to_dashboard(): void
    {
        $mobile = '09121234567';
        $code = app(OtpService::class)->generate(new \App\DTOs\OtpRequestData(
            mobile: $mobile,
            purpose: OtpPurpose::Recruitment,
        ));

        $this->post(route('recruitment.otp.verify'), [
            'mobile' => $mobile,
            'code' => $code,
        ])
            ->assertRedirect(route('recruitment.dashboard'));

        $this->assertEquals($mobile, session(EnsureRecruitmentAuth::CONTACT_MOBILE_KEY));
    }

    public function test_contact_can_create_multiple_applications(): void
    {
        $contactMobile = '09121111111';

        $this->withSession([
            EnsureRecruitmentAuth::CONTACT_MOBILE_KEY => $contactMobile,
        ]);

        $this->post(route('recruitment.applications.store'))
            ->assertRedirect();

        $this->post(route('recruitment.applications.store'))
            ->assertRedirect();

        $this->assertEquals(2, EmploymentApplication::query()->where('contact_mobile', $contactMobile)->count());
    }

    public function test_contact_cannot_access_other_application(): void
    {
        $application = app(ApplicationService::class)->createForContact('09129999999');

        $this->withSession([
            EnsureRecruitmentAuth::CONTACT_MOBILE_KEY => '09121111111',
        ]);

        $this->get(route('recruitment.applications.form', $application))
            ->assertForbidden();
    }
}
