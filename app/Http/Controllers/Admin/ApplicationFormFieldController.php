<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationFormField;
use App\Services\Recruitment\ApplicationFormSchemaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationFormFieldController extends Controller
{
    public function __construct(
        private readonly ApplicationFormSchemaService $schemaService,
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->canManageHr(), 403);

        $fields = ApplicationFormField::query()->ordered()->get();
        $fieldsByStep = $this->schemaService->getAllVisibleFields();

        return view('admin.form-fields.index', compact('fields', 'fieldsByStep'));
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->canManageHr(), 403);

        $validated = $request->validate([
            'fields' => ['required', 'array'],
            'fields.*.id' => ['required', 'exists:application_form_fields,id'],
            'fields.*.sort_order' => ['required', 'integer', 'min:0'],
            'fields.*.is_visible' => ['required', 'boolean'],
            'fields.*.step' => ['nullable', 'integer', 'min:1'],
        ]);

        foreach ($validated['fields'] as $fieldData) {
            ApplicationFormField::query()
                ->whereKey($fieldData['id'])
                ->update([
                    'sort_order' => $fieldData['sort_order'],
                    'is_visible' => $fieldData['is_visible'],
                    'step' => $fieldData['step'] ?? ApplicationFormField::query()->find($fieldData['id'])?->step,
                ]);
        }

        return back()->with('success', 'تنظیمات فیلدهای فرم با موفقیت ذخیره شد.');
    }
}
