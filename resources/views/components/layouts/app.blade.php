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
    <title>{{ $title ? $title . ' — ' : '' }}{{ $siteName ?? 'سامانه منابع انسانی' }}</title>
    @if(!empty($faviconUrl))
        <link rel="icon" href="{{ $faviconUrl }}">
    @endif
    <x-settings-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-sm md:text-base cyber-bg {{ $scanlines }} text-slate-900 dark:text-slate-100 antialiased min-h-full" x-data="sidebar">
    <div class="min-h-screen flex flex-col">
        <x-header />

        <div class="flex flex-1 pt-16">
            <x-sidebar />

            <div
                x-show="open"
                x-transition.opacity
                @click="closeSidebar()"
                class="fixed inset-0 z-30 bg-slate-900/60 backdrop-blur-sm md:hidden"
                x-cloak
            ></div>

            <main class="flex-1 w-full md:mr-64 p-4 md:p-6">
                @isset($hero)
                    {{ $hero }}
                @endisset

                @if (session('success'))
                    <div class="mb-4">
                        <x-alert type="success">{{ session('success') }}</x-alert>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4">
                        <x-alert type="error">{{ session('error') }}</x-alert>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4">
                        <x-alert type="error">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-alert>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>

        <x-app-footer />
    </div>
</body>
</html>
