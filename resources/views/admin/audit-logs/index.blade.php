<x-app-layout title="گزارش فعالیت">
    <div class="space-y-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">گزارش فعالیت</h1>

        <x-card>
            <form method="GET" class="flex flex-col sm:flex-row gap-3 mb-6">
                <x-form.input label="عملیات" name="action" :value="request('action')" class="sm:w-48" />
                <div class="flex items-end"><x-button type="submit" variant="secondary">فیلتر</x-button></div>
            </form>

            @if ($auditLogs->isEmpty())
                <x-empty-state title="لاگی یافت نشد" />
            @else
                <x-data-table>
                    <x-slot:head>
                        <th class="px-4 py-3 font-medium">کاربر</th>
                        <th class="px-4 py-3 font-medium">عملیات</th>
                        <th class="px-4 py-3 font-medium">موجودیت</th>
                        <th class="px-4 py-3 font-medium">تاریخ</th>
                    </x-slot:head>
                    @foreach ($auditLogs as $log)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-4 py-3">{{ $log->user?->name ?? 'سیستم' }}</td>
                            <td class="px-4 py-3">{{ $log->action?->label() ?? $log->action }}</td>
                            <td class="px-4 py-3 text-xs">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</td>
                            <td class="px-4 py-3"><x-jalali-date :date="$log->created_at" format="Y/m/d H:i" /></td>
                        </tr>
                    @endforeach
                </x-data-table>
                <div class="mt-4">{{ $auditLogs->links() }}</div>
            @endif
        </x-card>
    </div>
</x-app-layout>
