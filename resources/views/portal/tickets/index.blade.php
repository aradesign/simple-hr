<x-portal-layout title="تیکت‌ها">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">تیکت‌های منابع انسانی</h1>
            <x-button href="{{ route('portal.tickets.create') }}" variant="primary" size="sm">تیکت جدید</x-button>
        </div>

        <x-card>
            @if ($tickets->isEmpty())
                <x-empty-state title="تیکتی ثبت نشده" description="برای ارتباط با منابع انسانی یک تیکت جدید ایجاد کنید." />
            @else
                <x-data-table>
                    <x-slot:head>
                        <th class="px-4 py-3 font-medium">موضوع</th>
                        <th class="px-4 py-3 font-medium">وضعیت</th>
                        <th class="px-4 py-3 font-medium">تاریخ</th>
                        <th class="px-4 py-3 font-medium">عملیات</th>
                    </x-slot:head>
                    @foreach ($tickets as $ticket)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-4 py-3">{{ $ticket->subject }}</td>
                            <td class="px-4 py-3"><x-badge variant="info">{{ $ticket->status->label() }}</x-badge></td>
                            <td class="px-4 py-3"><x-jalali-date :date="$ticket->created_at" format="Y/m/d H:i" /></td>
                            <td class="px-4 py-3">
                                <x-button href="{{ route('portal.tickets.show', $ticket) }}" variant="ghost" size="sm">مشاهده</x-button>
                            </td>
                        </tr>
                    @endforeach
                </x-data-table>
                <div class="mt-4">{{ $tickets->links() }}</div>
            @endif
        </x-card>
    </div>
</x-portal-layout>
