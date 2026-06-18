<x-app-layout title="تیکت‌های HR">
    <div class="space-y-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">تیکت‌های پرسنل</h1>

        <x-card>
            <form method="GET" class="flex flex-col sm:flex-row gap-3 mb-6">
                <x-form.select label="وضعیت" name="status" class="sm:w-48">
                    <option value="">همه</option>
                    @foreach (\App\Domain\Enums\HrTicketStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </x-form.select>
                <div class="flex items-end"><x-button type="submit" variant="secondary">فیلتر</x-button></div>
            </form>

            @if ($tickets->isEmpty())
                <x-empty-state title="تیکتی یافت نشد" />
            @else
                <x-data-table>
                    <x-slot:head>
                        <th class="px-4 py-3 font-medium">موضوع</th>
                        <th class="px-4 py-3 font-medium">پرسنل</th>
                        <th class="px-4 py-3 font-medium">وضعیت</th>
                        <th class="px-4 py-3 font-medium">تاریخ</th>
                        <th class="px-4 py-3 font-medium">عملیات</th>
                    </x-slot:head>
                    @foreach ($tickets as $ticket)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-4 py-3">{{ $ticket->subject }}</td>
                            <td class="px-4 py-3">{{ $ticket->person?->full_name }}</td>
                            <td class="px-4 py-3"><x-badge variant="info">{{ $ticket->status->label() }}</x-badge></td>
                            <td class="px-4 py-3"><x-jalali-date :date="$ticket->created_at" format="Y/m/d H:i" /></td>
                            <td class="px-4 py-3">
                                <x-button href="{{ route('admin.tickets.show', $ticket) }}" variant="ghost" size="sm">مشاهده</x-button>
                            </td>
                        </tr>
                    @endforeach
                </x-data-table>
                <div class="mt-4">{{ $tickets->links() }}</div>
            @endif
        </x-card>
    </div>
</x-app-layout>
