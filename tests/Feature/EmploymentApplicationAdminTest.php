<?php

namespace Tests\Feature;

use App\Domain\Enums\ApplicationStatus;
use App\Domain\Enums\InterviewStatus;
use App\Models\ApplicationFormField;
use App\Models\EmploymentApplication;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmploymentApplicationAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_applications_export_includes_all_form_fields(): void
    {
        $hrUser = User::factory()->hr()->create();

        ApplicationFormField::query()->create([
            'field_key' => 'first_name',
            'label' => 'نام',
            'field_type' => \App\Domain\Enums\FormFieldType::Text,
            'step' => 1,
            'sort_order' => 1,
            'is_visible' => true,
            'is_required' => false,
        ]);

        ApplicationFormField::query()->create([
            'field_key' => 'family_members',
            'label' => 'اعضای خانواده',
            'field_type' => \App\Domain\Enums\FormFieldType::List,
            'list_columns' => [
                ['key' => 'relation', 'label' => 'نسبت'],
                ['key' => 'full_name', 'label' => 'نام و نام خانوادگی'],
            ],
            'step' => 1,
            'sort_order' => 2,
            'is_visible' => true,
            'is_required' => false,
        ]);

        EmploymentApplication::factory()->create([
            'application_number' => 'APP-1001',
            'status' => ApplicationStatus::Submitted,
            'contact_mobile' => '09121111111',
            'submitted_at' => now(),
            'form_data' => [
                'first_name' => 'علی',
                'entry_date' => '1404/01/01 10:00',
                'family_members' => [
                    ['relation' => 'پدر', 'full_name' => 'حسن احمدی'],
                ],
            ],
        ]);

        $response = $this->actingAs($hrUser)->get(route('admin.applications.export'));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('نام', $content);
        $this->assertStringContainsString('اعضای خانواده 1 - نسبت', $content);
        $this->assertStringContainsString('تاریخ ورودی', $content);
        $this->assertStringContainsString('علی', $content);
        $this->assertStringContainsString('پدر', $content);
        $this->assertStringContainsString('حسن احمدی', $content);
        $this->assertStringContainsString('APP-1001', $content);
    }

    public function test_hr_can_schedule_interview_from_application_page(): void
    {
        $hrUser = User::factory()->hr()->create();
        $interviewer = User::factory()->hr()->create();
        $person = Person::factory()->create();
        $application = EmploymentApplication::factory()->create([
            'person_id' => $person->id,
            'status' => ApplicationStatus::UnderReview,
            'form_data' => [
                'gender' => 'آقا',
                'age' => '30',
                'preferred_department' => 'کارخانه (شهرک صنعتی)',
            ],
        ]);

        $scheduledAt = now()->addDay()->format('Y-m-d H:i:s');

        $response = $this->actingAs($hrUser)->post(route('admin.applications.schedule-interview', $application), [
            'type' => 'in_person',
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => 60,
            'location' => 'دفتر مرکزی',
            'interviewer_id' => $interviewer->id,
            'notes' => 'مصاحبه اول',
            'hr_notes' => 'آماده‌سازی پرونده',
        ]);

        $response->assertRedirect(route('admin.applications.show', $application));
        $response->assertSessionHas('success');

        $application->refresh();

        $this->assertEquals(ApplicationStatus::InterviewScheduled, $application->status);
        $this->assertEquals('آماده‌سازی پرونده', $application->hr_notes);

        $this->assertDatabaseHas('interviews', [
            'person_id' => $person->id,
            'employment_application_id' => $application->id,
            'interviewer_id' => $interviewer->id,
            'status' => InterviewStatus::Scheduled->value,
            'location' => 'دفتر مرکزی',
        ]);
    }

    public function test_application_index_supports_gender_filter_and_age_sort(): void
    {
        $hrUser = User::factory()->hr()->create();

        EmploymentApplication::factory()->create([
            'form_data' => ['gender' => 'آقا', 'age' => '25', 'preferred_department' => 'فروشگاه'],
            'status' => ApplicationStatus::Submitted,
            'submitted_at' => now(),
        ]);

        EmploymentApplication::factory()->create([
            'form_data' => ['gender' => 'خانم', 'age' => '40', 'preferred_department' => 'کارخانه'],
            'status' => ApplicationStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $this->actingAs($hrUser)
            ->get(route('admin.applications.index', ['gender' => 'آقا']))
            ->assertOk()
            ->assertSee('آقا', false)
            ->assertSee('25', false)
            ->assertSee('فروشگاه', false);

        $sortedIds = EmploymentApplication::query()
            ->orderByRaw("CAST(json_extract(form_data, '$.age') AS INTEGER) asc")
            ->pluck('id')
            ->all();

        $response = $this->actingAs($hrUser)
            ->get(route('admin.applications.index', ['sort' => 'age', 'direction' => 'asc']));

        $response->assertOk();

        $pageIds = collect($response->original->getData()['applications']->items())
            ->pluck('id')
            ->all();

        $this->assertSame($sortedIds, $pageIds);
    }

    public function test_application_index_shows_form_columns(): void
    {
        $hrUser = User::factory()->hr()->create();

        EmploymentApplication::factory()->create([
            'form_data' => [
                'gender' => 'خانم',
                'age' => '36',
                'preferred_department' => 'کارخانه (شهرک صنعتی)',
            ],
            'status' => ApplicationStatus::Submitted,
        ]);

        $this->actingAs($hrUser)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertSee('جنسیت', false)
            ->assertSee('سن', false)
            ->assertSee('محل فعالیت', false)
            ->assertSee('خانم', false)
            ->assertSee('36', false)
            ->assertSee('کارخانه (شهرک صنعتی)', false);
    }
}
