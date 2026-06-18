<x-recruitment-layout title="پنل درخواست استخدام">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">پنل درخواست استخدام</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    ورود با شماره <span class="font-medium text-slate-700 dark:text-slate-200" dir="ltr">{{ $contactMobile }}</span>
                </p>
            </div>
            <form method="POST" action="{{ route('recruitment.applications.store') }}">
                @csrf
                <x-button type="submit" variant="primary">+ درخواست جدید</x-button>
            </form>
        </div>

        @if ($applications->isEmpty())
            <x-card>
                <x-empty-state
                    title="هنوز درخواستی ثبت نکرده‌اید"
                    description="با زدن «درخواست جدید» فرم استخدام را تکمیل کنید. در صورت نیاز می‌توانید چند درخواست جداگانه ثبت کنید."
                >
                    <x-slot:action>
                        <form method="POST" action="{{ route('recruitment.applications.store') }}">
                            @csrf
                            <x-button type="submit" variant="primary">درخواست جدید</x-button>
                        </form>
                    </x-slot:action>
                </x-empty-state>
            </x-card>
        @else
            <x-card>
                <x-data-table>
                    <x-slot:head>
                        <th class="px-4 py-3 font-medium">درخواست</th>
                        <th class="px-4 py-3 font-medium">متقاضی</th>
                        <th class="px-4 py-3 font-medium">موبایل</th>
                        <th class="px-4 py-3 font-medium">وضعیت</th>
                        <th class="px-4 py-3 font-medium">عملیات</th>
                    </x-slot:head>
                    @foreach ($applications as $application)
                        @php
                            $person = $application->person;
                            $formData = \App\Support\EmploymentFormFields::normalizeFormData($application->form_data ?? []);
                            $displayName = trim(($formData['first_name'] ?? $person->first_name).' '.($formData['last_name'] ?? $person->last_name));
                            $displayMobile = $formData['mobile'] ?? ($person->mobile && ! str_starts_with($person->mobile, '098') ? $person->mobile : '—');
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-4 py-3 font-medium">{{ $application->application_number }}</td>
                            <td class="px-4 py-3">{{ $displayName !== 'درخواست جدید' ? $displayName : '—' }}</td>
                            <td class="px-4 py-3" dir="ltr">{{ $displayMobile }}</td>
                            <td class="px-4 py-3">
                                <x-badge :variant="$application->status->value">{{ $application->status->label() }}</x-badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($application->status === \App\Domain\Enums\ApplicationStatus::Draft)
                                        <x-button href="{{ route('recruitment.applications.form', $application) }}" variant="primary" size="sm">تکمیل فرم</x-button>
                                    @endif
                                    <x-button href="{{ route('recruitment.applications.status', $application) }}" variant="ghost" size="sm">پیگیری</x-button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-data-table>
            </x-card>
        @endif
    </div>
</x-recruitment-layout>
