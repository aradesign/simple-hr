<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Enums\InterviewResult;
use App\DTOs\InterviewData;
use App\Http\Controllers\Concerns\BulkDestroysModels;
use App\Http\Controllers\Controller;
use App\Models\Interview;
use App\Services\Interview\InterviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InterviewController extends Controller
{
    use BulkDestroysModels;

    public function __construct(
        private readonly InterviewService $interviewService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Interview::class);

        $interviews = Interview::query()
            ->with(['person', 'interviewer', 'employmentApplication'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('scheduled_at', $request->string('date')))
            ->orderByDesc('scheduled_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.interviews.index', compact('interviews'));
    }

    public function create(): View
    {
        $this->authorize('create', Interview::class);

        return view('admin.interviews.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Interview::class);

        $validated = $request->validate([
            'person_id' => ['required', 'exists:persons,id'],
            'employment_application_id' => ['nullable', 'exists:employment_applications,id'],
            'type' => ['required', 'string'],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:15'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_url' => ['nullable', 'url', 'max:500'],
            'interviewer_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $interview = $this->interviewService->schedule(InterviewData::fromArray($validated));

        return redirect()
            ->route('admin.interviews.show', $interview)
            ->with('success', 'مصاحبه با موفقیت برنامه‌ریزی شد.');
    }

    public function show(Interview $interview): View
    {
        $this->authorize('view', $interview);

        $interview->load(['person', 'interviewer', 'employmentApplication', 'calendarEvents']);

        return view('admin.interviews.show', compact('interview'));
    }

    public function edit(Interview $interview): View
    {
        $this->authorize('update', $interview);

        return view('admin.interviews.edit', compact('interview'));
    }

    public function update(Request $request, Interview $interview): RedirectResponse
    {
        $this->authorize('update', $interview);

        if ($request->filled('result')) {
            $this->authorize('complete', $interview);

            $validated = $request->validate([
                'result' => ['required', 'string'],
                'feedback' => ['nullable', 'string'],
            ]);

            $this->interviewService->complete(
                $interview,
                InterviewResult::from($validated['result']),
                $validated['feedback'] ?? null,
            );

            return redirect()
                ->route('admin.interviews.show', $interview)
                ->with('success', 'نتیجه مصاحبه ثبت شد.');
        }

        $validated = $request->validate([
            'type' => ['sometimes', 'string'],
            'scheduled_at' => ['sometimes', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:15'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_url' => ['nullable', 'url', 'max:500'],
            'interviewer_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $interview->update($validated);
        $this->interviewService->syncCalendarEvent($interview->fresh());

        return redirect()
            ->route('admin.interviews.show', $interview)
            ->with('success', 'مصاحبه با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Interview $interview): RedirectResponse
    {
        $this->authorize('delete', $interview);

        $this->interviewService->delete($interview);

        return redirect()
            ->route('admin.interviews.index')
            ->with('success', 'مصاحبه با موفقیت حذف شد.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        return $this->bulkDestroyModels(
            $request,
            Interview::class,
            fn (Interview $interview) => $this->interviewService->delete($interview),
            ':count مصاحبه با موفقیت حذف شد.',
        );
    }
}
