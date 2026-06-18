@props(['icon', 'value', 'label', 'color' => 'cyan'])

@php
    $colors = [
        'cyan' => ['icon' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-400', 'stat' => 'stat-cyan'],
        'blue' => ['icon' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400', 'stat' => 'stat-blue'],
        'green' => ['icon' => 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-400', 'stat' => 'stat-green'],
        'amber' => ['icon' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400', 'stat' => 'stat-amber'],
        'red' => ['icon' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400', 'stat' => 'stat-magenta'],
        'purple' => ['icon' => 'bg-purple-100 text-purple-700 dark:bg-purple-500/15 dark:text-purple-400', 'stat' => 'stat-purple'],
        'emerald' => ['icon' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400', 'stat' => 'stat-green'],
        'magenta' => ['icon' => 'bg-pink-100 text-pink-700 dark:bg-pink-500/15 dark:text-pink-400', 'stat' => 'stat-magenta'],
    ];
    $c = $colors[$color] ?? $colors['cyan'];
@endphp

<div {{ $attributes->merge(['class' => 'cyber-panel rounded-xl p-4 md:p-6 ' . $c['stat']]) }}>
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-lg flex items-center justify-center shrink-0 {{ $c['icon'] }}">
            {!! $icon !!}
        </div>
        <div>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $value }}</p>
            <p class="text-sm text-slate-600 dark:text-slate-400">{{ $label }}</p>
        </div>
    </div>
</div>
