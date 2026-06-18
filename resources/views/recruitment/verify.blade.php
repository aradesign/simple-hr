<x-layouts.guest title="تأیید کد">
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-6 md:p-8">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">تأیید کد</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">کد ارسال‌شده به {{ session('mobile', old('mobile')) }} را وارد کنید</p>

        <form method="POST" action="{{ route('recruitment.otp.verify') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="mobile" value="{{ session('mobile', old('mobile')) }}">
            <x-form.input label="کد تأیید" name="code" type="text" maxlength="6" placeholder="123456" required autofocus />
            <x-button type="submit" variant="primary" class="w-full">تأیید و ادامه</x-button>
        </form>

        <p class="text-center mt-4 text-sm">
            <a href="{{ route('recruitment.login') }}" class="text-blue-600 hover:underline">تغییر شماره موبایل</a>
        </p>
    </div>
</x-layouts.guest>
