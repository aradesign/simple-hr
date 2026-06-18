<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Enums\DocumentType;
use App\DTOs\PersonData;
use App\Helpers\JalaliHelper;
use App\Http\Controllers\Concerns\BulkDestroysModels;
use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Rules\IranianNationalIdRule;
use App\Services\Person\PersonService;
use App\Support\IranianNationalId;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PersonController extends Controller
{
    use BulkDestroysModels;

    public function __construct(
        private readonly PersonService $personService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Person::class);

        $persons = Person::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhere('national_id', 'like', "%{$search}%");
                });
            })
            ->when(
                $request->filled('lifecycle_status'),
                fn ($query) => $query->where('lifecycle_status', $request->string('lifecycle_status')),
                fn ($query) => $query->inPersonnelRoster(),
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.persons.index', compact('persons'));
    }

    public function create(): View
    {
        $this->authorize('create', Person::class);

        return view('admin.persons.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Person::class);

        if ($request->filled('birth_date')) {
            $request->merge([
                'birth_date' => JalaliHelper::normalizeDateString($request->input('birth_date')),
            ]);
        }

        if ($request->filled('national_id')) {
            $request->merge([
                'national_id' => IranianNationalId::normalize($request->input('national_id')),
            ]);
        }

        if (! $request->filled('lifecycle_status')) {
            $request->merge([
                'lifecycle_status' => \App\Domain\Enums\PersonLifecycleStatus::Employee->value,
            ]);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'national_id' => ['nullable', 'string', 'size:10', 'unique:persons,national_id', new IranianNationalIdRule],
            'mobile' => ['nullable', 'string', 'max:20', 'unique:persons,mobile'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'string'],
            'lifecycle_status' => ['nullable', 'string'],
            'marital_status' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
        ]);

        $person = $this->personService->create(PersonData::fromArray($validated));

        return redirect()
            ->route('admin.persons.show', $person)
            ->with('success', 'پرونده با موفقیت ایجاد شد.');
    }

    public function show(Person $person): View
    {
        $this->authorize('view', $person);

        $person->load([
            'educations',
            'workExperiences',
            'familyMembers',
            'documents.latestVersion',
            'employmentApplications',
            'interviews.interviewer',
            'employmentRecords.department',
            'calendarEvents',
            'assignments.user',
            'departments',
        ]);

        return view('admin.persons.show', [
            'person' => $person,
            'documentTypes' => DocumentType::cases(),
            'tabs' => [
                'profile' => $person->only([
                    'first_name', 'last_name', 'national_id', 'mobile', 'birth_date',
                    'gender', 'lifecycle_status', 'marital_status', 'address', 'city', 'province',
                ]),
                'educations' => $person->educations,
                'work_experiences' => $person->workExperiences,
                'family_members' => $person->familyMembers,
                'documents' => $person->documents,
                'applications' => $person->employmentApplications,
                'interviews' => $person->interviews,
                'employment_records' => $person->employmentRecords,
                'calendar_events' => $person->calendarEvents,
                'assignments' => $person->assignments,
                'departments' => $person->departments,
            ],
        ]);
    }

    public function edit(Person $person): View
    {
        $this->authorize('update', $person);

        return view('admin.persons.edit', compact('person'));
    }

    public function update(Request $request, Person $person): RedirectResponse
    {
        $this->authorize('update', $person);

        if ($request->filled('birth_date')) {
            $request->merge([
                'birth_date' => JalaliHelper::normalizeDateString($request->input('birth_date')),
            ]);
        }

        if ($request->filled('national_id')) {
            $request->merge([
                'national_id' => IranianNationalId::normalize($request->input('national_id')),
            ]);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'national_id' => ['nullable', 'string', 'size:10', 'unique:persons,national_id,'.$person->id, new IranianNationalIdRule],
            'mobile' => ['nullable', 'string', 'max:20', 'unique:persons,mobile,'.$person->id],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'string'],
            'lifecycle_status' => ['nullable', 'string'],
            'marital_status' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->personService->update($person, PersonData::fromArray($validated));

        return redirect()
            ->route('admin.persons.show', $person)
            ->with('success', 'پرونده با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Person $person): RedirectResponse
    {
        $this->authorize('delete', $person);

        $this->personService->delete($person);

        return redirect()
            ->route('admin.persons.index')
            ->with('success', 'پرونده با موفقیت حذف شد.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        return $this->bulkDestroyModels(
            $request,
            Person::class,
            fn (Person $person) => $this->personService->delete($person),
            ':count پرونده با موفقیت حذف شد.',
        );
    }
}
