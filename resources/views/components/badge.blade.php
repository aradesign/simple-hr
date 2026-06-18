@props(['variant' => 'default'])

@php
    $variants = [
        'applicant' => 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
        'interviewed' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-300',
        'accepted' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
        'employee' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
        'former_employee' => 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
        'pending' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
        'default' => 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
        'success' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
        'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
        'danger' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
        'info' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-300',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . ($variants[$variant] ?? $variants['default'])]) }}>
    {{ $slot }}
</span>
