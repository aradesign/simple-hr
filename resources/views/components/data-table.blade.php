@props(['empty' => 'موردی یافت نشد'])

<div {{ $attributes->merge(['class' => 'overflow-hidden']) }}>
    <div class="hidden md:block overflow-x-auto rounded-lg border border-slate-200 dark:border-cyan-500/15">
        <table class="w-full text-sm text-right">
            @isset($head)
                <thead class="bg-slate-100 dark:bg-cyan-500/10 text-slate-700 dark:text-slate-300">
                    <tr>{{ $head }}</tr>
                    @isset($filters)
                        <tr class="bg-slate-50/80 dark:bg-slate-800/40">{{ $filters }}</tr>
                    @endisset
                </thead>
            @endisset
            <tbody class="divide-y divide-slate-200 dark:divide-cyan-500/10 text-slate-800 dark:text-slate-200">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @isset($mobile)
        <div class="md:hidden space-y-3">
            {{ $mobile }}
        </div>
    @else
        <div class="md:hidden space-y-3">
            {{ $slot }}
        </div>
    @endisset
</div>
