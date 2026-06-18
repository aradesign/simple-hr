<x-app-layout title="درخواست‌های استخدام">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">درخواست‌های استخدام</h1>
            <div class="flex flex-wrap items-center gap-2">
                <x-button
                    type="button"
                    variant="primary"
                    onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'import-applications-csv' }))"
                >
                    بارگذاری CSV
                </x-button>
                <x-button href="{{ route('admin.applications.export') }}" variant="secondary">خروجی CSV</x-button>
            </div>
        </div>

        <x-card>
            <form method="GET" id="applications-filters" class="flex flex-col sm:flex-row gap-3 mb-4">
                <x-form.input label="جستجو" name="search" :value="request('search')" class="flex-1" />
                <x-form.select label="وضعیت" name="status" class="sm:w-48">
                    <option value="">همه</option>
                    @foreach (\App\Domain\Enums\ApplicationStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </x-form.select>
                @if (request('sort'))
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                @endif
                @if (request('direction'))
                    <input type="hidden" name="direction" value="{{ request('direction') }}">
                @endif
                <div class="flex items-end"><x-button type="submit" variant="secondary">فیلتر</x-button></div>
            </form>

            @if ($applications->isEmpty())
                <x-empty-state title="درخواستی یافت نشد" />
            @else
                <x-bulk-actions
                    :action="route('admin.applications.bulk-destroy')"
                    :page-ids="$applications->pluck('id')->all()"
                >
                <x-data-table>
                    <x-slot:head>
                        <x-bulk-checkbox head />
                        <th class="px-4 py-3 font-medium"><x-table.sort-link column="application_number" label="شماره" /></th>
                        <th class="px-4 py-3 font-medium">متقاضی</th>
                        <th class="px-4 py-3 font-medium">موبایل</th>
                        <th class="px-4 py-3 font-medium"><x-table.sort-link column="gender" label="جنسیت" /></th>
                        <th class="px-4 py-3 font-medium"><x-table.sort-link column="age" label="سن" /></th>
                        <th class="px-4 py-3 font-medium"><x-table.sort-link column="preferred_department" label="محل فعالیت" /></th>
                        <th class="px-4 py-3 font-medium">وضعیت</th>
                        <th class="px-4 py-3 font-medium"><x-table.sort-link column="submitted_at" label="تاریخ" /></th>
                        <th class="px-4 py-3 font-medium">عملیات</th>
                    </x-slot:head>
                    <x-slot:filters>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th class="px-4 py-2">
                            <select name="gender" form="applications-filters" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 text-sm" onchange="document.getElementById('applications-filters').submit()">
                                <option value="">همه</option>
                                @foreach ($genderOptions as $gender)
                                    <option value="{{ $gender }}" @selected(request('gender') === $gender)>{{ $gender }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th class="px-4 py-2">
                            <input
                                type="text"
                                name="age"
                                form="applications-filters"
                                value="{{ request('age') }}"
                                placeholder="سن"
                                class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 text-sm"
                                onchange="document.getElementById('applications-filters').submit()"
                            >
                        </th>
                        <th class="px-4 py-2">
                            <input
                                type="text"
                                name="preferred_department"
                                form="applications-filters"
                                value="{{ request('preferred_department') }}"
                                placeholder="بخش"
                                list="department-options"
                                class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 text-sm"
                                onchange="document.getElementById('applications-filters').submit()"
                            >
                            <datalist id="department-options">
                                @foreach ($departmentOptions as $department)
                                    <option value="{{ $department }}"></option>
                                @endforeach
                            </datalist>
                        </th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </x-slot:filters>
                    @foreach ($applications as $application)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <x-bulk-checkbox :id="$application->id" />
                            <td class="px-4 py-3">{{ $application->application_number }}</td>
                            <td class="px-4 py-3">{{ $application->person?->full_name }}</td>
                            <td class="px-4 py-3">{{ $application->person?->mobile }}</td>
                            <td class="px-4 py-3">{{ $application->formValue('gender') ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $application->formValue('age') ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $application->formValue('preferred_department') ?: '—' }}</td>
                            <td class="px-4 py-3"><x-badge :variant="$application->status->value">{{ $application->status->label() }}</x-badge></td>
                            <td class="px-4 py-3"><x-jalali-date :date="$application->submitted_at ?? $application->created_at" /></td>
                            <td class="px-4 py-3">
                                <x-button href="{{ route('admin.applications.show', $application) }}" variant="ghost" size="sm">مشاهده</x-button>
                            </td>
                        </tr>
                    @endforeach
                </x-data-table>
                </x-bulk-actions>
                <div class="mt-4">{{ $applications->links() }}</div>
            @endif
        </x-card>
    </div>

    @include('admin.applications._csv-import-modal')
</x-app-layout>
