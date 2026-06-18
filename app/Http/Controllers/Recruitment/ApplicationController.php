<?php

namespace App\Http\Controllers\Recruitment;

use App\Domain\Enums\ApplicationStatus;
use App\Domain\Enums\FormFieldType;
use App\Http\Controllers\Controller;
use App\Models\ApplicationFormField;
use App\Models\EmploymentApplication;
use App\Rules\IranianNationalIdRule;
use App\Services\Recruitment\ApplicationFormSchemaService;
use App\Services\Recruitment\ApplicationPersonSyncService;
use App\Services\Recruitment\ApplicationService;
use App\Services\Recruitment\ConditionalLogicEvaluator;
use App\Services\Recruitment\RecruitmentAccessService;
use App\Support\EmploymentFormFields;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function __construct(
        private readonly ApplicationService $applicationService,
        private readonly ApplicationFormSchemaService $schemaService,
        private readonly RecruitmentAccessService $accessService,
        private readonly ConditionalLogicEvaluator $conditionalLogic,
        private readonly ApplicationPersonSyncService $personSync,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $contactMobile = $this->accessService->contactMobile($request);
        $application = $this->applicationService->createForContact($contactMobile);

        return redirect()
            ->route('recruitment.applications.form', $application)
            ->with('success', 'درخواست جدید ایجاد شد. لطفاً فرم را تکمیل کنید.');
    }

    public function edit(Request $request, EmploymentApplication $application): View|RedirectResponse
    {
        $this->accessService->authorizeApplication($request, $application);

        if ($application->status !== ApplicationStatus::Draft) {
            return redirect()
                ->route('recruitment.applications.status', $application)
                ->with('error', 'این درخواست قبلاً ارسال شده است.');
        }

        $fields = $this->schemaService->getAllFields();
        $formData = EmploymentFormFields::normalizeFormData($application->form_data ?? []);

        if (! isset($formData['age'])) {
            $age = $this->personSync->computeAge($formData);
            if ($age !== null) {
                $formData['age'] = $age;
            }
        }

        return view('recruitment.application.form', [
            'application' => $application->load('person'),
            'fields' => $fields,
            'fieldsPayload' => $this->schemaService->fieldsPayload($fields),
            'formData' => $formData,
        ]);
    }

    public function update(Request $request, EmploymentApplication $application): RedirectResponse
    {
        $this->accessService->authorizeApplication($request, $application);

        $fields = $this->schemaService->getAllFields();
        $incoming = $request->input('form_data', []);
        $merged = EmploymentFormFields::normalizeFormData(array_merge(
            EmploymentFormFields::normalizeFormData($application->form_data ?? []),
            is_array($incoming) ? $incoming : [],
        ));

        foreach ($fields as $field) {
            if ($field->field_type === FormFieldType::File && $request->hasFile("form_files.{$field->field_key}")) {
                $path = $request->file("form_files.{$field->field_key}")->store('recruitment/uploads', 'public');
                $merged[$field->field_key] = $path;
            }
        }

        $age = $this->personSync->computeAge($merged);
        if ($age !== null) {
            $merged['age'] = $age;
        }

        $request->merge(['form_data' => $merged]);

        $visibleFields = $this->conditionalLogic->visibleFields($fields, $merged);
        $rules = $this->buildValidationRules($visibleFields, (bool) $request->boolean('submit'));

        if ($rules !== []) {
            $request->validate($rules);
        }

        $this->personSync->sync($application, $merged);

        if ($request->boolean('submit')) {
            $this->applicationService->submit($application, $merged);

            return redirect()
                ->route('recruitment.applications.status', $application)
                ->with('success', 'درخواست استخدام با موفقیت ارسال شد.');
        }

        $this->applicationService->saveDraft($application, $merged);

        return redirect()
            ->route('recruitment.applications.form', $application)
            ->with('success', 'اطلاعات ذخیره شد.');
    }

    public function status(Request $request, EmploymentApplication $application): View
    {
        $this->accessService->authorizeApplication($request, $application);

        $application->load(['person', 'interviews']);

        return view('recruitment.application.track', [
            'application' => $application,
            'person' => $application->person,
        ]);
    }

    private function buildValidationRules($visibleFields, bool $submitting): array
    {
        $rules = [];

        foreach ($visibleFields as $field) {
            if (! $field->is_required || $field->field_type === FormFieldType::Hidden) {
                continue;
            }

            $key = "form_data.{$field->field_key}";

            $rules[$key] = match ($field->field_type) {
                FormFieldType::Email => ['required', 'email'],
                FormFieldType::Number, FormFieldType::Hidden => ['required', 'numeric'],
                FormFieldType::Date => ['required', 'string'],
                FormFieldType::NationalId => ['required', 'string', 'size:10', new IranianNationalIdRule],
                FormFieldType::Tel, FormFieldType::Text => ['required', 'string', 'max:500'],
                FormFieldType::Textarea => ['required', 'string', 'max:5000'],
                FormFieldType::Select, FormFieldType::Radio => ['required', 'string'],
                FormFieldType::Checkbox => ['required', 'array', 'min:1'],
                FormFieldType::List => ['required', 'array', 'min:1'],
                FormFieldType::File => $submitting ? ['required', 'string'] : ['nullable', 'string'],
                default => ['required'],
            };
        }

        return $rules;
    }
}
