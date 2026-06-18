<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\EmploymentApplication;
use App\Models\Interview;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_manager_can_bulk_delete_persons(): void
    {
        $manager = User::factory()->hrManager()->create();
        $persons = Person::factory()->count(3)->create();

        $this->actingAs($manager)
            ->delete(route('admin.persons.bulk-destroy'), [
                'ids' => $persons->pluck('id')->all(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        foreach ($persons as $person) {
            $this->assertSoftDeleted('persons', ['id' => $person->id]);
        }
    }

    public function test_regular_hr_user_cannot_bulk_delete_persons(): void
    {
        $hrUser = User::factory()->hr()->create();
        $person = Person::factory()->create();

        $this->actingAs($hrUser)
            ->delete(route('admin.persons.bulk-destroy'), [
                'ids' => [$person->id],
            ])
            ->assertForbidden();
    }

    public function test_hr_manager_can_bulk_delete_applications(): void
    {
        $manager = User::factory()->hrManager()->create();
        $person = Person::factory()->create();
        $applications = EmploymentApplication::factory()->count(2)->create(['person_id' => $person->id]);

        $this->actingAs($manager)
            ->delete(route('admin.applications.bulk-destroy'), [
                'ids' => $applications->pluck('id')->all(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        foreach ($applications as $application) {
            $this->assertSoftDeleted('employment_applications', ['id' => $application->id]);
        }
    }

    public function test_hr_manager_can_bulk_delete_interviews_and_calendar_events(): void
    {
        $manager = User::factory()->hrManager()->create();
        $person = Person::factory()->create();
        $interview = Interview::factory()->create(['person_id' => $person->id]);

        $event = CalendarEvent::query()->create([
            'title' => 'مصاحبه تست',
            'event_type' => 'interview',
            'starts_at' => now()->addDay(),
            'interview_id' => $interview->id,
            'person_id' => $person->id,
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->delete(route('admin.interviews.bulk-destroy'), [
                'ids' => [$interview->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSoftDeleted('interviews', ['id' => $interview->id]);
        $this->assertSoftDeleted('calendar_events', ['id' => $event->id]);
    }

    public function test_bulk_delete_index_pages_show_checkboxes_for_managers(): void
    {
        $manager = User::factory()->hrManager()->create();
        Person::factory()->employee()->create();

        $this->actingAs($manager)
            ->withoutVite()
            ->get(route('admin.persons.index'))
            ->assertOk()
            ->assertSee('حذف انتخاب‌شده‌ها', false)
            ->assertSee('aria-label="انتخاب همه"', false);
    }
}
