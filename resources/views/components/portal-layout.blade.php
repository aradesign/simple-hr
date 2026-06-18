@props(['title' => null])

@php
    $settings = app(\App\Services\Settings\SettingService::class);
    $branding = $settings->group('branding');
    $texts = $settings->group('texts');
    $appearance = $settings->group('appearance');
    $siteName = $branding['site_name'] ?? 'پورتال پرسنلی';
    $logoUrl = $settings->brandingUrl($branding['logo_path'] ?? null);
    $faviconUrl = $settings->brandingUrl($branding['favicon_path'] ?? null);
    $scanlines = ($appearance['show_scanlines'] ?? true) ? 'dark:cyber-scanlines' : '';
    $portalTitle = $texts['portal_title'] ?? 'پورتال پرسنلی';
@endphp

<!DOCTYPE html>
<html dir="rtl" lang="fa" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title . ' — ' : '' }}{{ $portalTitle }}</title>
    @if($faviconUrl)
        <link rel="icon" href="{{ $faviconUrl }}">
    @endif
    <x-settings-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-sm md:text-base cyber-bg {{ $scanlines }} text-slate-900 dark:text-slate-100 antialiased min-h-full flex flex-col">
    <header class="cyber-panel border-b border-slate-200 dark:border-cyan-500/20">
        <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
            <a href="{{ route('portal.dashboard') }}" class="flex items-center gap-2 font-bold text-slate-800 dark:text-accent">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-7 w-auto rounded">
                @endif
                {{ $portalTitle }}
            </a>
            <nav class="flex items-center gap-1 sm:gap-4 text-sm">
                <a href="{{ route('portal.dashboard') }}" class="px-2 py-1 rounded text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-cyan-500/10 {{ request()->routeIs('portal.dashboard') ? 'text-cyan-700 dark:text-cyan-400 font-medium' : '' }}">داشبورد</a>
                <a href="{{ route('portal.profile') }}" class="px-2 py-1 rounded text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-cyan-500/10 {{ request()->routeIs('portal.profile*') ? 'text-cyan-700 dark:text-cyan-400 font-medium' : '' }}">پروفایل</a>
                <a href="{{ route('portal.documents') }}" class="px-2 py-1 rounded text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-cyan-500/10 {{ request()->routeIs('portal.documents*') ? 'text-cyan-700 dark:text-cyan-400 font-medium' : '' }}">اسناد</a>
                <a href="{{ route('portal.tickets.index') }}" class="px-2 py-1 rounded text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-cyan-500/10 {{ request()->routeIs('portal.tickets.*') ? 'text-cyan-700 dark:text-cyan-400 font-medium' : '' }}">تیکت HR</a>
                <a href="{{ route('portal.notifications') }}" class="px-2 py-1 rounded text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-cyan-500/10 {{ request()->routeIs('portal.notifications') ? 'text-cyan-700 dark:text-cyan-400 font-medium' : '' }}">اعلان‌ها</a>
                <button
                    @click="$store.theme.toggle()"
                    class="p-2 rounded-lg border border-slate-300 dark:border-cyan-500/30 text-slate-600 dark:text-cyan-400 hover:bg-slate-100 dark:hover:bg-cyan-500/10"
                    aria-label="تغییر تم"
                >
                    <svg x-show="!$store.theme.dark" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg x-show="$store.theme.dark" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>
                <form method="POST" action="{{ route('portal.logout') }}">
                    @csrf
                    <button type="submit" class="text-red-600 dark:text-pink-400 text-sm hover:underline">خروج</button>
                </form>
            </nav>
        </div>
    </header>
    <main class="flex-1 max-w-4xl mx-auto p-4 md:p-6 w-full">
        @if (session('success'))
            <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
        @endif
        {{ $slot }}
    </main>
    <x-app-footer />
</body>
</html>
