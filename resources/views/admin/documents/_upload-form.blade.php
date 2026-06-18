@props([
    'person' => null,
    'documentTypes',
    'existingDocument' => null,
])

<form
    method="POST"
    action="{{ route('admin.documents.store') }}"
    enctype="multipart/form-data"
    class="space-y-4"
>
    @csrf

    @if ($person)
        <input type="hidden" name="person_id" value="{{ $person->id }}">
        <input type="hidden" name="redirect_tab" value="documents">
    @endif

    @if ($existingDocument)
        <input type="hidden" name="document_id" value="{{ $existingDocument->id }}">
        <input type="hidden" name="person_id" value="{{ $existingDocument->person_id }}">
        <p class="text-sm text-slate-600 dark:text-slate-400">
            نسخه جدید برای سند «{{ $existingDocument->title }}» بارگذاری می‌شود.
        </p>
    @else
        @unless ($person)
            <div>
                <label class="block text-sm font-medium mb-1">پرسنل</label>
                <select name="person_id" required class="cyber-input w-full rounded-lg px-3 py-2 text-sm">
                    <option value="">انتخاب کنید…</option>
                    @foreach ($persons ?? [] as $p)
                        <option value="{{ $p->id }}" @selected((int) old('person_id', request('person_id')) === $p->id)>
                            {{ $p->full_name }} — {{ $p->mobile ?? 'بدون موبایل' }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endunless

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">نوع سند</label>
                <select name="type" required class="cyber-input w-full rounded-lg px-3 py-2 text-sm">
                    @foreach ($documentTypes as $type)
                        <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">عنوان</label>
                <input
                    type="text"
                    name="title"
                    value="{{ old('title') }}"
                    required
                    maxlength="255"
                    placeholder="مثلاً قرارداد همکاری ۱۴۰۵"
                    class="cyber-input w-full rounded-lg px-3 py-2 text-sm"
                >
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">فایل</label>
            <input type="file" name="file" required class="cyber-input w-full rounded-lg px-3 py-2 text-sm">
            <p class="text-xs text-slate-500 mt-1">حداکثر ۱۰ مگابایت</p>
        </div>
        @unless ($existingDocument)
            <x-form.jalali-date label="تاریخ انقضا (اختیاری)" name="expires_at" :value="old('expires_at')" />
        @endunless
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">یادداشت (اختیاری)</label>
        <textarea name="notes" rows="2" class="cyber-input w-full rounded-lg px-3 py-2 text-sm">{{ old('notes') }}</textarea>
    </div>

    <div class="flex justify-end">
        <x-button type="submit">{{ $existingDocument ? 'بارگذاری نسخه جدید' : 'بارگذاری سند' }}</x-button>
    </div>
</form>
