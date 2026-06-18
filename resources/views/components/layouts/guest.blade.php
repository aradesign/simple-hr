@props(['title' => null])

@php
    $scanlines = ($appSettings['appearance']['show_scanlines'] ?? true) ? 'dark:cyber-scanlines' : '';
@endphp

<!DOCTYPE html>
<html dir="rtl" lang="fa" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ورود' }} — {{ $siteName ?? 'سامانه منابع انسانی' }}</title>
    @if(!empty($faviconUrl))
        <link rel="icon" href="{{ $faviconUrl }}">
    @endif
    <x-settings-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-sm md:text-base cyber-bg {{ $scanlines }} text-slate-900 dark:text-slate-100 antialiased min-h-full flex flex-col">
    <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-cyan-500/20 bg-white/80 dark:bg-transparent backdrop-blur-sm">
        <div class="flex items-center gap-2">
            @if(!empty($logoUrl))
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-8 w-auto rounded">
            @else
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-600 to-purple-600 dark:from-cyan-400 dark:to-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            @endif
            <div>
                <span class="font-bold text-slate-800 dark:text-accent">{{ $siteName ?? 'سامانه منابع انسانی' }}</span>
                @if(!empty($appSettings['branding']['site_tagline']))
                    <p class="text-xs text-slate-500 dark:text-slate-400 hidden sm:block">{{ $appSettings['branding']['site_tagline'] }}</p>
                @endif
            </div>
        </div>
        <button
            @click="$store.theme.toggle()"
            class="p-2 rounded-lg border border-slate-300 dark:border-cyan-500/30 text-slate-600 dark:text-cyan-400 hover:bg-slate-100 dark:hover:bg-cyan-500/10 transition-colors"
            aria-label="تغییر تم"
        >
            <svg x-show="!$store.theme.dark" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
            <svg x-show="$store.theme.dark" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
        </button>
    </div>
    <main class="flex-1 flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            {{ $slot }}
        </div>
    </main>
    <x-app-footer />
</body>
</html>
