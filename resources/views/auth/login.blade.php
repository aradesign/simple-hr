<x-layouts.guest title="ورود به پنل HR">
    <div class="cyber-panel rounded-xl p-6 md:p-8">
        <div class="text-center mb-6">
            <div class="w-14 h-14 mx-auto mb-3 bg-gradient-to-br from-cyan-600 to-purple-600 dark:from-cyan-400 dark:to-purple-600 rounded-xl flex items-center justify-center">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $appSettings['texts']['login_title'] ?? 'ورود به پنل منابع انسانی' }}</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ $appSettings['texts']['login_subtitle'] ?? 'با حساب کاربری سازمانی وارد شوید' }}</p>
        </div>

        @if ($errors->any())
            <x-alert type="error" class="mb-4">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </x-alert>
        @endif

        <form method="POST" action="{{ route('admin.login') }}" class="space-y-4">
            @csrf
            <x-form.input label="ایمیل" name="email" type="email" :value="old('email')" required autofocus />
            <x-form.input label="رمز عبور" name="password" type="password" required />
            <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                <input type="checkbox" name="remember" class="rounded border-slate-400 dark:border-cyan-500/40 text-cyan-600 focus:ring-cyan-500" {{ old('remember') ? 'checked' : '' }}>
                مرا به خاطر بسپار
            </label>
            <x-button type="submit" variant="primary" class="w-full">ورود به سیستم</x-button>
        </form>
    </div>
</x-layouts.guest>
