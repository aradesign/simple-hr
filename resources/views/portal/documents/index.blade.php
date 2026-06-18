<x-portal-layout title="اسناد">
    <div class="space-y-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">اسناد من</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            اسنادی که منابع انسانی برای شما بارگذاری کرده‌اند اینجا نمایش داده می‌شوند.
        </p>

        <x-card>
            @if ($documents->isEmpty())
                <x-empty-state title="سندی موجود نیست" />
            @else
                <div class="space-y-3">
                    @foreach ($documents as $document)
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 rounded-lg border border-slate-200 dark:border-slate-700">
                            <div>
                                <p class="font-medium">{{ $document->title }}</p>
                                <p class="text-sm text-slate-500">{{ $document->type?->label() }} — <x-jalali-date :date="$document->created_at" /></p>
                            </div>
                            @if ($document->latestVersion)
                                <x-button
                                    href="{{ route('portal.documents.download', [$document, $document->latestVersion]) }}"
                                    variant="secondary"
                                    size="sm"
                                >
                                    دانلود {{ $document->latestVersion->file_name }}
                                </x-button>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</x-portal-layout>
