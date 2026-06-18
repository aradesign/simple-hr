<x-app-layout :title="'مصاحبه — ' . $interview->person?->full_name">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">جزئیات مصاحبه</h1>
            <x-button href="{{ route('admin.interviews.edit', $interview) }}" variant="secondary">ویرایش</x-button>
        </div>

        <x-card>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div><dt class="text-slate-500">متقاضی</dt><dd>{{ $interview->person?->full_name }}</dd></div>
                <div><dt class="text-slate-500">نوع</dt><dd>{{ $interview->type?->label() }}</dd></div>
                <div><dt class="text-slate-500">زمان</dt><dd><x-jalali-date :date="$interview->scheduled_at" format="Y/m/d H:i" /></dd></div>
                <div><dt class="text-slate-500">وضعیت</dt><dd><x-badge variant="info">{{ $interview->status?->label() }}</x-badge></dd></div>
                <div><dt class="text-slate-500">مصاحبه‌گر</dt><dd>{{ $interview->interviewer?->name ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">مکان</dt><dd>{{ $interview->location ?? '—' }}</dd></div>
                @if ($interview->meeting_url)
                    <div class="sm:col-span-2"><dt class="text-slate-500">لینک</dt><dd><a href="{{ $interview->meeting_url }}" class="text-blue-600 hover:underline" target="_blank">{{ $interview->meeting_url }}</a></dd></div>
                @endif
                @if ($interview->result)
                    <div><dt class="text-slate-500">نتیجه</dt><dd>{{ $interview->result?->label() }}</dd></div>
                @endif
                @if ($interview->feedback)
                    <div class="sm:col-span-2"><dt class="text-slate-500">بازخورد</dt><dd>{{ $interview->feedback }}</dd></div>
                @endif
            </dl>
        </x-card>
    </div>
</x-app-layout>
