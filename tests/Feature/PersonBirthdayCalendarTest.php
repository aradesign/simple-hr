<?php

namespace Tests\Feature;

use App\Domain\Enums\CalendarEventType;
use App\Models\CalendarEvent;
use App\Models\Person;
use App\Models\User;
use App\Services\Calendar\CalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Morilog\Jalali\Jalalian;
use Tests\TestCase;

class PersonBirthdayCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_applicant_birth_date_does_not_create_calendar_event(): void
    {
        $user = User::factory()->hr()->create();
        $person = Person::factory()->applicant()->create(['birth_date' => null]);

        $this->actingAs($user)
            ->put(route('admin.persons.update', $person), [
                'first_name' => $person->first_name,
                'last_name' => $person->last_name,
                'birth_date' => '1990-05-15',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('calendar_events', [
            'person_id' => $person->id,
            'event_type' => CalendarEventType::Birthday->value,
        ]);
    }

    public function test_updating_person_birth_date_creates_birthday_calendar_event(): void
    {
        $user = User::factory()->hr()->create();
        $person = Person::factory()->employee()->create(['birth_date' => null]);

        $this->actingAs($user)
            ->put(route('admin.persons.update', $person), [
                'first_name' => $person->first_name,
                'last_name' => $person->last_name,
                'birth_date' => '1990-05-15',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('calendar_events', [
            'person_id' => $person->id,
            'event_type' => CalendarEventType::Birthday->value,
        ]);

        $event = CalendarEvent::query()
            ->where('person_id', $person->id)
            ->where('event_type', CalendarEventType::Birthday)
            ->first();

        $this->assertNotNull($event);
        $this->assertSame(
            now()->year.'-05-15',
            $event->starts_at->format('Y-m-d'),
        );
    }

    public function test_birthday_event_appears_in_jalali_month_query(): void
    {
        $user = User::factory()->hr()->create();
        $person = Person::factory()->employee()->create(['birth_date' => '1990-05-15']);

        app(CalendarService::class)->syncPersonBirthday($person, $user->id);

        $jalali = Jalalian::fromCarbon(now()->copy()->month(5)->day(15)->startOfDay());
        $events = app(CalendarService::class)->getMonthEvents($jalali->getYear(), $jalali->getMonth());

        $this->assertTrue(
            $events->contains(fn (CalendarEvent $event) => $event->person_id === $person->id
                && $event->event_type === CalendarEventType::Birthday),
        );
    }

    public function test_clearing_birth_date_removes_birthday_calendar_event(): void
    {
        $user = User::factory()->hr()->create();
        $person = Person::factory()->employee()->create(['birth_date' => '1990-05-15']);

        app(CalendarService::class)->syncPersonBirthday($person, $user->id);

        $this->actingAs($user)
            ->put(route('admin.persons.update', $person), [
                'first_name' => $person->first_name,
                'last_name' => $person->last_name,
                'birth_date' => '',
            ])
            ->assertRedirect();

        $person->refresh();
        $this->assertNull($person->birth_date);

        $this->assertDatabaseMissing('calendar_events', [
            'person_id' => $person->id,
            'event_type' => CalendarEventType::Birthday->value,
            'deleted_at' => null,
        ]);
    }
}
