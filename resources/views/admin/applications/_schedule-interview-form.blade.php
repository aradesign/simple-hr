@props([
    'application',
    'interviewers',
    'hrNotes' => null,
    'submitLabel' => 'برنامه‌ریزی مصاحبه',
])

<form method="POST" action="{{ route('admin.applications.schedule-interview', $application) }}" class="space-y-4">
    @csrf
    <input type="hidden" name="_form" value="schedule-interview">

    <x-form.select label="نوع مصاحبه" name="type" required>
        @foreach (\App\Domain\Enums\InterviewType::cases() as $type)
            <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
        @endforeach
    </x-form.select>

    <x-form.jalali-date label="زمان برنامه‌ریزی" name="scheduled_at" mode="datetime" :value="old('scheduled_at')" required />

    <x-form.input label="مدت (دقیقه)" name="duration_minutes" type="number" :value="old('duration_minutes', 60)" />

    <x-form.input label="مکان" name="location" :value="old('location')" />

    <x-form.input label="لینک جلسه" name="meeting_url" type="url" :value="old('meeting_url')" />

    <x-form.select label="مصاحبه‌گر" name="interviewer_id" required>
        <option value="">انتخاب کنید</option>
        @foreach ($interviewers as $user)
            <option value="{{ $user->id }}" @selected((string) old('interviewer_id') === (string) $user->id)>{{ $user->name }}</option>
        @endforeach
    </x-form.select>

    <x-form.textarea label="یادداشت مصاحبه" name="notes" :value="old('notes')" />

    <x-form.textarea label="یادداشت HR" name="hr_notes" :value="old('hr_notes', $hrNotes)" />

    <div class="flex gap-3 justify-end">
        <x-button type="button" variant="ghost" @click="window.dispatchEvent(new CustomEvent('close-modal', { detail: 'schedule-interview' }))">انصراف</x-button>
        <x-button type="submit" variant="primary">{{ $submitLabel }}</x-button>
    </div>
</form>
