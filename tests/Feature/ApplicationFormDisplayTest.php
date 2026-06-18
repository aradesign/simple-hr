<?php

namespace Tests\Feature;

use App\Domain\Enums\ApplicationStatus;
use App\Models\ApplicationFormField;
use App\Models\EmploymentApplication;
use App\Models\Person;
use App\Models\User;
use App\Services\Recruitment\ApplicationFormDisplayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationFormDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_entries_use_persian_field_labels(): void
    {
        ApplicationFormField::query()->create([
            'field_key' => 'first_name',
            'label' => 'نام',
            'field_type' => 'text',
            'step' => 1,
            'sort_order' => 1,
            'is_visible' => true,
            'is_required' => true,
        ]);

        ApplicationFormField::query()->create([
            'field_key' => 'birth_date',
            'label' => 'تاریخ تولد',
            'field_type' => 'date',
            'step' => 1,
            'sort_order' => 2,
            'is_visible' => true,
            'is_required' => true,
        ]);

        $application = EmploymentApplication::factory()->create([
            'status' => ApplicationStatus::Submitted,
            'form_data' => [
                'first_name' => 'محمد',
                'birth_date' => '1990-02-23',
            ],
        ]);

        $entries = app(ApplicationFormDisplayService::class)->entries($application);

        $this->assertEquals('نام', $entries[0]['label']);
        $this->assertEquals('محمد', $entries[0]['value']);
        $this->assertEquals('تاریخ تولد', $entries[1]['label']);
        $this->assertStringContainsString('1368', $entries[1]['value']);
    }

    public function test_admin_can_view_printable_form_page(): void
    {
        $user = User::factory()->hr()->create();
        $application = EmploymentApplication::factory()->create([
            'status' => ApplicationStatus::Submitted,
            'form_data' => ['first_name' => 'محمد'],
        ]);

        ApplicationFormField::query()->create([
            'field_key' => 'first_name',
            'label' => 'نام',
            'field_type' => 'text',
            'step' => 1,
            'sort_order' => 1,
            'is_visible' => true,
            'is_required' => true,
        ]);

        $this->actingAs($user)
            ->get(route('admin.applications.print', $application))
            ->assertOk()
            ->assertSee('فرم متقاضی', false)
            ->assertSee('نام', false)
            ->assertSee('محمد', false)
            ->assertSee('dir="rtl"', false);
    }

    public function test_admin_can_download_form_as_pdf(): void
    {
        $user = User::factory()->hr()->create();
        $person = Person::factory()->create(['first_name' => 'محمد', 'last_name' => 'تستی']);
        $application = EmploymentApplication::factory()->create([
            'person_id' => $person->id,
            'status' => ApplicationStatus::Submitted,
            'form_data' => ['first_name' => 'محمد'],
        ]);

        ApplicationFormField::query()->create([
            'field_key' => 'first_name',
            'label' => 'نام',
            'field_type' => 'text',
            'step' => 1,
            'sort_order' => 1,
            'is_visible' => true,
            'is_required' => true,
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.applications.download', $application));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition', 'attachment; filename="application-'.$application->application_number.'.pdf"');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
