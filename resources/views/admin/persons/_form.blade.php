@props(['person' => null, 'action', 'method' => 'POST'])

@php
    use App\Domain\Enums\Gender;
    use App\Domain\Enums\MaritalStatus;
    use App\Domain\Enums\PersonLifecycleStatus;
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if (in_array(strtoupper($method), ['PUT', 'PATCH']))
        @method($method)
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-form.input label="نام" name="first_name" :value="$person?->first_name" required />
        <x-form.input label="نام خانوادگی" name="last_name" :value="$person?->last_name" required />
        <x-form.input label="کد ملی" name="national_id" :value="$person?->national_id" />
        <x-form.input label="موبایل" name="mobile" :value="$person?->mobile" />
        <x-form.jalali-date label="تاریخ تولد" name="birth_date" :value="$person?->birth_date" />
        <x-form.select label="جنسیت" name="gender">
            @foreach (Gender::cases() as $gender)
                <option value="{{ $gender->value }}" @selected(old('gender', $person?->gender?->value) === $gender->value)>{{ $gender->label() }}</option>
            @endforeach
        </x-form.select>
        <x-form.select label="وضعیت" name="lifecycle_status">
            @foreach (PersonLifecycleStatus::personnelRosterCases() as $status)
                <option value="{{ $status->value }}" @selected(old('lifecycle_status', $person?->lifecycle_status?->value ?? PersonLifecycleStatus::Employee->value) === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </x-form.select>
        <x-form.select label="وضعیت تأهل" name="marital_status">
            @foreach (MaritalStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(old('marital_status', $person?->marital_status?->value) === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </x-form.select>
        <x-form.input label="شهر" name="city" :value="$person?->city" />
        <x-form.input label="استان" name="province" :value="$person?->province" />
        <x-form.input label="کد پستی" name="postal_code" :value="$person?->postal_code" />
    </div>
    <x-form.textarea label="آدرس" name="address" :value="$person?->address" class="col-span-full" />
    <x-form.textarea label="یادداشت" name="notes" :value="$person?->notes" />

    <div class="flex items-center gap-3">
        <x-button type="submit" variant="primary">ذخیره</x-button>
        <x-button href="{{ $person ? route('admin.persons.show', $person) : route('admin.persons.index') }}" variant="ghost">انصراف</x-button>
    </div>
</form>
