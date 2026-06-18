@php
    $settings = app(\App\Services\Settings\SettingService::class);
    $appearance = $settings->group('appearance');
    $defaultTheme = $appearance['default_theme'] ?? 'dark';
    $primaryCss = \App\Helpers\SettingsHelper::primaryColorCss();
@endphp
<script>
    (function () {
        const defaultTheme = @json($defaultTheme);
        const stored = localStorage.getItem('theme');
        let isDark;
        if (stored) {
            isDark = stored === 'dark';
        } else if (defaultTheme === 'system') {
            isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        } else {
            isDark = defaultTheme === 'dark';
        }
        if (isDark) document.documentElement.classList.add('dark');
        document.documentElement.dataset.defaultTheme = defaultTheme;
    })();
</script>
<style>
    :root { {!! $primaryCss !!} }
    .text-accent { color: var(--accent-dark); }
    .dark .text-accent { color: var(--accent-light); text-shadow: 0 0 12px var(--accent-glow); }
    .cyber-nav-active { border-right-color: var(--accent) !important; color: var(--accent-dark) !important; }
    .dark .cyber-nav-active { color: var(--accent-light) !important; }
    .stat-cyan, .stat-blue, .stat-purple, .stat-magenta { border-top-color: var(--accent) !important; }
</style>
