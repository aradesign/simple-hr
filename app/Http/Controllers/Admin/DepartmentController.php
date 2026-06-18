<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Department::class);

        $departments = Department::query()
            ->with(['manager', 'persons'])
            ->ordered()
            ->paginate(20);

        return view('admin.departments.index', compact('departments'));
    }

    public function create(): View
    {
        $this->authorize('create', Department::class);

        return view('admin.departments.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Department::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:departments,code'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'manager_id' => ['nullable', 'exists:users,id'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $department = Department::query()->create($validated);

        return redirect()
            ->route('admin.departments.show', $department)
            ->with('success', 'دپارتمان با موفقیت ایجاد شد.');
    }

    public function show(Department $department): View
    {
        $this->authorize('view', $department);

        $department->load(['manager', 'persons', 'employmentRecords.person']);

        return view('admin.departments.show', compact('department'));
    }

    public function edit(Department $department): View
    {
        $this->authorize('update', $department);

        return view('admin.departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $this->authorize('update', $department);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:departments,code,'.$department->id],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'manager_id' => ['nullable', 'exists:users,id'],
        ]);

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $department->update($validated);

        return redirect()
            ->route('admin.departments.show', $department)
            ->with('success', 'دپارتمان با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        $department->delete();

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'دپارتمان با موفقیت حذف شد.');
    }
}
