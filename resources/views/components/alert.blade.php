@props(['type' => 'info'])

@php
    $types = [
        'success' => 'bg-green-50 border-green-300 text-green-800 dark:bg-green-900/20 dark:border-green-700 dark:text-green-300',
        'warning' => 'bg-amber-50 border-amber-300 text-amber-800 dark:bg-amber-900/20 dark:border-amber-700 dark:text-amber-300',
        'error' => 'bg-red-50 border-red-300 text-red-800 dark:bg-red-900/20 dark:border-red-700 dark:text-red-300',
        'info' => 'bg-cyan-50 border-cyan-300 text-cyan-800 dark:bg-cyan-900/20 dark:border-cyan-700 dark:text-cyan-300',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg border p-4 text-sm ' . ($types[$type] ?? $types['info'])]) }} role="alert">
    {{ $slot }}
</div>
