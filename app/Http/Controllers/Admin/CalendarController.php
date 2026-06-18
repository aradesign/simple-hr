<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Enums\CalendarEventType;
use App\Helpers\JalaliHelper;
use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Person;
use App\Services\Calendar\CalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Morilog\Jalali\Jalalian;

class CalendarController extends Controller
{
    public function __construct(
        private readonly CalendarService $calendarService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', CalendarEvent::class);

        $now = Jalalian::now();
        $jalaliYear = (int) $request->input('year', $now->getYear());
        $jalaliMonth = (int) $request->input('month', $now->getMonth());
        $meta = JalaliHelper::monthMeta($jalaliYear, $jalaliMonth);

        $events = $this->calendarService->getMonthEvents($jalaliYear, $jalaliMonth);

        $eventColors = [
            'interview' => 'bg-blue-500',
            'birthday' => 'bg-pink-500',
            'contract_end' => 'bg-red-500',
            'contract_renewal' => 'bg-green-500',
            'probation_end' => 'bg-amber-500',
            'training' => 'bg-purple-500',
            'hr_event' => 'bg-cyan-600',
        ];

        return view('admin.calendar.index', [
            'events' => $events,
            'jalaliYear' => $jalaliYear,
            'jalaliMonth' => $jalaliMonth,
            'monthMeta' => $meta,
            'eventColors' => $eventColors,
            'eventsPayload' => $events->map(fn (CalendarEvent $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'event_type' => $event->event_type->value,
                'event_type_label' => $event->event_type->label(),
                'starts_at' => $event->starts_at->format('Y-m-d H:i:s'),
                'ends_at' => $event->ends_at?->format('Y-m-d H:i:s'),
                'all_day' => $event->all_day,
                'person_id' => $event->person_id,
                'person_name' => $event->person?->full_name,
                'jalali_date' => Jalalian::fromCarbon($event->starts_at)->format('Y-m-d'),
                'time' => $event->starts_at->format('H:i'),
            ])->values(),
            'persons' => Person::query()
                ->inPersonnelRoster()
                ->orderBy('first_name')
                ->limit(200)
                ->get(['id', 'first_name', 'last_name']),
            'eventTypes' => CalendarEventType::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CalendarEvent::class);

        $validated = $this->validateEvent($request);
        $validated['created_by'] = $request->user()->id;

        $this->calendarService->create($validated);

        return back()->with('success', 'رویداد با موفقیت ایجاد شد.');
    }

    public function update(Request $request, CalendarEvent $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $validated = $this->validateEvent($request, partial: true);

        $this->calendarService->update($event, $validated);

        return back()->with('success', 'رویداد با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(CalendarEvent $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        $this->calendarService->delete($event);

        return back()->with('success', 'رویداد با موفقیت حذف شد.');
    }

    private function validateEvent(Request $request, bool $partial = false): array
    {
        $rules = [
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'event_type' => [$partial ? 'sometimes' : 'required', 'string'],
            'starts_at' => [$partial ? 'sometimes' : 'required', 'date'],
            'starts_time' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'all_day' => ['boolean'],
            'person_id' => ['nullable', 'exists:persons,id'],
            'interview_id' => ['nullable', 'exists:interviews,id'],
            'color' => ['nullable', 'string', 'max:20'],
        ];

        $validated = $request->validate($rules);
        $validated['all_day'] = $request->boolean('all_day');

        if ($request->filled('starts_time') && isset($validated['starts_at'])) {
            $validated['starts_at'] = JalaliHelper::parseDateTime(
                $validated['starts_at'],
                $request->input('starts_time'),
            )?->format('Y-m-d H:i:s');
        }

        unset($validated['starts_time']);

        return $validated;
    }
}
