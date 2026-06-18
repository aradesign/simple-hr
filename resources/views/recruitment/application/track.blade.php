<x-recruitment-layout :title="'پیگیری — ' . $application->application_number">
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">پیگیری درخواست</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ $person->full_name }}</p>
            </div>
            <x-button href="{{ route('recruitment.dashboard') }}" variant="ghost" size="sm">بازگشت به پنل</x-button>
        </div>

        <x-card>
            <dl class="space-y-4 text-sm">
                <div class="flex justify-between py-3 border-b border-slate-200 dark:border-slate-700">
                    <dt class="text-slate-500">شماره درخواست</dt>
                    <dd class="font-medium">{{ $application->application_number }}</dd>
                </div>
                <div class="flex justify-between py-3 border-b border-slate-200 dark:border-slate-700">
                    <dt class="text-slate-500">وضعیت</dt>
                    <dd><x-badge :variant="$application->status->value">{{ $application->status->label() }}</x-badge></dd>
                </div>
                <div class="flex justify-between py-3 border-b border-slate-200 dark:border-slate-700">
                    <dt class="text-slate-500">تاریخ ارسال</dt>
                    <dd>
                        @if ($application->submitted_at)
                            <x-jalali-date :date="$application->submitted_at" format="Y/m/d H:i" />
                        @else
                            —
                        @endif
                    </dd>
                </div>
            </dl>

            @if ($application->interviews->isNotEmpty())
                <h2 class="text-lg font-semibold mt-6 mb-3">مصاحبه‌ها</h2>
                @foreach ($application->interviews as $interview)
                    <div class="py-3 border-b border-slate-200 dark:border-slate-700 last:border-0 text-sm flex justify-between">
                        <span>{{ $interview->type?->label() }}</span>
                        <x-jalali-date :date="$interview->scheduled_at" format="Y/m/d H:i" />
                    </div>
                @endforeach
            @endif

            @if ($application->status === \App\Domain\Enums\ApplicationStatus::Draft)
                <div class="mt-6">
                    <x-button href="{{ route('recruitment.applications.form', $application) }}" variant="primary">ادامه تکمیل فرم</x-button>
                </div>
            @endif
        </x-card>
    </div>
</x-recruitment-layout>
