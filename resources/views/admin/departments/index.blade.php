<x-app-layout title="دپارتمان‌ها">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">دپارتمان‌ها</h1>
            <x-button href="{{ route('admin.departments.create') }}" variant="primary">افزودن دپارتمان</x-button>
        </div>

        <x-card>
            @if ($departments->isEmpty())
                <x-empty-state title="دپارتمانی یافت نشد">
                    <x-slot:action><x-button href="{{ route('admin.departments.create') }}" variant="primary">افزودن دپارتمان</x-button></x-slot:action>
                </x-empty-state>
            @else
                <x-data-table>
                    <x-slot:head>
                        <th class="px-4 py-3 font-medium">نام</th>
                        <th class="px-4 py-3 font-medium">کد</th>
                        <th class="px-4 py-3 font-medium">مدیر</th>
                        <th class="px-4 py-3 font-medium">وضعیت</th>
                        <th class="px-4 py-3 font-medium">عملیات</th>
                    </x-slot:head>
                    @foreach ($departments as $department)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-4 py-3 font-medium">{{ $department->name }}</td>
                            <td class="px-4 py-3">{{ $department->code }}</td>
                            <td class="px-4 py-3">{{ $department->manager?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <x-badge :variant="$department->is_active ? 'success' : 'default'">{{ $department->is_active ? 'فعال' : 'غیرفعال' }}</x-badge>
                            </td>
                            <td class="px-4 py-3">
                                <x-button href="{{ route('admin.departments.edit', $department) }}" variant="ghost" size="sm">ویرایش</x-button>
                            </td>
                        </tr>
                    @endforeach
                </x-data-table>
                <div class="mt-4">{{ $departments->links() }}</div>
            @endif
        </x-card>
    </div>
</x-app-layout>
