<x-app-layout title="اسناد">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">مدیریت اسناد</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                هر سند به یک پرسنل تعلق دارد و در پورتال کارمندی همان شخص قابل دانلود است.
            </p>
        </div>

        <x-card title="بارگذاری سند برای پرسنل">
            @include('admin.documents._upload-form', [
                'documentTypes' => $documentTypes,
                'persons' => $persons,
            ])
        </x-card>

        <x-card title="فهرست اسناد">
            @if ($documents->isEmpty())
                <x-empty-state title="سندی یافت نشد" />
            @else
                <x-data-table>
                    <x-slot:head>
                        <th class="px-4 py-3 font-medium">عنوان</th>
                        <th class="px-4 py-3 font-medium">نوع</th>
                        <th class="px-4 py-3 font-medium">پرسنل</th>
                        <th class="px-4 py-3 font-medium">تاریخ</th>
                        <th class="px-4 py-3 font-medium">عملیات</th>
                    </x-slot:head>
                    @foreach ($documents as $document)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-4 py-3">{{ $document->title }}</td>
                            <td class="px-4 py-3">{{ $document->type?->label() }}</td>
                            <td class="px-4 py-3">{{ $document->person?->full_name }}</td>
                            <td class="px-4 py-3"><x-jalali-date :date="$document->created_at" /></td>
                            <td class="px-4 py-3">
                                @if ($document->latestVersion)
                                    <x-button href="{{ route('admin.documents.download-version', [$document, $document->latestVersion]) }}" variant="ghost" size="sm">دانلود</x-button>
                                    <x-button href="{{ route('admin.persons.show', ['person' => $document->person_id, 'tab' => 'documents']) }}" variant="ghost" size="sm">پرونده</x-button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-data-table>
                <div class="mt-4">{{ $documents->links() }}</div>
            @endif
        </x-card>
    </div>
</x-app-layout>
