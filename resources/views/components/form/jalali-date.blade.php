@props([
    'label',
    'name',
    'value' => '',
    'required' => false,
    'mode' => 'date',
    'defaultToday' => false,
    'class' => '',
])

@php
    use App\Helpers\JalaliHelper;

    $inputId = 'jdp-' . preg_replace('/[^a-z0-9_-]/i', '-', $name) . '-' . uniqid();
    $hiddenValue = '';
    $displayValue = '';

    if ($value instanceof \Carbon\Carbon) {
        $hiddenValue = $mode === 'datetime' ? $value->format('Y-m-d H:i:s') : $value->format('Y-m-d');
        $displayValue = JalaliHelper::format($value, $mode === 'datetime' ? 'Y/m/d H:i' : 'Y/m/d');
    } elseif (is_string($value) && $value !== '') {
        try {
            $carbon = \Carbon\Carbon::parse($value);
            $hiddenValue = $mode === 'datetime' ? $carbon->format('Y-m-d H:i:s') : $carbon->format('Y-m-d');
            $displayValue = JalaliHelper::format($carbon, $mode === 'datetime' ? 'Y/m/d H:i' : 'Y/m/d');
        } catch (\Throwable) {
            $hiddenValue = $value;
            $displayValue = $value;
        }
    } elseif ($defaultToday) {
        $hiddenValue = $mode === 'datetime' ? now()->format('Y-m-d H:i:s') : now()->format('Y-m-d');
        $displayValue = JalaliHelper::format(now(), $mode === 'datetime' ? 'Y/m/d H:i' : 'Y/m/d');
    }

    $hiddenValue = old($name, $hiddenValue);

    if (old($name)) {
        try {
            $displayValue = JalaliHelper::format(\Carbon\Carbon::parse(old($name)), $mode === 'datetime' ? 'Y/m/d H:i' : 'Y/m/d');
        } catch (\Throwable) {
            $displayValue = old($name);
        }
    }
@endphp

<div class="{{ $class }}">
    <label for="{{ $inputId }}" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
        {{ $label }}
        @if ($required)<span class="text-red-500">*</span>@endif
    </label>

    <input
        id="{{ $inputId }}"
        type="text"
        value="{{ $displayValue }}"
        data-jdp
        data-jdp-target="{{ $inputId }}-hidden"
        data-jdp-mode="{{ $mode }}"
        @if($mode === 'date') data-jdp-only-date @endif
        autocomplete="off"
        placeholder="انتخاب تاریخ"
        class="cyber-input w-full rounded-lg px-3 py-2 text-sm"
        readonly
    >

    <input
        id="{{ $inputId }}-hidden"
        type="hidden"
        name="{{ $name }}"
        value="{{ $hiddenValue }}"
        @if($required) required @endif
    >

    @error($name)
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
