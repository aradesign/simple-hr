<x-portal-layout title="مشاهده تیکت">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $ticket->subject }}</h1>
            <x-badge variant="info">{{ $ticket->status->label() }}</x-badge>
        </div>

        <x-card title="پیام شما">
            <p class="text-sm whitespace-pre-wrap">{{ $ticket->message }}</p>
            <p class="text-xs text-slate-500 mt-3"><x-jalali-date :date="$ticket->created_at" format="Y/m/d H:i" /></p>
        </x-card>

        @if ($ticket->hr_reply)
            <x-card title="پاسخ منابع انسانی">
                <p class="text-sm whitespace-pre-wrap">{{ $ticket->hr_reply }}</p>
                @if ($ticket->replied_at)
                    <p class="text-xs text-slate-500 mt-3"><x-jalali-date :date="$ticket->replied_at" format="Y/m/d H:i" /></p>
                @endif
            </x-card>
        @endif

        <x-button href="{{ route('portal.tickets.index') }}" variant="ghost">بازگشت</x-button>
    </div>
</x-portal-layout>
