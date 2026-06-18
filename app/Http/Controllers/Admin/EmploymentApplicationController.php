<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Enums\ApplicationStatus;
use App\Http\Controllers\Concerns\BulkDestroysModels;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScheduleApplicationInterviewRequest;
use App\Models\EmploymentApplication;
use App\Models\Interview;
use App\Models\User;
use App\Services\Interview\InterviewService;
use App\Services\Recruitment\ApplicationCsvExportService;
use App\Services\Recruitment\ApplicationFormDisplayService;
use App\Services\Recruitment\ApplicationFormPdfService;
use App\Services\Recruitment\ApplicationFormPrintLayoutService;
use App\DTOs\InterviewData;
use App\Support\EmploymentApplicationIndexQuery;
use App\Services\Recruitment\ApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmploymentApplicationController extends Controller
{
    use BulkDestroysModels;

    public function __construct(
        private readonly ApplicationService $applicationService,
        private readonly ApplicationFormDisplayService $formDisplayService,
        private readonly ApplicationCsvExportService $csvExportService,
        private readonly ApplicationFormPdfService $formPdfService,
        private readonly ApplicationFormPrintLayoutService $printLayoutService,
        private readonly InterviewService $interviewService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', EmploymentApplication::class);

        $applications = EmploymentApplicationIndexQuery::make($request)
            ->apply()
            ->paginate(20)
            ->withQueryString();

        $genderOptions = ['آقا', 'خانم'];
        $departmentOptions = EmploymentApplication::query()
            ->whereNotNull('form_data->preferred_department')
            ->pluck('form_data')
            ->map(fn (array $data) => $data['preferred_department'] ?? null)
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('admin.applications.index', compact('applications', 'genderOptions', 'departmentOptions'));
    }

    public function show(EmploymentApplication $application): View
    {
        $this->authorize('view', $application);

        $application->load(['person', 'interviews.interviewer', 'assignee', 'reviewer', 'assignments.user']);

        $formEntries = $this->formDisplayService->entries($application);

        return view('admin.applications.show', [
            'application' => $application,
            'formEntries' => $formEntries,
            'profilePhotoUrl' => $this->formDisplayService->profilePhotoUrl($application),
            'initials' => $this->formDisplayService->applicantInitials($application),
            'applicantName' => $this->formDisplayService->applicantDisplayName($application),
            'interviewers' => User::query()->where('hr_access', true)->orderBy('name')->get(),
        ]);
    }

    public function print(EmploymentApplication $application): View
    {
        $this->authorize('view', $application);

        return view('admin.applications.print', $this->printViewData($application));
    }

    public function download(EmploymentApplication $application): Response
    {
        $this->authorize('view', $application);

        $application->load('person');

        $pdf = $this->formPdfService->generate($application);

        $filename = 'application-'.$application->application_number.'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /** @return array<string, mixed> */
    private function printViewData(EmploymentApplication $application): array
    {
        $application->loadMissing('person');

        return [
            'application' => $application,
            'layout' => $this->printLayoutService->build($application),
            'profilePhotoUrl' => $this->formDisplayService->profilePhotoUrl($application),
            'profilePhotoDataUri' => null,
            'initials' => $this->formDisplayService->applicantInitials($application),
            'applicantName' => $this->formDisplayService->applicantDisplayName($application),
        ];
    }

    public function updateStatus(Request $request, EmploymentApplication $application): RedirectResponse
    {
        $this->authorize('updateStatus', $application);

        $validated = $request->validate([
            'status' => ['required', 'string'],
            'hr_notes' => ['nullable', 'string'],
        ]);

        $status = ApplicationStatus::from($validated['status']);

        if (! empty($validated['hr_notes'])) {
            $application->update(['hr_notes' => $validated['hr_notes']]);
        }

        $this->applicationService->updateStatus($application, $status, $request->user());

        return back()->with('success', 'وضعیت درخواست با موفقیت به‌روزرسانی شد.');
    }

    public function scheduleInterview(
        ScheduleApplicationInterviewRequest $request,
        EmploymentApplication $application,
    ): RedirectResponse {
        $this->authorize('updateStatus', $application);
        $this->authorize('create', Interview::class);

        $application->loadMissing('person');

        if (! $application->person_id) {
            return back()
                ->withErrors(['schedule' => 'این درخواست به پرسنل متصل نیست.'])
                ->withInput();
        }

        $validated = $request->validated();

        if (! empty($validated['hr_notes'])) {
            $application->update(['hr_notes' => $validated['hr_notes']]);
        }

        $this->interviewService->schedule(InterviewData::fromArray([
            'person_id' => $application->person_id,
            'employment_application_id' => $application->id,
            'type' => $validated['type'],
            'scheduled_at' => $validated['scheduled_at'],
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'location' => $validated['location'] ?? null,
            'meeting_url' => $validated['meeting_url'] ?? null,
            'interviewer_id' => $validated['interviewer_id'],
            'notes' => $validated['notes'] ?? null,
        ]));

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'مصاحبه برنامه‌ریزی شد و وضعیت درخواست به‌روزرسانی شد.');
    }

    public function export(): StreamedResponse
    {
        $this->authorize('viewAny', EmploymentApplication::class);

        $applications = EmploymentApplication::query()
            ->with('person')
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($applications) {
            $this->csvExportService->writeToStream($applications);
        }, 'applications-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function destroy(EmploymentApplication $application): RedirectResponse
    {
        $this->authorize('delete', $application);

        $this->applicationService->delete($application);

        return redirect()
            ->route('admin.applications.index')
            ->with('success', 'درخواست با موفقیت حذف شد.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        return $this->bulkDestroyModels(
            $request,
            EmploymentApplication::class,
            fn (EmploymentApplication $application) => $this->applicationService->delete($application),
            ':count درخواست با موفقیت حذف شد.',
        );
    }
}
