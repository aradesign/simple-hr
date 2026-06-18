@props(['column', 'label'])

@php
    $currentSort = request('sort');
    $currentDirection = request('direction', 'desc') === 'asc' ? 'asc' : 'desc';
    $isActive = $currentSort === $column;
    $nextDirection = $isActive && $currentDirection === 'asc' ? 'desc' : 'asc';
    $url = request()->fullUrlWithQuery([
        'sort' => $column,
        'direction' => $nextDirection,
        'page' => null,
    ]);
@endphp

<a href="{{ $url }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 hover:text-slate-900 dark:hover:text-white']) }}>
    <span>{{ $label }}</span>
    @if ($isActive)
        <span class="text-xs text-slate-500" aria-hidden="true">{{ $currentDirection === 'asc' ? '↑' : '↓' }}</span>
    @endif
</a>
