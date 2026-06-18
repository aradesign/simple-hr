@props(['action', 'settings'])

@php
    $sms = $settings['sms'] ?? [];
    $texts = $settings['texts'] ?? [];
    $textKey = $action['text_key'];
    $smsIrKey = \App\Support\SmsActionCatalog::smsIrPatternSettingKey($action['key']);
    $ippanelKey = \App\Support\SmsActionCatalog::ippanelPatternSettingKey($action['key']);
@endphp

<div class="p-4 md:p-5 rounded-xl border border-slate-200 dark:border-cyan-500/20 bg-slate-50/50 dark:bg-slate-900/30 space-y-4">
    <div>
        <div class="flex flex-wrap items-center gap-2 mb-1">
            <h3 class="font-semibold text-slate-900 dark:text-white">{{ $action['label'] }}</h3>
            <x-badge :variant="$action['type'] === 'otp' ? 'info' : 'default'">
                {{ $action['type'] === 'otp' ? 'OTP' : 'اطلاع‌رسانی' }}
            </x-badge>
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $action['description'] }}</p>
        <p class="text-xs text-slate-400 mt-1 font-mono">action: {{ $action['key'] }}</p>
    </div>

    <x-form.textarea
        label="متن پیامک (قابل ویرایش)"
        :name="'texts['.$textKey.']'"
        rows="2"
        :value="$texts[$textKey] ?? ''"
        required
    />
    <p class="text-xs text-slate-500 -mt-2">متغیرها: {{ collect($action['variables'])->map(fn($v) => '{'.$v['key'].'}')->implode('، ') }}</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <x-form.input
            label="شناسه قالب sms.ir"
            :name="'patterns[sms_ir]['.$action['key'].']'"
            :value="$sms[$smsIrKey] ?? ''"
            placeholder="مثال: 123456"
        />
        <x-form.input
            label="کد الگو ippanel"
            :name="'patterns[ippanel]['.$action['key'].']'"
            :value="$sms[$ippanelKey] ?? ''"
            placeholder="مثال: abcdef12"
        />
    </div>

    <div class="rounded-lg bg-white dark:bg-slate-800/60 border border-dashed border-slate-300 dark:border-cyan-500/25 p-3 text-xs space-y-2">
        <p class="font-medium text-slate-700 dark:text-slate-300">راهنمای ساخت قالب در پنل پیامکی</p>
        <div>
            <span class="text-slate-500">متن پیشنهادی قالب:</span>
            <code class="block mt-1 p-2 rounded bg-slate-100 dark:bg-slate-900 text-accent break-all">{{ $action['pattern_template'] }}</code>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-slate-500">
            <div>
                <span class="font-medium text-slate-600 dark:text-slate-400">sms.ir — پارامترها:</span>
                <ul class="mt-1 list-disc list-inside">
                    @foreach($action['variables'] as $var)
                        <li><code>{{ $var['sms_ir'] }}</code> ← {{ $var['label'] }}</li>
                    @endforeach
                </ul>
            </div>
            <div>
                <span class="font-medium text-slate-600 dark:text-slate-400">ippanel — متغیرها:</span>
                <ul class="mt-1 list-disc list-inside">
                    @foreach($action['variables'] as $var)
                        <li><code>{{ $var['ippanel'] }}</code> ← {{ $var['label'] }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <p class="text-slate-400">بعد از ساخت قالب در پنل، شناسه/کد را در فیلدهای بالا وارد و ذخیره کنید. اگر خالی بماند، متن کامل به‌صورت عادی ارسال می‌شود.</p>
    </div>
</div>
