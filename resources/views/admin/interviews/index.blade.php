<x-app-layout title="مصاحبه‌ها">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">مصاحبه‌ها</h1>
            <x-button href="{{ route('admin.interviews.create') }}" variant="primary">برنامه‌ریزی مصاحبه</x-button>
        </div>

        <x-card>
            <form method="GET" class="flex flex-col sm:flex-row gap-3 mb-6">
                <x-form.jalali-date label="تاریخ" name="date" :value="request('date')" class="sm:w-56" />
                <x-form.select label="وضعیت" name="status" class="sm:w-48">
                    <option value="">همه</option>
                    @foreach (\App\Domain\Enums\InterviewStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </x-form.select>
                <div class="flex items-end"><x-button type="submit" variant="secondary">فیلتر</x-button></div>
            </form>

            @if ($interviews->isEmpty())
                <x-empty-state title="مصاحبه‌ای یافت نشد">
                    <x-slot:action><x-button href="{{ route('admin.interviews.create') }}" variant="primary">برنامه‌ریزی مصاحبه</x-button></x-slot:action>
                </x-empty-state>
            @else
                <x-bulk-actions
                    :action="route('admin.interviews.bulk-destroy')"
                    :page-ids="$interviews->pluck('id')->all()"
                >
                <x-data-table>
                    <x-slot:head>
                        <x-bulk-checkbox head />
                        <th class="px-4 py-3 font-medium">متقاضی</th>
                        <th class="px-4 py-3 font-medium">نوع</th>
                        <th class="px-4 py-3 font-medium">زمان</th>
                        <th class="px-4 py-3 font-medium">وضعیت</th>
                        <th class="px-4 py-3 font-medium">عملیات</th>
                    </x-slot:head>
                    @foreach ($interviews as $interview)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <x-bulk-checkbox :id="$interview->id" />
                            <td class="px-4 py-3">{{ $interview->person?->full_name }}</td>
                            <td class="px-4 py-3">{{ $interview->type?->label() }}</td>
                            <td class="px-4 py-3"><x-jalali-date :date="$interview->scheduled_at" format="Y/m/d H:i" /></td>
                            <td class="px-4 py-3"><x-badge variant="info">{{ $interview->status?->label() }}</x-badge></td>
                            <td class="px-4 py-3">
                                <x-button href="{{ route('admin.interviews.edit', $interview) }}" variant="ghost" size="sm">ویرایش</x-button>
                            </td>
                        </tr>
                    @endforeach
                </x-data-table>
                </x-bulk-actions>
                <div class="mt-4">{{ $interviews->links() }}</div>
            @endif
        </x-card>
    </div>
</x-app-layout>
