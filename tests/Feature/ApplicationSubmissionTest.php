<?php

namespace Tests\Feature;

use App\Domain\Enums\ApplicationStatus;
use App\Domain\Enums\PersonLifecycleStatus;
use App\Models\EmploymentApplication;
use App\Models\Person;
use App\Services\Recruitment\ApplicationPersonSyncService;
use App\Services\Recruitment\ApplicationService;
use App\Services\Recruitment\GravityFormsImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(GravityFormsImportService::class)->importFromFile(
            database_path('seeders/data/gravity-employment-form.json'),
        );
    }

    public function test_application_can_be_submitted_with_form_data(): void
    {
        $application = app(ApplicationService::class)->createForContact('09121111111');

        $formData = [
            'first_name' => 'علی',
            'last_name' => 'رضایی',
            'mobile' => '09122222222',
            'national_id' => '1234567890',
            'birth_date' => '1370/01/01',
        ];

        app(ApplicationPersonSyncService::class)->sync($application, $formData);
        $submitted = app(ApplicationService::class)->submit($application, $formData);

        $this->assertEquals(ApplicationStatus::Submitted, $submitted->status);
        $this->assertNotNull($submitted->submitted_at);
        $this->assertEquals('علی', $submitted->form_data['first_name']);
        $this->assertEquals(PersonLifecycleStatus::Applicant, $submitted->person->lifecycle_status);
        $this->assertEquals('09122222222', $submitted->person->fresh()->mobile);
    }

    public function test_legacy_gf_keys_are_normalized_on_submit(): void
    {
        $application = app(ApplicationService::class)->createForContact('09121111111');

        $submitted = app(ApplicationService::class)->submit($application, [
            'gf_1' => 'علی',
            'gf_3' => 'رضایی',
            'gf_21' => 'تهران، خیابان آزادی',
        ]);

        $this->assertEquals('علی', $submitted->form_data['first_name']);
        $this->assertEquals('رضایی', $submitted->form_data['last_name']);
        $this->assertEquals('تهران، خیابان آزادی', $submitted->form_data['address']);
        $this->assertArrayNotHasKey('gf_1', $submitted->form_data);
    }

    public function test_draft_application_is_created_for_new_request(): void
    {
        $application = app(ApplicationService::class)->createForContact('09121111111');

        $this->assertEquals(ApplicationStatus::Draft, $application->status);
        $this->assertStringStartsWith('APP-', $application->application_number);
        $this->assertEquals('09121111111', $application->contact_mobile);
        $this->assertDatabaseHas('employment_applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Draft->value,
        ]);
    }

    public function test_application_number_skips_soft_deleted_records(): void
    {
        $service = app(ApplicationService::class);
        $first = $service->createForContact('09121111111');
        $number = $first->application_number;

        $service->delete($first);

        $second = $service->createForContact('09123333333');

        $this->assertNotEquals($number, $second->application_number);
        $this->assertDatabaseHas('employment_applications', [
            'id' => $second->id,
            'application_number' => $second->application_number,
        ]);
    }

    public function test_submitted_application_persists_merged_form_data(): void
    {
        $person = Person::factory()->create();
        $application = EmploymentApplication::factory()->create([
            'person_id' => $person->id,
            'status' => ApplicationStatus::Draft,
            'form_data' => ['first_name' => 'علی', 'last_name' => 'رضایی'],
        ]);

        $submitted = app(ApplicationService::class)->submit($application, [
            'address' => 'تهران، خیابان آزادی',
        ]);

        $this->assertEquals('علی', $submitted->form_data['first_name']);
        $this->assertEquals('رضایی', $submitted->form_data['last_name']);
        $this->assertEquals('تهران، خیابان آزادی', $submitted->form_data['address']);
    }

    public function test_sync_skips_mobile_when_owned_by_soft_deleted_person(): void
    {
        $deleted = Person::factory()->create(['mobile' => '09120000099']);
        $deleted->delete();

        $application = app(ApplicationService::class)->createForContact('09121111111');

        app(ApplicationPersonSyncService::class)->sync($application, [
            'first_name' => 'علی',
            'last_name' => 'رضایی',
            'mobile' => '09120000099',
        ]);

        $this->assertNotEquals('09120000099', $application->person->fresh()->mobile);
        $this->assertEquals('علی', $application->person->fresh()->first_name);
    }

    public function test_sync_skips_mobile_when_already_owned_by_another_person(): void
    {
        Person::factory()->create(['mobile' => '09120000099']);
        $application = app(ApplicationService::class)->createForContact('09121111111');

        app(ApplicationPersonSyncService::class)->sync($application, [
            'first_name' => 'علی',
            'last_name' => 'رضایی',
            'mobile' => '09120000099',
        ]);

        $this->assertNotEquals('09120000099', $application->person->fresh()->mobile);
        $this->assertEquals('علی', $application->person->fresh()->first_name);
    }

    public function test_create_for_contact_reuses_existing_applicant_person(): void
    {
        $existing = Person::factory()->create([
            'mobile' => '09120000099',
            'lifecycle_status' => PersonLifecycleStatus::Applicant,
        ]);

        $application = app(ApplicationService::class)->createForContact('09120000099');

        $this->assertEquals($existing->id, $application->person_id);
    }
}
