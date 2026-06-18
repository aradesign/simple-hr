<x-recruitment-layout :title="'فرم درخواست — ' . $application->application_number">
    <div
        x-data="recruitmentForm({
            fields: @js($fieldsPayload),
            initialValues: @js($formData),
        })"
        @change.capture="syncInput($event)"
        class="space-y-6"
    >
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">فرم درخواست استخدام</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    شماره درخواست: <span class="font-medium">{{ $application->application_number }}</span>
                </p>
            </div>
            <x-button href="{{ route('recruitment.dashboard') }}" variant="ghost" size="sm">بازگشت به پنل</x-button>
        </div>

        <x-card>
            <form
                method="POST"
                action="{{ route('recruitment.applications.update', $application) }}"
                enctype="multipart/form-data"
                class="space-y-5"
            >
                @csrf
                @method('PUT')

                @foreach ($fields as $field)
                    <x-recruitment.form-field :field="$field" :form-data="$formData" />
                @endforeach

                <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-slate-200 dark:border-cyan-500/15">
                    <x-button type="submit" variant="secondary" class="flex-1">ذخیره پیش‌نویس</x-button>
                    <x-button type="submit" name="submit" value="1" variant="primary" class="flex-1">ارسال نهایی درخواست</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-recruitment-layout>
