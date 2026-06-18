@props(['entries'])

@if ($entries->isEmpty())
    <p class="text-sm text-slate-500 dark:text-slate-400">اطلاعاتی ثبت نشده است.</p>
@else
    <div class="space-y-8">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach ($entries as $entry)
                @if ($entry['type'] === \App\Domain\Enums\FormFieldType::List)
                    @continue
                @endif

                <div @class([
                    'rounded-xl border border-slate-200/80 dark:border-cyan-500/10 bg-slate-50/70 dark:bg-slate-800/35 px-4 py-3.5 shadow-sm',
                    'sm:col-span-2' => in_array($entry['type'], [\App\Domain\Enums\FormFieldType::Textarea, \App\Domain\Enums\FormFieldType::File], true),
                ])>
                    <dt class="text-xs font-semibold tracking-wide text-cyan-700/90 dark:text-cyan-400/90 mb-2">{{ $entry['label'] }}</dt>
                    <dd class="text-sm leading-7 text-slate-800 dark:text-slate-100 whitespace-pre-wrap break-words">
                        @if ($entry['type'] === \App\Domain\Enums\FormFieldType::File && $entry['file_url'])
                            <a href="{{ $entry['file_url'] }}" target="_blank" class="text-cyan-700 dark:text-cyan-300 hover:underline">{{ $entry['value'] }}</a>
                        @else
                            {{ $entry['value'] }}
                        @endif
                    </dd>
                </div>
            @endforeach
        </dl>

        @foreach ($entries as $entry)
            @if ($entry['type'] !== \App\Domain\Enums\FormFieldType::List || empty($entry['list_rows']))
                @continue
            @endif

            <div class="space-y-3">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 pb-2 border-b border-slate-200 dark:border-cyan-500/15">{{ $entry['label'] }}</h3>
                <div class="space-y-3">
                    @foreach ($entry['list_rows'] as $rowIndex => $row)
                        <div class="rounded-xl border border-slate-200/80 dark:border-cyan-500/10 bg-white/60 dark:bg-slate-900/20 overflow-hidden">
                            <div class="px-4 py-2 bg-slate-100/80 dark:bg-slate-800/60 text-xs font-medium text-slate-600 dark:text-slate-400">ردیف {{ $rowIndex + 1 }}</div>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-4 text-sm">
                                @foreach ($row as $cell)
                                    <div>
                                        <dt class="text-xs text-slate-500 dark:text-slate-400 mb-1">{{ $cell['label'] }}</dt>
                                        <dd class="text-slate-800 dark:text-slate-100">{{ $cell['value'] }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif
