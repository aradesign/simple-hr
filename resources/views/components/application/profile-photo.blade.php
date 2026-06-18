@props([
    'photoUrl' => null,
    'initials' => '؟',
    'name' => null,
    'subtitle' => null,
    'size' => 'lg',
])

@php
    $sizes = [
        'lg' => 'w-28 h-28 text-2xl rounded-2xl',
        'md' => 'w-20 h-20 text-xl rounded-xl',
        'sm' => 'w-16 h-16 text-lg rounded-lg',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['lg'];
@endphp

<div class="flex flex-col sm:flex-row items-center sm:items-start gap-4">
    @if ($photoUrl)
        <img
            src="{{ $photoUrl }}"
            alt="عکس پروفایل {{ $name }}"
            class="{{ $sizeClass }} object-cover ring-2 ring-cyan-500/20 shadow-sm shrink-0"
        >
    @else
        <div class="{{ $sizeClass }} shrink-0 flex items-center justify-center bg-gradient-to-br from-cyan-500/15 via-teal-500/10 to-slate-200/40 dark:from-cyan-500/20 dark:via-teal-500/10 dark:to-slate-700/40 text-cyan-700 dark:text-cyan-300 font-bold ring-2 ring-cyan-500/10">
            {{ $initials }}
        </div>
    @endif

    @if ($name || $subtitle)
        <div class="text-center sm:text-right min-w-0">
            @if ($name)
                <p class="text-lg font-semibold text-slate-900 dark:text-white">{{ $name }}</p>
            @endif
            @if ($subtitle)
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ $subtitle }}</p>
            @endif
        </div>
    @endif
</div>
