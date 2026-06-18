@props(['name' => 'modal', 'title' => null, 'maxWidth' => 'lg'])

@php
    $maxWidths = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
    ];
@endphp

<div x-data="modal" x-on:open-modal.window="if ($event.detail === '{{ $name }}') show()" x-on:close-modal.window="if ($event.detail === '{{ $name }}') hide()">
    <div
        x-show="open"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50"
        x-cloak
        @keydown.escape.window="hide()"
    >
        <div
            x-show="open"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.outside="hide()"
            class="w-full {{ $maxWidths[$maxWidth] ?? $maxWidths['lg'] }} bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700"
        >
            @if ($title)
                <div class="flex items-center justify-between p-4 md:p-6 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $title }}</h3>
                    <button @click="hide()" class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700" aria-label="بستن">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @endif
            <div class="p-4 md:p-6">
                {{ $slot }}
            </div>
            @isset($footer)
                <div class="flex items-center justify-end gap-2 p-4 md:p-6 border-t border-slate-200 dark:border-slate-700">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
