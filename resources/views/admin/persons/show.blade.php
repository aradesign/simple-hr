<x-app-layout :title="$person->full_name">
    <div class="space-y-6" x-data="{ tab: '{{ request('tab', 'profile') }}' }">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $person->full_name }}</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    <x-badge :variant="$person->lifecycle_status->value">{{ $person->lifecycle_status->label() }}</x-badge>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <x-button href="{{ route('admin.persons.edit', $person) }}" variant="secondary">ویرایش</x-button>
                <form method="POST" action="{{ route('admin.persons.destroy', $person) }}" onsubmit="return confirm('آیا مطمئن هستید؟')">
                    @csrf @method('DELETE')
                    <x-button type="submit" variant="danger">حذف</x-button>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto border-b border-slate-200 dark:border-slate-700">
            <nav class="flex gap-1 min-w-max">
                @foreach ([
                    'profile' => 'اطلاعات پایه',
                    'contact' => 'تماس و آدرس',
                    'family' => 'خانواده',
                    'educations' => 'تحصیلات',
                    'work_experiences' => 'سوابق شغلی',
                    'applications' => 'درخواست‌های استخدام',
                    'interviews' => 'مصاحبه‌ها',
                    'documents' => 'اسناد',
                    'employment_records' => 'سوابق همکاری',
                    'assignments' => 'فعالیت',
                ] as $key => $label)
                    <button
                        @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
                    >{{ $label }}</button>
                @endforeach
            </nav>
        </div>

        <x-card>
            <div x-show="tab === 'profile'" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div><span class="text-slate-500">نام:</span> {{ $person->first_name }}</div>
                <div><span class="text-slate-500">نام خانوادگی:</span> {{ $person->last_name }}</div>
                <div><span class="text-slate-500">کد ملی:</span> {{ $person->national_id ?? '—' }}</div>
                <div><span class="text-slate-500">موبایل:</span> <span dir="ltr">{{ $person->display_mobile ?? '—' }}</span></div>
                <div><span class="text-slate-500">تاریخ تولد:</span> <x-jalali-date :date="$person->birth_date" /></div>
                <div><span class="text-slate-500">جنسیت:</span> {{ $person->gender?->label() ?? '—' }}</div>
                <div><span class="text-slate-500">وضعیت تأهل:</span> {{ $person->marital_status?->label() ?? '—' }}</div>
            </div>

            <div x-show="tab === 'contact'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div class="sm:col-span-2"><span class="text-slate-500">آدرس:</span> {{ $person->address ?? '—' }}</div>
                <div><span class="text-slate-500">شهر:</span> {{ $person->city ?? '—' }}</div>
                <div><span class="text-slate-500">استان:</span> {{ $person->province ?? '—' }}</div>
                <div><span class="text-slate-500">کد پستی:</span> {{ $person->postal_code ?? '—' }}</div>
            </div>

            <div x-show="tab === 'family'" x-cloak>
                @forelse ($person->familyMembers as $member)
                    <div class="py-3 border-b border-slate-200 dark:border-slate-700 last:border-0 text-sm">
                        <span class="font-medium">{{ $member->full_name }}</span>
                        <span class="text-slate-500"> — {{ $member->relation?->label() }}</span>
                    </div>
                @empty
                    <x-empty-state title="عضو خانواده‌ای ثبت نشده" />
                @endforelse
            </div>

            <div x-show="tab === 'educations'" x-cloak>
                @forelse ($person->educations as $edu)
                    <div class="py-3 border-b border-slate-200 dark:border-slate-700 last:border-0 text-sm">
                        <span class="font-medium">{{ $edu->degree }}</span> — {{ $edu->field_of_study }} ({{ $edu->institution }})
                    </div>
                @empty
                    <x-empty-state title="سابقه تحصیلی ثبت نشده" />
                @endforelse
            </div>

            <div x-show="tab === 'work_experiences'" x-cloak>
                @forelse ($person->workExperiences as $exp)
                    <div class="py-3 border-b border-slate-200 dark:border-slate-700 last:border-0 text-sm">
                        <span class="font-medium">{{ $exp->job_title }}</span> — {{ $exp->company_name }}
                    </div>
                @empty
                    <x-empty-state title="سابقه شغلی ثبت نشده" />
                @endforelse
            </div>

            <div x-show="tab === 'applications'" x-cloak>
                @forelse ($person->employmentApplications as $app)
                    <div class="py-3 border-b border-slate-200 dark:border-slate-700 last:border-0 flex items-center justify-between text-sm">
                        <a href="{{ route('admin.applications.show', $app) }}" class="text-blue-600 hover:underline">{{ $app->application_number }}</a>
                        <x-badge :variant="$app->status->value">{{ $app->status->label() }}</x-badge>
                    </div>
                @empty
                    <x-empty-state title="درخواست استخدامی ثبت نشده" />
                @endforelse
            </div>

            <div x-show="tab === 'interviews'" x-cloak>
                @forelse ($person->interviews as $interview)
                    <div class="py-3 border-b border-slate-200 dark:border-slate-700 last:border-0 flex items-center justify-between text-sm">
                        <span>{{ $interview->type?->label() }} — <x-jalali-date :date="$interview->scheduled_at" format="Y/m/d H:i" /></span>
                        <x-badge variant="info">{{ $interview->status?->label() }}</x-badge>
                    </div>
                @empty
                    <x-empty-state title="مصاحبه‌ای ثبت نشده" />
                @endforelse
            </div>

            <div x-show="tab === 'documents'" x-cloak class="space-y-6">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">بارگذاری سند جدید</h3>
                    @include('admin.documents._upload-form', [
                        'person' => $person,
                        'documentTypes' => $documentTypes,
                    ])
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">اسناد ثبت‌شده</h3>
                    @forelse ($person->documents as $doc)
                        <div class="py-4 border-b border-slate-200 dark:border-slate-700 last:border-0">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                <div class="text-sm space-y-1">
                                    <p class="font-medium text-slate-900 dark:text-white">{{ $doc->title }}</p>
                                    <p class="text-slate-500">
                                        {{ $doc->type?->label() }}
                                        @if ($doc->expires_at)
                                            — انقضا: <x-jalali-date :date="$doc->expires_at" />
                                        @endif
                                    </p>
                                    @if ($doc->latestVersion)
                                        <p class="text-xs text-slate-400">
                                            نسخه {{ $doc->latestVersion->version_number }} —
                                            {{ $doc->latestVersion->file_name }} —
                                            <x-jalali-date :date="$doc->latestVersion->uploaded_at" format="Y/m/d H:i" />
                                        </p>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-2 shrink-0">
                                    @if ($doc->latestVersion)
                                        <x-button
                                            href="{{ route('admin.documents.download-version', [$doc, $doc->latestVersion]) }}"
                                            variant="secondary"
                                            size="sm"
                                        >
                                            دانلود
                                        </x-button>
                                    @endif
                                </div>
                            </div>

                            <details class="mt-3 text-sm">
                                <summary class="cursor-pointer text-cyan-600 dark:text-cyan-400">بارگذاری نسخه جدید</summary>
                                <div class="mt-3 p-4 rounded-lg bg-slate-50 dark:bg-slate-800/40">
                                    @include('admin.documents._upload-form', [
                                        'person' => $person,
                                        'documentTypes' => $documentTypes,
                                        'existingDocument' => $doc,
                                    ])
                                </div>
                            </details>
                        </div>
                    @empty
                        <x-empty-state title="سندی ثبت نشده" description="با فرم بالا اولین سند این پرسنل را بارگذاری کنید." />
                    @endforelse
                </div>
            </div>

            <div x-show="tab === 'employment_records'" x-cloak>
                @forelse ($person->employmentRecords as $record)
                    <div class="py-3 border-b border-slate-200 dark:border-slate-700 last:border-0 text-sm">
                        {{ $record->department?->name ?? '—' }} — <x-jalali-date :date="$record->start_date" /> تا <x-jalali-date :date="$record->end_date" />
                    </div>
                @empty
                    <x-empty-state title="سابقه همکاری ثبت نشده" />
                @endforelse
            </div>

            <div x-show="tab === 'assignments'" x-cloak>
                @forelse ($person->assignments as $assignment)
                    <div class="py-3 border-b border-slate-200 dark:border-slate-700 last:border-0 text-sm">
                        {{ $assignment->role?->label() }} — {{ $assignment->user?->name }}
                    </div>
                @empty
                    <x-empty-state title="ارجاعی ثبت نشده" />
                @endforelse
            </div>
        </x-card>
    </div>
</x-app-layout>
