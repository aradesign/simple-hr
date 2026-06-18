@php
    use App\Domain\Enums\ApplicationStatus;

    $currentStatus = $application->status->value;
    $interviewScheduledValue = ApplicationStatus::InterviewScheduled->value;
    $shouldOpenScheduleModal = old('_form') === 'schedule-interview' && $errors->any();
@endphp

<x-app-layout :title="'درخواست ' . $application->application_number">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">درخواست {{ $application->application_number }}</h1>
                <p class="text-sm text-slate-500 mt-1">{{ $applicantName }} — {{ $application->contact_mobile ?? $application->person?->mobile }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <x-button href="{{ route('admin.applications.print', $application) }}" variant="secondary" size="sm" target="_blank">چاپ فرم</x-button>
                <x-button href="{{ route('admin.applications.download', $application) }}" variant="secondary" size="sm">دانلود PDF</x-button>
                <x-badge :variant="$application->status->value" class="text-sm">{{ $application->status->label() }}</x-badge>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <x-card title="اطلاعات درخواست">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div class="rounded-lg bg-slate-50/80 dark:bg-slate-800/30 px-3 py-2.5"><dt class="text-xs text-slate-500 mb-1">تاریخ ارسال</dt><dd class="font-medium"><x-jalali-date :date="$application->submitted_at" format="Y/m/d H:i" /></dd></div>
                        <div class="rounded-lg bg-slate-50/80 dark:bg-slate-800/30 px-3 py-2.5"><dt class="text-xs text-slate-500 mb-1">مسئول</dt><dd class="font-medium">{{ $application->assignee?->name ?? '—' }}</dd></div>
                        <div class="rounded-lg bg-slate-50/80 dark:bg-slate-800/30 px-3 py-2.5"><dt class="text-xs text-slate-500 mb-1">بازبین</dt><dd class="font-medium">{{ $application->reviewer?->name ?? '—' }}</dd></div>
                        <div class="sm:col-span-2 rounded-lg bg-slate-50/80 dark:bg-slate-800/30 px-3 py-2.5"><dt class="text-xs text-slate-500 mb-1">یادداشت HR</dt><dd class="font-medium whitespace-pre-wrap">{{ $application->hr_notes ?? '—' }}</dd></div>
                    </dl>
                </x-card>

                @if ($formEntries->isNotEmpty())
                    <x-card title="اطلاعات فرم">
                        <div class="mb-6 pb-6 border-b border-slate-200/80 dark:border-cyan-500/10">
                            <x-application.profile-photo
                                :photo-url="$profilePhotoUrl"
                                :initials="$initials"
                                :name="$applicantName"
                                :subtitle="$application->contact_mobile ?? null"
                            />
                        </div>

                        @include('admin.applications._form-data', ['entries' => $formEntries])
                    </x-card>
                @endif

                <x-card title="مصاحبه‌ها">
                    <div class="flex justify-end mb-4">
                        <x-button
                            type="button"
                            variant="secondary"
                            size="sm"
                            onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'schedule-interview' }))"
                        >
                            برنامه‌ریزی مصاحبه
                        </x-button>
                    </div>
                    @forelse ($application->interviews as $interview)
                        <div class="py-3 border-b border-slate-200 dark:border-slate-700 last:border-0 text-sm flex justify-between items-center gap-3">
                            <a href="{{ route('admin.interviews.show', $interview) }}" class="hover:text-cyan-600 dark:hover:text-cyan-400">
                                {{ $interview->type?->label() }} — <x-jalali-date :date="$interview->scheduled_at" format="Y/m/d H:i" />
                            </a>
                            <x-badge variant="info">{{ $interview->status?->label() }}</x-badge>
                        </div>
                    @empty
                        <x-empty-state title="مصاحبه‌ای ثبت نشده" />
                    @endforelse
                </x-card>
            </div>

            <div>
                <x-card title="تغییر وضعیت">
                    <form
                        method="POST"
                        action="{{ route('admin.applications.update-status', $application) }}"
                        class="space-y-4"
                        x-data="{ currentStatus: @js($currentStatus) }"
                        @submit="
                            const selected = $refs.statusSelect.value;
                            if (selected === @js($interviewScheduledValue) && selected !== currentStatus) {
                                $event.preventDefault();
                                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'schedule-interview' }));
                            }
                        "
                    >
                        @csrf @method('PATCH')
                        <x-form.select label="وضعیت جدید" name="status" required x-ref="statusSelect">
                            @foreach (ApplicationStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected($application->status === $status)>{{ $status->label() }}</option>
                            @endforeach
                        </x-form.select>
                        <x-form.textarea label="یادداشت HR" name="hr_notes" :value="$application->hr_notes" />
                        <x-button type="submit" variant="primary" class="w-full">به‌روزرسانی</x-button>
                        @if ($application->status === ApplicationStatus::InterviewScheduled)
                            <p class="text-xs text-slate-500">برای برنامه‌ریزی مصاحبه جدید از دکمه داخل بخش مصاحبه‌ها استفاده کنید.</p>
                        @endif
                    </form>
                </x-card>
            </div>
        </div>
    </div>

    <x-modal name="schedule-interview" title="برنامه‌ریزی مصاحبه" maxWidth="2xl">
        @include('admin.applications._schedule-interview-form', [
            'application' => $application,
            'interviewers' => $interviewers,
            'hrNotes' => old('hr_notes', $application->hr_notes),
        ])
    </x-modal>

    @if ($shouldOpenScheduleModal)
        <div
            x-data
            x-init="$nextTick(() => window.dispatchEvent(new CustomEvent('open-modal', { detail: 'schedule-interview' })))"
        ></div>
    @endif
</x-app-layout>
