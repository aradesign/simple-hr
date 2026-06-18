@props(['field', 'formData' => []])

@php
    $name = "form_data[{$field->field_key}]";
    $value = $formData[$field->field_key] ?? '';
    $fileName = "form_files[{$field->field_key}]";
    $fieldJson = [
        'key' => $field->field_key,
        'type' => $field->field_type->value,
        'conditional_logic' => $field->conditional_logic,
        'css_class' => $field->css_class,
    ];
@endphp

<div x-show="isVisible(@js($fieldJson))" x-cloak class="space-y-1">
    @if ($field->field_type->value === 'hidden')
        <input type="hidden" name="{{ $name }}" x-model="values['{{ $field->field_key }}']">
    @elseif ($field->field_type->value === 'textarea')
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
            {{ $field->label }} @if($field->is_required)<span class="text-red-500">*</span>@endif
        </label>
        @if ($field->description)
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">{{ $field->description }}</p>
        @endif
        <textarea
            name="{{ $name }}"
            rows="4"
            @if($field->is_required) required @endif
            x-model="values['{{ $field->field_key }}']"
            class="cyber-input w-full rounded-lg px-3 py-2 text-sm"
        >{{ is_string($value) ? $value : '' }}</textarea>
    @elseif ($field->field_type->value === 'select')
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
            {{ $field->label }} @if($field->is_required)<span class="text-red-500">*</span>@endif
        </label>
        <select
            name="{{ $name }}"
            x-model="values['{{ $field->field_key }}']"
            @if($field->is_required) required @endif
            class="cyber-input w-full rounded-lg px-3 py-2 text-sm"
        >
            <option value="">انتخاب کنید</option>
            @foreach ($field->options ?? [] as $option)
                <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
        </select>
    @elseif ($field->field_type->value === 'radio')
        <fieldset>
            <legend class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                {{ $field->label }} @if($field->is_required)<span class="text-red-500">*</span>@endif
            </legend>
            <div :class="inlineClass(@js(['css_class' => $field->css_class]))">
                @foreach ($field->options ?? [] as $option)
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <input
                            type="radio"
                            name="{{ $name }}"
                            value="{{ $option }}"
                            x-model="values['{{ $field->field_key }}']"
                            @if($field->is_required) required @endif
                            class="text-cyan-600 border-slate-400"
                        >
                        {{ $option }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    @elseif ($field->field_type->value === 'checkbox')
        <fieldset>
            <legend class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ $field->label }}</legend>
            <div :class="inlineClass(@js(['css_class' => $field->css_class]))">
                @foreach ($field->options ?? [] as $option)
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <input
                            type="checkbox"
                            name="{{ $name }}[]"
                            value="{{ $option }}"
                            x-model="values['{{ $field->field_key }}']"
                            class="rounded border-slate-400 text-cyan-600"
                        >
                        {{ $option }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    @elseif ($field->field_type->value === 'date')
        <x-form.jalali-date :label="$field->label" :name="$name" :value="$value" :required="$field->is_required" />
    @elseif ($field->field_type->value === 'national_id')
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
            {{ $field->label }} @if($field->is_required)<span class="text-red-500">*</span>@endif
        </label>
        <input
            type="text"
            name="{{ $name }}"
            maxlength="10"
            dir="ltr"
            @if($field->is_required) required @endif
            x-model="values['{{ $field->field_key }}']"
            value="{{ is_string($value) ? $value : '' }}"
            class="cyber-input w-full rounded-lg px-3 py-2 text-sm text-left"
        >
    @elseif ($field->field_type->value === 'number')
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
            {{ $field->label }} @if($field->is_required)<span class="text-red-500">*</span>@endif
        </label>
        <input
            type="number"
            name="{{ $name }}"
            @if($field->is_required) required @endif
            x-model="values['{{ $field->field_key }}']"
            value="{{ is_scalar($value) ? $value : '' }}"
            class="cyber-input w-full rounded-lg px-3 py-2 text-sm"
        >
    @elseif ($field->field_type->value === 'file')
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
            {{ $field->label }} @if($field->is_required)<span class="text-red-500">*</span>@endif
        </label>
        <input type="file" name="{{ $fileName }}" class="cyber-input w-full rounded-lg px-3 py-2 text-sm">
        @if (is_string($value) && $value !== '')
            <p class="text-xs text-slate-500 mt-1">فایل قبلی: {{ basename($value) }}</p>
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endif
    @elseif ($field->field_type->value === 'list')
        <div class="space-y-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $field->label }}</h3>
                @if ($field->description)
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $field->description }}</p>
                @endif
            </div>
            <template x-for="(row, rowIndex) in listRows(@js(['key' => $field->field_key]))" :key="rowIndex">
                <div class="rounded-lg border border-slate-200 dark:border-cyan-500/15 p-3 space-y-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach ($field->list_columns ?? [] as $column)
                            <div>
                                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">{{ $column['label'] }}</label>
                                <input
                                    type="text"
                                    class="cyber-input w-full rounded-lg px-3 py-2 text-sm"
                                    :name="`form_data[{{ $field->field_key }}][${rowIndex}][{{ $column['key'] }}]`"
                                    x-model="values['{{ $field->field_key }}'][rowIndex]['{{ $column['key'] }}']"
                                >
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="text-xs text-red-600 hover:underline" @click="removeListRow(@js(['key' => $field->field_key]), rowIndex)">حذف ردیف</button>
                </div>
            </template>
            <x-button type="button" variant="secondary" size="sm" @click="addListRow(@js(['key' => $field->field_key]))">+ افزودن ردیف</x-button>
        </div>
    @else
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
            {{ $field->label }} @if($field->is_required)<span class="text-red-500">*</span>@endif
        </label>
        <input
            type="{{ $field->field_type->value === 'tel' ? 'tel' : 'text' }}"
            name="{{ $name }}"
            @if($field->is_required) required @endif
            x-model="values['{{ $field->field_key }}']"
            value="{{ is_string($value) ? $value : '' }}"
            class="cyber-input w-full rounded-lg px-3 py-2 text-sm"
        >
    @endif
</div>
