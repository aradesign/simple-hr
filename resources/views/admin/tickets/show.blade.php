<x-app-layout :title="'تیکت — ' . $ticket->subject">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $ticket->subject }}</h1>
                <p class="text-sm text-slate-500 mt-1">{{ $ticket->person?->full_name }} — <x-jalali-date :date="$ticket->created_at" format="Y/m/d H:i" /></p>
            </div>
            <x-badge variant="info">{{ $ticket->status->label() }}</x-badge>
        </div>

        <x-card title="پیام پرسنل">
            <p class="text-sm whitespace-pre-wrap">{{ $ticket->message }}</p>
        </x-card>

        <x-card title="پاسخ و پیگیری">
            <form method="POST" action="{{ route('admin.tickets.update', $ticket) }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <x-form.select label="وضعیت" name="status" required>
                    @foreach (\App\Domain\Enums\HrTicketStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $ticket->status->value) === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </x-form.select>

                <x-form.select label="مسئول پیگیری" name="assigned_to">
                    <option value="">انتخاب کنید</option>
                    @foreach ($hrUsers as $user)
                        <option value="{{ $user->id }}" @selected((string) old('assigned_to', $ticket->assigned_to) === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </x-form.select>

                <x-form.textarea label="پاسخ منابع انسانی" name="hr_reply" :value="old('hr_reply', $ticket->hr_reply)" rows="5" />

                <x-button type="submit" variant="primary">ذخیره</x-button>
            </form>
        </x-card>
    </div>
</x-app-layout>
