<x-portal-layout title="پروفایل">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">پروفایل من</h1>
            <x-badge :variant="$person->lifecycle_status->value">{{ $person->lifecycle_status->label() }}</x-badge>
        </div>

        <x-card title="ویرایش اطلاعات">
            <form method="POST" action="{{ route('portal.profile.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form.input label="نام" name="first_name" :value="old('first_name', $person->first_name)" required />
                    <x-form.input label="نام خانوادگی" name="last_name" :value="old('last_name', $person->last_name)" required />
                    <x-form.input label="کد ملی" name="national_id" :value="old('national_id', $person->national_id)" />
                    <x-form.jalali-date label="تاریخ تولد" name="birth_date" :value="old('birth_date', $person->birth_date?->format('Y-m-d'))" />
                    <x-form.select label="جنسیت" name="gender">
                        <option value="">انتخاب کنید</option>
                        @foreach (\App\Domain\Enums\Gender::cases() as $gender)
                            <option value="{{ $gender->value }}" @selected(old('gender', $person->gender?->value) === $gender->value)>{{ $gender->label() }}</option>
                        @endforeach
                    </x-form.select>
                    <x-form.select label="وضعیت تأهل" name="marital_status">
                        <option value="">انتخاب کنید</option>
                        @foreach (\App\Domain\Enums\MaritalStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(old('marital_status', $person->marital_status?->value) === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </x-form.select>
                    <x-form.input label="شهر" name="city" :value="old('city', $person->city)" />
                    <x-form.input label="استان" name="province" :value="old('province', $person->province)" />
                    <x-form.input label="کد پستی" name="postal_code" :value="old('postal_code', $person->postal_code)" />
                    <div class="sm:col-span-2">
                        <x-form.textarea label="آدرس" name="address" :value="old('address', $person->address)" />
                    </div>
                </div>

                <div class="rounded-lg bg-slate-50 dark:bg-slate-800/40 px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                    موبایل: <span dir="ltr">{{ $person->display_mobile ?? '—' }}</span>
                    <span class="text-xs text-slate-500">(برای تغییر موبایل با منابع انسانی تماس بگیرید)</span>
                </div>

                <x-button type="submit" variant="primary">ذخیره تغییرات</x-button>
            </form>
        </x-card>

        @if ($person->educations->isNotEmpty())
            <x-card title="تحصیلات">
                @foreach ($person->educations as $edu)
                    <div class="py-2 border-b border-slate-200 dark:border-slate-700 last:border-0 text-sm">
                        {{ $edu->degree }} — {{ $edu->field_of_study }} ({{ $edu->institution }})
                    </div>
                @endforeach
            </x-card>
        @endif

        @if ($person->workExperiences->isNotEmpty())
            <x-card title="سوابق شغلی">
                @foreach ($person->workExperiences as $exp)
                    <div class="py-2 border-b border-slate-200 dark:border-slate-700 last:border-0 text-sm">
                        {{ $exp->position }} — {{ $exp->company_name }}
                    </div>
                @endforeach
            </x-card>
        @endif

        @if ($person->employmentRecords->isNotEmpty())
            <x-card title="سوابق همکاری">
                @foreach ($person->employmentRecords as $record)
                    <div class="py-2 border-b border-slate-200 dark:border-slate-700 last:border-0 text-sm">
                        {{ $record->position_title ?? '—' }} — {{ $record->department?->name ?? '—' }}
                        (<x-jalali-date :date="$record->start_date" />)
                    </div>
                @endforeach
            </x-card>
        @endif
    </div>
</x-portal-layout>
