<x-app-layout title="پرسنل">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">مدیریت پرسنل</h1>
            <div class="flex flex-wrap items-center gap-2">
                <x-button
                    type="button"
                    variant="primary"
                    onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'import-persons-csv' }))"
                >
                    بارگذاری CSV
                </x-button>
                <x-button href="{{ route('admin.persons.create') }}" variant="secondary">افزودن پرسنل</x-button>
            </div>
        </div>

        <x-card>
            <form method="GET" class="flex flex-col sm:flex-row gap-3 mb-6">
                <x-form.input label="جستجو" name="search" :value="request('search')" placeholder="نام، موبایل، کد ملی..." class="flex-1" />
                <x-form.select label="وضعیت" name="lifecycle_status" class="sm:w-48">
                    <option value="">همه پرسنل</option>
                    @foreach (\App\Domain\Enums\PersonLifecycleStatus::personnelRosterCases() as $status)
                        <option value="{{ $status->value }}" @selected(request('lifecycle_status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </x-form.select>
                <div class="flex items-end">
                    <x-button type="submit" variant="secondary">فیلتر</x-button>
                </div>
            </form>

            @if ($persons->isEmpty())
                <x-empty-state title="پرسنلی یافت نشد" description="برای شروع یک پرونده جدید ایجاد کنید.">
                    <x-slot:action>
                        <x-button href="{{ route('admin.persons.create') }}" variant="primary">افزودن پرسنل</x-button>
                    </x-slot:action>
                </x-empty-state>
            @else
                <x-bulk-actions
                    :action="route('admin.persons.bulk-destroy')"
                    :page-ids="$persons->pluck('id')->all()"
                >
                <x-data-table>
                    <x-slot:head>
                        <x-bulk-checkbox head />
                        <th class="px-4 py-3 font-medium">نام</th>
                        <th class="px-4 py-3 font-medium">موبایل</th>
                        <th class="px-4 py-3 font-medium">وضعیت</th>
                        <th class="px-4 py-3 font-medium">عملیات</th>
                    </x-slot:head>
                    @foreach ($persons as $person)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <x-bulk-checkbox :id="$person->id" />
                            <td class="px-4 py-3 font-medium">{{ $person->full_name }}</td>
                            <td class="px-4 py-3" dir="ltr">{{ $person->display_mobile ?? '—' }}</td>
                            <td class="px-4 py-3"><x-badge :variant="$person->lifecycle_status->value">{{ $person->lifecycle_status->label() }}</x-badge></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <x-button href="{{ route('admin.persons.show', $person) }}" variant="ghost" size="sm">مشاهده</x-button>
                                    <x-button href="{{ route('admin.persons.edit', $person) }}" variant="ghost" size="sm">ویرایش</x-button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-data-table>
                </x-bulk-actions>
                <div class="mt-4">{{ $persons->links() }}</div>
            @endif
        </x-card>
    </div>

    @include('admin.persons._csv-import-modal')
</x-app-layout>
