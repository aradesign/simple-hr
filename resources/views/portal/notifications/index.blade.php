<x-portal-layout title="اعلان‌ها">
    <div class="space-y-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">اعلان‌ها</h1>

        <x-card>
            @if ($notifications instanceof \Illuminate\Pagination\LengthAwarePaginator && $notifications->isEmpty() || ($notifications instanceof \Illuminate\Support\Collection && $notifications->isEmpty()))
                <x-empty-state title="اعلانی وجود ندارد" />
            @elseif ($notifications instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="space-y-3">
                    @foreach ($notifications as $notification)
                        <div class="p-4 rounded-lg border border-slate-200 dark:border-slate-700">
                            <p class="font-medium text-sm">{{ $notification->subject ?? 'اعلان' }}</p>
                            <p class="text-sm mt-1">{{ $notification->body }}</p>
                            <p class="text-xs text-slate-500 mt-1"><x-jalali-date :date="$notification->created_at" format="Y/m/d H:i" /></p>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4">{{ $notifications->links() }}</div>
            @endif
        </x-card>
    </div>
</x-portal-layout>
