@php
    $footer = app(\App\Services\Settings\SettingService::class)->get('texts', 'footer_text', '');
@endphp
@if($footer)
    <footer class="mt-auto py-4 px-6 text-center text-xs text-slate-500 dark:text-slate-400 border-t border-slate-200 dark:border-cyan-500/15">
        {{ $footer }}
    </footer>
@endif
