@php
    $menuItems = [
        ['route' => 'admin.dashboard', 'label' => 'داشبورد', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'pattern' => 'admin.dashboard'],
        ['route' => 'admin.persons.index', 'label' => 'پرسنل', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'pattern' => 'admin.persons.*'],
        ['route' => 'admin.applications.index', 'label' => 'درخواست‌های استخدام', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'pattern' => 'admin.applications.*'],
        ['route' => 'admin.interviews.index', 'label' => 'مصاحبه‌ها', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'pattern' => 'admin.interviews.*'],
        ['route' => 'admin.tickets.index', 'label' => 'تیکت‌های HR', 'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z', 'pattern' => 'admin.tickets.*'],
        ['route' => 'admin.calendar.index', 'label' => 'تقویم HR', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'pattern' => 'admin.calendar.*'],
        ['route' => 'admin.documents.index', 'label' => 'اسناد', 'icon' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z', 'pattern' => 'admin.documents.*'],
        ['route' => 'admin.departments.index', 'label' => 'دپارتمان‌ها', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'pattern' => 'admin.departments.*'],
        ['route' => 'admin.users.index', 'label' => 'کاربران', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'pattern' => 'admin.users.*'],
        ['route' => 'admin.form-fields.index', 'label' => 'تنظیمات فرم', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'pattern' => 'admin.form-fields.*'],
        ['route' => 'admin.audit-logs.index', 'label' => 'گزارش فعالیت', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'pattern' => 'admin.audit-logs.*'],
    ];

    if (auth()->user()?->canManageSettings()) {
        $menuItems[] = ['route' => 'admin.settings.index', 'label' => 'تنظیمات سیستم', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'pattern' => 'admin.settings.*'];
    }
@endphp

<aside
    :class="open ? 'translate-x-0' : 'translate-x-full md:translate-x-0'"
    class="fixed top-16 right-0 z-40 w-64 h-[calc(100vh-4rem)] cyber-panel border-l border-slate-200 dark:border-cyan-500/20 transition-transform duration-200 ease-in-out md:translate-x-0 overflow-y-auto"
>
    <nav class="p-4 space-y-1">
        @foreach ($menuItems as $item)
            @php $active = request()->routeIs($item['pattern']); @endphp
            <a
                href="{{ route($item['route']) }}"
                @click="closeSidebar()"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $active ? 'cyber-nav-active' : 'text-slate-600 dark:text-slate-400 hover:text-cyan-700 dark:hover:text-cyan-400 hover:bg-slate-100 dark:hover:bg-cyan-500/5' }}"
            >
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}" />
                </svg>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
</aside>
