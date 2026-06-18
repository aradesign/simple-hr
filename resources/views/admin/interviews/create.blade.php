@php use App\Models\Person; use App\Models\User; @endphp

<x-app-layout title="برنامه‌ریزی مصاحبه">
    <div class="space-y-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">برنامه‌ریزی مصاحبه جدید</h1>
        <x-card>
            <form method="POST" action="{{ route('admin.interviews.store') }}" class="space-y-4">
                @csrf
                <x-form.select label="پرسنل / متقاضی" name="person_id" required>
                    @foreach (Person::query()->whereHas('employmentApplications')->latest()->limit(100)->get() as $p)
                        <option value="{{ $p->id }}" @selected(old('person_id') == $p->id)>{{ $p->full_name }}</option>
                    @endforeach
                </x-form.select>
                <x-form.select label="نوع مصاحبه" name="type" required>
                    @foreach (\App\Domain\Enums\InterviewType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </x-form.select>
                <x-form.jalali-date label="زمان برنامه‌ریزی" name="scheduled_at" mode="datetime" :value="old('scheduled_at')" required />
                <x-form.input label="مدت (دقیقه)" name="duration_minutes" type="number" :value="old('duration_minutes', 60)" />
                <x-form.input label="مکان" name="location" :value="old('location')" />
                <x-form.input label="لینک جلسه" name="meeting_url" type="url" :value="old('meeting_url')" />
                <x-form.select label="مصاحبه‌گر" name="interviewer_id">
                    @foreach (User::query()->where('hr_access', true)->get() as $user)
                        <option value="{{ $user->id }}" @selected(old('interviewer_id') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </x-form.select>
                <x-form.textarea label="یادداشت" name="notes" :value="old('notes')" />
                <div class="flex gap-3">
                    <x-button type="submit" variant="primary">ذخیره</x-button>
                    <x-button href="{{ route('admin.interviews.index') }}" variant="ghost">انصراف</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
