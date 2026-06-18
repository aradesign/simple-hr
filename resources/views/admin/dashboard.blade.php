<x-layouts.app title="داشبورد">
    <div class="space-y-6">
        {{-- Hero Banner --}}
        <div class="cyber-hero rounded-xl p-6 md:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <p class="text-sm text-cyan-700 dark:text-cyan-400 mb-1">{{ \App\Helpers\SettingsHelper::text('welcome_message', [], 'خوش آمدید') }}</p>
                    <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-white">
                        {{ \App\Helpers\SettingsHelper::text('dashboard_greeting', [], 'سلام') }}، <span class="text-accent">{{ auth()->user()->name }}</span>
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">
                        {{ \App\Helpers\JalaliHelper::format(now()) }}
                    </p>
                </div>
                <div class="hidden md:block">
                    <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-cyan-400/20 to-purple-600/20 border border-cyan-500/30 flex items-center justify-center neon-glow-cyan">
                        <svg class="w-8 h-8 text-cyan-500 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <x-stat-card
                :value="$stats['applicants_count']"
                label="متقاضیان فعال"
                color="cyan"
                :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z\' /></svg>'"
            />
            <x-stat-card
                :value="$stats['today_interviews_count']"
                label="مصاحبه‌های امروز"
                color="purple"
                :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z\' /></svg>'"
            />
            <x-stat-card
                :value="$stats['today_birthdays_count'] ?? 0"
                label="تولدهای امروز"
                color="magenta"
                :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.701 2.701 0 00-1.5-.454M9 6v2m3-2v2m3-2v2M9 3h.01M12 3h.01M15 3h.01M21 21v-7a2 2 0 00-2-2H5a2 2 0 00-2 2v7h18z\' /></svg>'"
            />
            <x-stat-card
                :value="$stats['contracts_expiring_soon_count'] ?? 0"
                label="قراردادهای در آستانه انقضا"
                color="amber"
                :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z\' /></svg>'"
            />
            <x-stat-card
                :value="$stats['active_employees_count']"
                label="پرسنل فعال"
                color="green"
                :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z\' /></svg>'"
            />
            <x-stat-card
                :value="$stats['new_applications_count']"
                label="درخواست‌های جدید"
                color="blue"
                :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\' /></svg>'"
            />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @if(isset($todayInterviews) && $todayInterviews->isNotEmpty())
                <x-card title="مصاحبه‌های امروز">
                    <div class="space-y-3">
                        @foreach($todayInterviews as $interview)
                            <div class="flex items-center justify-between p-3 rounded-lg border border-slate-200 dark:border-cyan-500/15 hover:border-cyan-500/40 transition-colors">
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-white">{{ $interview->person->full_name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        <x-jalali-date :date="$interview->scheduled_at" format="H:i" />
                                    </p>
                                </div>
                                <x-badge variant="info">{{ $interview->type->label() }}</x-badge>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif

            @if(isset($recentApplications) && $recentApplications->isNotEmpty())
                <x-card title="درخواست‌های جدید">
                    <div class="space-y-3">
                        @foreach($recentApplications as $application)
                            <div class="flex items-center justify-between p-3 rounded-lg border border-slate-200 dark:border-purple-500/15 hover:border-purple-500/40 transition-colors">
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-white">{{ $application->person->full_name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $application->application_number }}</p>
                                </div>
                                <x-badge :variant="$application->status->value">{{ $application->status->label() }}</x-badge>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif
        </div>
    </div>
</x-layouts.app>
