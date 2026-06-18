@props(['title' => null])

<div {{ $attributes->merge(['class' => 'cyber-panel rounded-xl']) }}>
  @if ($title || isset($actions))
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 md:p-6 border-b border-slate-200 dark:border-cyan-500/15">
      @if ($title)
        <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ $title }}</h2>
      @endif
      @isset($actions)
        <div class="flex items-center gap-2 shrink-0">{{ $actions }}</div>
      @endisset
    </div>
  @endif
  <div class="p-4 md:p-6 text-slate-700 dark:text-slate-300">
    {{ $slot }}
  </div>
</div>
