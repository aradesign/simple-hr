@props(['title' => 'موردی یافت نشد', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center py-12 px-4 text-center']) }}>
    <div class="w-16 h-16 mb-4 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-400">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
    </div>
    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">{{ $title }}</h3>
    @if ($description)
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 max-w-sm">{{ $description }}</p>
    @endif
    @isset($action)
        <div>{{ $action }}</div>
    @endisset
</div>
