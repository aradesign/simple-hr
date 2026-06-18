<x-portal-layout title="تیکت جدید">
    <div class="space-y-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">ارسال تیکت به منابع انسانی</h1>

        <x-card>
            <form method="POST" action="{{ route('portal.tickets.store') }}" class="space-y-4">
                @csrf
                <x-form.input label="موضوع" name="subject" :value="old('subject')" required />
                <x-form.textarea label="پیام" name="message" :value="old('message')" rows="6" required />
                <div class="flex gap-3">
                    <x-button type="submit" variant="primary">ارسال تیکت</x-button>
                    <x-button href="{{ route('portal.tickets.index') }}" variant="ghost">انصراف</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-portal-layout>
