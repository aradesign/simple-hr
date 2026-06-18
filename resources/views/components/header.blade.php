<header class="fixed top-0 right-0 left-0 z-50 h-16 cyber-panel border-b border-slate-200 dark:border-cyan-500/20">
    <div class="flex items-center justify-between h-full px-4 md:px-6">
        <div class="flex items-center gap-3">
            <button
                @click="toggleSidebar()"
                class="md:hidden p-2 rounded-lg text-slate-600 dark:text-cyan-400 hover:bg-slate-100 dark:hover:bg-cyan-500/10"
                aria-label="باز کردن منو"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                @if(!empty($logoUrl) || !empty($logoDarkUrl ?? null))
                    @if(!empty($logoUrl))
                        <img src="{{ $logoUrl }}" alt="{{ $siteName ?? 'سامانه' }}" class="h-8 w-auto max-w-[120px] object-contain dark:hidden">
                    @endif
                    @if(!empty($logoDarkUrl ?? null))
                        <img src="{{ $logoDarkUrl }}" alt="{{ $siteName ?? 'سامانه' }}" class="h-8 w-auto max-w-[120px] object-contain hidden dark:block">
                    @elseif(!empty($logoUrl))
                        <img src="{{ $logoUrl }}" alt="{{ $siteName ?? 'سامانه' }}" class="h-8 w-auto max-w-[120px] object-contain hidden dark:block">
                    @endif
                @else
                    <div class="w-8 h-8 bg-gradient-to-br from-cyan-600 to-purple-600 dark:from-cyan-400 dark:to-purple-600 rounded-lg flex items-center justify-center dark:neon-glow-cyan">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                @endif
                <span class="font-bold text-slate-800 dark:text-accent hidden sm:block">{{ $siteName ?? 'سامانه منابع انسانی' }}</span>
            </a>
        </div>

        <div class="flex items-center gap-2">
            <button
                @click="$store.theme.toggle()"
                class="p-2 rounded-lg border border-slate-300 dark:border-cyan-500/30 text-slate-600 dark:text-cyan-400 hover:bg-slate-100 dark:hover:bg-cyan-500/10 transition-colors"
                aria-label="تغییر تم"
            >
                <svg x-show="!$store.theme.dark" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
                <svg x-show="$store.theme.dark" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </button>

            @auth
                <div x-data="{ userMenuOpen: false }" class="relative">
                    <button
                        @click="userMenuOpen = !userMenuOpen"
                        class="flex items-center gap-2 p-2 rounded-lg border border-slate-300 dark:border-purple-500/30 hover:bg-slate-100 dark:hover:bg-purple-500/10 transition-colors"
                        aria-label="منوی کاربر"
                    >
                        @if(auth()->user()->avatarUrl())
                            <img src="{{ auth()->user()->avatarUrl() }}" alt="" class="w-8 h-8 rounded-full object-cover">
                        @else
                            <div class="w-8 h-8 bg-gradient-to-br from-purple-600 to-pink-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                {{ mb_substr(auth()->user()->name, 0, 1) }}
                            </div>
                        @endif
                        <span class="hidden sm:block text-sm font-medium text-slate-700 dark:text-slate-200">{{ auth()->user()->name }}</span>
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div
                        x-show="userMenuOpen"
                        @click.outside="userMenuOpen = false"
                        x-transition
                        x-cloak
                        class="absolute left-0 mt-2 w-52 cyber-panel rounded-lg py-1 z-50"
                    >
                        <div class="px-4 py-2 border-b border-slate-200 dark:border-cyan-500/15">
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->role?->label() }}</p>
                        </div>
                        <a href="{{ route('admin.profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-cyan-500/10">
                            پروفایل من
                        </a>
                        @if(auth()->user()->canManageSettings())
                            <a href="{{ route('admin.settings.index') }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-cyan-500/10">
                                تنظیمات سیستم
                            </a>
                        @endif
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-right px-4 py-2 text-sm text-red-600 dark:text-pink-400 hover:bg-slate-100 dark:hover:bg-pink-500/10 transition-colors">
                                خروج
                            </button>
                        </form>
                    </div>
                </div>
            @endauth
        </div>
    </div>
</header>
