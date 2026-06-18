@props([
    'action',
    'pageIds' => [],
])

@php
    $enabled = auth()->user()?->canManageHr() ?? false;
    $ids = array_values($pageIds);
@endphp

<div
    @if ($enabled)
        x-data="bulkTable({ ids: @js($ids), deleteUrl: @js($action) })"
    @endif
    {{ $attributes }}
>
    @if ($enabled)
        <div
            x-show="selected.length > 0"
            x-cloak
            class="flex flex-wrap items-center justify-between gap-3 mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-500/20"
        >
            <span class="text-sm font-medium text-red-800 dark:text-red-300" x-text="selected.length + ' مورد انتخاب شده'"></span>
            <form method="POST" :action="deleteUrl" @submit.prevent="submitDelete($event)">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger" size="sm">حذف انتخاب‌شده‌ها</x-button>
            </form>
        </div>
    @endif

    {{ $slot }}
</div>
