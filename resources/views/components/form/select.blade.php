@props(['label', 'name', 'required' => false, 'placeholder' => 'انتخاب کنید'])

<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
        {{ $label }}
        @if ($required)<span class="text-red-500">*</span>@endif
    </label>
    <select
        name="{{ $name }}"
        id="{{ $name }}"
        @if ($required) required @endif
        {{ $attributes->except('class')->merge(['class' => 'cyber-input w-full rounded-lg px-3 py-2 text-sm transition-all']) }}
    >
        <option value="">{{ $placeholder }}</option>
        {{ $slot }}
    </select>
    @error($name)
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
