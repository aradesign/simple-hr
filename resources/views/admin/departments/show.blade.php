<x-app-layout :title="$department->name">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $department->name }}</h1>
                <p class="text-sm text-slate-500">کد: {{ $department->code }}</p>
            </div>
            <x-button href="{{ route('admin.departments.edit', $department) }}" variant="secondary">ویرایش</x-button>
        </div>

        <x-card title="اطلاعات">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div><dt class="text-slate-500">مدیر</dt><dd>{{ $department->manager?->name ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">وضعیت</dt><dd><x-badge :variant="$department->is_active ? 'success' : 'default'">{{ $department->is_active ? 'فعال' : 'غیرفعال' }}</x-badge></dd></div>
                <div class="sm:col-span-2"><dt class="text-slate-500">توضیحات</dt><dd>{{ $department->description ?? '—' }}</dd></div>
            </dl>
        </x-card>

        <x-card title="پرسنل ({{ $department->persons->count() }})">
            @forelse ($department->persons as $person)
                <div class="py-2 border-b border-slate-200 dark:border-slate-700 last:border-0 text-sm">
                    <a href="{{ route('admin.persons.show', $person) }}" class="text-blue-600 hover:underline">{{ $person->full_name }}</a>
                </div>
            @empty
                <x-empty-state title="پرسنلی در این دپارتمان نیست" />
            @endforelse
        </x-card>
    </div>
</x-app-layout>
