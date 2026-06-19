@php
    use App\Domain\Enums\PersonLifecycleStatus;
    use App\Domain\Enums\UserRole;
@endphp

<x-app-layout title="افزودن کاربر">
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">افزودن کاربر</h1>
            <x-button href="{{ route('admin.users.index') }}" variant="ghost">بازگشت</x-button>
        </div>

        <x-card>
            <form
                method="POST"
                action="{{ route('admin.users.store') }}"
                class="space-y-4"
                x-data="{
                    persons: @js($personOptions),
                    personId: @js(old('person_id', '')),
                    name: @js(old('name', '')),
                    email: @js(old('email', '')),
                    mobile: @js(old('mobile', '')),
                    role: @js(old('role', 'hr')),
                    applyPerson() {
                        const person = this.persons[this.personId];
                        if (!person) return;
                        this.name = person.name;
                        this.mobile = person.mobile || '';
                        if (!this.email) {
                            this.email = person.suggested_email;
                        }
                        this.role = person.suggested_role;
                    }
                }"
            >
                @csrf

                <x-form.select label="انتخاب از پرسنل (اختیاری)" name="person_id" placeholder="" x-model="personId" @change="applyPerson()">
                    <option value="">— بدون پرسنل (فقط حساب پنل ادمین) —</option>
                    @foreach ($availablePersons as $person)
                        <option value="{{ $person->id }}" @selected((string) old('person_id') === (string) $person->id)>
                            {{ $person->full_name }}
                            — {{ $person->lifecycle_status->label() }}
                            @if ($person->display_mobile)
                                ({{ $person->display_mobile }})
                            @endif
                        </option>
                    @endforeach
                </x-form.select>
                <p class="text-xs text-slate-500 dark:text-slate-400 -mt-2">
                    با انتخاب پرسنل، نام و موبایل خودکار پر می‌شود. هر پرسنل فقط یک حساب کاربری می‌تواند داشته باشد.
                </p>

                <x-form.input label="نام نمایشی" name="name" x-model="name" required />
                <x-form.input label="ایمیل (ورود به پنل ادمین)" name="email" type="email" x-model="email" required />
                <x-form.input label="موبایل" name="mobile" x-model="mobile" placeholder="09121234567" />
                <x-form.input label="رمز عبور" name="password" type="password" required />
                <x-form.input label="تکرار رمز عبور" name="password_confirmation" type="password" required />

                <x-form.select label="نقش" name="role" x-model="role" required>
                    @foreach (UserRole::cases() as $roleOption)
                        @if ($roleOption === UserRole::SuperAdmin && ! auth()->user()->isSuperAdmin())
                            @continue
                        @endif
                        <option value="{{ $roleOption->value }}">{{ $roleOption->label() }}</option>
                    @endforeach
                </x-form.select>

                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="hr_access" value="1" @checked(old('hr_access', true)) class="rounded border-slate-300 text-blue-600">
                    دسترسی به پنل منابع انسانی
                </label>

                <div class="flex gap-3 pt-2">
                    <x-button type="submit" variant="primary">ایجاد کاربر</x-button>
                    <x-button href="{{ route('admin.users.index') }}" variant="ghost">انصراف</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
