<x-portal-layout title="داشبورد">
    <div class="space-y-6">
        <div class="rounded-xl bg-gradient-to-l from-blue-600 to-blue-800 p-6 text-white">
            <h1 class="text-xl font-bold">سلام، {{ $person->full_name }}</h1>
            <p class="text-blue-100 mt-1"><x-jalali-date :date="now()" format="l j F Y" /></p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <x-stat-card
                :value="$person->departments->count()"
                label="دپارتمان"
                color="blue"
                :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16\'/></svg>'"
            />
            <x-stat-card
                :value="$person->employmentRecords->count()"
                label="سوابق همکاری"
                color="emerald"
                :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\'/></svg>'"
            />
        </div>

        <x-card title="خلاصه پروفایل">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div><dt class="text-slate-500">موبایل</dt><dd dir="ltr">{{ $person->display_mobile ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">کد ملی</dt><dd>{{ $person->national_id ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">وضعیت</dt><dd><x-badge :variant="$person->lifecycle_status->value">{{ $person->lifecycle_status->label() }}</x-badge></dd></div>
                <div><dt class="text-slate-500">تاریخ تولد</dt><dd><x-jalali-date :date="$person->birth_date" /></dd></div>
            </dl>
            <div class="mt-4">
                <x-button href="{{ route('portal.profile') }}" variant="primary" size="sm">مشاهده و ویرایش پروفایل</x-button>
            </div>
        </x-card>
    </div>
</x-portal-layout>
