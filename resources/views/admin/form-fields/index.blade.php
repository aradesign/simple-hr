<x-app-layout title="تنظیمات فرم">
    <div class="space-y-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">تنظیمات فیلدهای فرم استخدام</h1>

        <x-card>
            <form method="POST" action="{{ route('admin.form-fields.update') }}" class="space-y-6">
                @csrf @method('PATCH')
                @foreach ($fields as $index => $field)
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-4 rounded-lg border border-slate-200 dark:border-slate-700">
                        <input type="hidden" name="fields[{{ $index }}][id]" value="{{ $field->id }}">
                        <div class="flex-1">
                            <p class="font-medium">{{ $field->label }}</p>
                            <p class="text-xs text-slate-500">{{ $field->field_key }} — مرحله {{ $field->step }}</p>
                        </div>
                        <x-form.input label="ترتیب" name="fields[{{ $index }}][sort_order]" type="number" :value="$field->sort_order" class="sm:w-24" />
                        <x-form.input label="مرحله" name="fields[{{ $index }}][step]" type="number" :value="$field->step" class="sm:w-24" />
                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="fields[{{ $index }}][is_visible]" value="0">
                            <input type="checkbox" name="fields[{{ $index }}][is_visible]" value="1" @checked($field->is_visible) class="rounded">
                            نمایش
                        </label>
                    </div>
                @endforeach
                <x-button type="submit" variant="primary">ذخیره تنظیمات</x-button>
            </form>
        </x-card>
    </div>
</x-app-layout>
