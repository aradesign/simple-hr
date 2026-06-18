<?php

namespace Tests\Feature;

use App\Domain\Enums\ApplicationStatus;
use App\Domain\Enums\CalendarEventType;
use App\Domain\Enums\PersonLifecycleStatus;
use App\Http\Middleware\EnsurePortalAuth;
use App\Models\EmploymentApplication;
use App\Models\HrTicket;
use App\Models\NotificationLog;
use App\Models\Person;
use App\Models\User;
use App\Services\Recruitment\ApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalEmployeeFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_update_profile(): void
    {
        $person = Person::factory()->employee()->create([
            'first_name' => 'علی',
            'last_name' => 'رضایی',
        ]);

        $this->withSession([EnsurePortalAuth::SESSION_KEY => $person->id])
            ->put(route('portal.profile.update'), [
                'first_name' => 'محمد',
                'last_name' => 'تستی',
                'address' => 'تهران',
            ])
            ->assertRedirect(route('portal.profile'));

        $person->refresh();
        $this->assertEquals('محمد', $person->first_name);
        $this->assertEquals('تستی', $person->last_name);
        $this->assertEquals('تهران', $person->address);
    }

    public function test_employee_can_create_hr_ticket(): void
    {
        $person = Person::factory()->employee()->create();

        $this->withSession([EnsurePortalAuth::SESSION_KEY => $person->id])
            ->post(route('portal.tickets.store'), [
                'subject' => 'درخواست تغییر موبایل',
                'message' => 'لطفاً شماره موبایل من را به‌روزرسانی کنید.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('hr_tickets', [
            'person_id' => $person->id,
            'subject' => 'درخواست تغییر موبایل',
        ]);
    }

    public function test_employee_sees_in_app_notifications_by_person_id(): void
    {
        $person = Person::factory()->employee()->create();

        NotificationLog::query()->create([
            'person_id' => $person->id,
            'channel' => 'in_app',
            'recipient' => $person->mobile,
            'subject' => 'خوش آمدید',
            'body' => 'پروفایل شما فعال شد.',
            'status' => 'sent',
        ]);

        $this->withSession([EnsurePortalAuth::SESSION_KEY => $person->id])
            ->get(route('portal.notifications'))
            ->assertOk()
            ->assertSee('خوش آمدید', false)
            ->assertSee('پروفایل شما فعال شد.', false);
    }

    public function test_accepted_applicant_gets_birthday_on_calendar(): void
    {
        $hrUser = User::factory()->hr()->create();
        $person = Person::factory()->applicant()->create([
            'birth_date' => '1990-05-15',
        ]);

        $application = EmploymentApplication::factory()->create([
            'person_id' => $person->id,
            'status' => ApplicationStatus::UnderReview,
            'form_data' => ['first_name' => 'محمد', 'last_name' => 'تستی'],
        ]);

        app(ApplicationService::class)->updateStatus(
            $application,
            ApplicationStatus::Accepted,
            $hrUser,
        );

        $this->assertDatabaseHas('calendar_events', [
            'person_id' => $person->id,
            'event_type' => CalendarEventType::Birthday->value,
        ]);

        $this->assertDatabaseHas('notification_logs', [
            'person_id' => $person->id,
            'channel' => 'in_app',
        ]);
    }

    public function test_hr_can_view_employee_ticket(): void
    {
        $hrUser = User::factory()->hr()->create();
        $person = Person::factory()->employee()->create();
        $ticket = HrTicket::query()->create([
            'person_id' => $person->id,
            'subject' => 'سوال حقوق',
            'message' => 'فیش حقوقی من را می‌خواهم.',
            'status' => 'open',
        ]);

        $this->actingAs($hrUser)
            ->get(route('admin.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('سوال حقوق', false);
    }
}
