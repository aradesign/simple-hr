@php use App\Models\User; @endphp

<x-app-layout title="ویرایش مصاحبه">
    <div class="space-y-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">ویرایش مصاحبه — {{ $interview->person?->full_name }}</h1>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-card title="اطلاعات مصاحبه">
                <form method="POST" action="{{ route('admin.interviews.update', $interview) }}" class="space-y-4">
                    @csrf @method('PUT')
                    <x-form.select label="نوع" name="type">
                        @foreach (\App\Domain\Enums\InterviewType::cases() as $type)
                            <option value="{{ $type->value }}" @selected($interview->type === $type)>{{ $type->label() }}</option>
                        @endforeach
                    </x-form.select>
                    <x-form.jalali-date label="زمان" name="scheduled_at" mode="datetime" :value="$interview->scheduled_at" />
                    <x-form.input label="مدت (دقیقه)" name="duration_minutes" type="number" :value="$interview->duration_minutes" />
                    <x-form.input label="مکان" name="location" :value="$interview->location" />
                    <x-form.input label="لینک جلسه" name="meeting_url" type="url" :value="$interview->meeting_url" />
                    <x-form.select label="مصاحبه‌گر" name="interviewer_id">
                        <option value="">—</option>
                        @foreach (User::query()->where('hr_access', true)->get() as $user)
                            <option value="{{ $user->id }}" @selected($interview->interviewer_id == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </x-form.select>
                    <x-form.textarea label="یادداشت" name="notes" :value="$interview->notes" />
                    <x-button type="submit" variant="primary">به‌روزرسانی</x-button>
                </form>
            </x-card>

            @if ($interview->status?->value === 'scheduled')
                <x-card title="ثبت نتیجه">
                    <form method="POST" action="{{ route('admin.interviews.update', $interview) }}" class="space-y-4">
                        @csrf @method('PUT')
                        <x-form.select label="نتیجه" name="result" required>
                            @foreach (\App\Domain\Enums\InterviewResult::cases() as $result)
                                <option value="{{ $result->value }}">{{ $result->label() }}</option>
                            @endforeach
                        </x-form.select>
                        <x-form.textarea label="بازخورد" name="feedback" />
                        <x-button type="submit" variant="primary">ثبت نتیجه</x-button>
                    </form>
                </x-card>
            @endif
        </div>
    </div>
</x-app-layout>
