@props(['date', 'format' => 'Y/m/d'])

@if ($date)
    <time datetime="{{ $date instanceof \Carbon\Carbon ? $date->toIso8601String() : $date }}">
        {{ \App\Helpers\JalaliHelper::format($date instanceof \Carbon\Carbon ? $date : \Carbon\Carbon::parse($date), $format) }}
    </time>
@else
    <span class="text-slate-400">—</span>
@endif
