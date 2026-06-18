<x-layouts.guest title="ورود پورتال">
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-6 md:p-8">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">{{ \App\Helpers\SettingsHelper::text('portal_title') }}</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">{{ \App\Helpers\SettingsHelper::text('portal_subtitle') }}</p>

        @if (session('success'))
            <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
        @endif

        <form method="POST" action="{{ route('portal.otp.request') }}" class="space-y-4">
            @csrf
            <x-form.input label="شماره موبایل" name="mobile" type="tel" :value="old('mobile')" placeholder="09123456789" required />
            <x-button type="submit" variant="primary" class="w-full">دریافت کد تأیید</x-button>
        </form>
    </div>
</x-layouts.guest>
