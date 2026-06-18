<?php

namespace Tests\Feature;

use App\Helpers\JalaliHelper;
use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Morilog\Jalali\Jalalian;
use Tests\TestCase;

class CalendarPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_user_can_view_calendar_with_jalali_month(): void
    {
        $user = User::factory()->hr()->create();
        $now = Jalalian::now();

        $this->actingAs($user)
            ->withoutVite()
            ->get(route('admin.calendar.index', [
                'year' => $now->getYear(),
                'month' => $now->getMonth(),
            ]))
            ->assertOk()
            ->assertSee('تقویم HR')
            ->assertSee('رویدادهای روز');
    }

    public function test_hr_user_can_create_calendar_event(): void
    {
        $user = User::factory()->hr()->create();
        $date = JalaliHelper::parseDate('1403/01/15');

        $this->actingAs($user)
            ->post(route('admin.calendar.store'), [
                'title' => 'رویداد تست',
                'event_type' => 'hr_event',
                'starts_at' => $date->format('Y-m-d'),
                'starts_time' => '10:30',
                'all_day' => '0',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('calendar_events', [
            'title' => 'رویداد تست',
            'created_by' => $user->id,
        ]);
    }

    public function test_jalali_month_range_matches_gregorian_storage(): void
    {
        [$start, $end] = JalaliHelper::monthRange(1403, 1);

        CalendarEvent::query()->create([
            'title' => 'داخل فروردین',
            'event_type' => 'hr_event',
            'starts_at' => $start->copy()->addDay(),
            'created_by' => User::factory()->hr()->create()->id,
        ]);

        $events = app(\App\Services\Calendar\CalendarService::class)->getMonthEvents(1403, 1);

        $this->assertCount(1, $events);
        $this->assertTrue($end->greaterThan($start));
    }
}
