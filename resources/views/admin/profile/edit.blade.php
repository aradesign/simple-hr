<x-layouts.app title="پروفایل من">
    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">پروفایل من</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">مشخصات حساب کاربری خود را تکمیل کنید</p>
        </div>

        <x-card>
            <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf @method('PUT')

                <div class="flex items-center gap-4">
                    @if($user->avatarUrl())
                        <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="w-16 h-16 rounded-full object-cover border-2 border-cyan-500/30">
                    @else
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center text-white text-xl font-bold">
                            {{ mb_substr($user->name, 0, 1) }}
                        </div>
                    @endif
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">تصویر پروفایل</label>
                        <input type="file" name="avatar" accept="image/*" class="cyber-input w-full rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>

                <x-form.input label="نام و نام خانوادگی" name="name" :value="$user->name" required />
                <x-form.input label="ایمیل" name="email" type="email" :value="$user->email" required />
                <x-form.input label="موبایل" name="mobile" :value="$user->mobile" placeholder="09121234567" />
                <x-form.input label="سمت سازمانی" name="job_title" :value="$user->job_title" />

                <div class="border-t border-slate-200 dark:border-cyan-500/15 pt-4">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">تغییر رمز عبور (اختیاری)</p>
                    <div class="space-y-4">
                        <x-form.input label="رمز عبور جدید" name="password" type="password" />
                        <x-form.input label="تکرار رمز عبور" name="password_confirmation" type="password" />
                    </div>
                </div>

                <div class="rounded-lg bg-slate-100 dark:bg-cyan-500/5 p-4 text-sm text-slate-600 dark:text-slate-400 space-y-1">
                    <p><strong>نقش:</strong> {{ $user->role?->label() }}</p>
                    <p><strong>دسترسی HR:</strong> {{ $user->hasHrAccess() ? 'بله' : 'خیر' }}</p>
                </div>

                <x-button type="submit" variant="primary">ذخیره تغییرات</x-button>
            </form>
        </x-card>
    </div>
</x-layouts.app>
