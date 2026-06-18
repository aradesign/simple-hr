<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    @unless ($forPdf)
        <meta name="viewport" content="width=device-width, initial-scale=1">
    @endunless
    <title>فرم متقاضی — {{ $application->application_number }}</title>
    <style>
        @unless ($forPdf)
        @font-face {
            font-family: 'Vazirmatn';
            src: url('{{ asset('fonts/vazirmatn-regular.woff') }}') format('woff');
            font-weight: 400;
            font-style: normal;
        }
        @font-face {
            font-family: 'Vazirmatn';
            src: url('{{ asset('fonts/vazirmatn-bold.woff') }}') format('woff');
            font-weight: 700;
            font-style: normal;
        }
        @endunless

        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: {{ $forPdf ? '0' : '1.25rem' }};
            font-family: {{ $forPdf ? 'vazirmatn' : "'Vazirmatn', Tahoma, Arial, sans-serif" }};
            font-size: {{ $forPdf ? '9.5pt' : '12px' }};
            line-height: 1.45;
            color: #000;
            background: #fff;
            direction: rtl;
        }
        .toolbar {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .toolbar button, .toolbar a {
            padding: 0.4rem 0.85rem;
            border: 1px solid #666;
            background: #fff;
            color: #000;
            text-decoration: none;
            font: inherit;
            cursor: pointer;
        }
        .toolbar .primary { background: #111; color: #fff; border-color: #111; }

        .sheet {
            max-width: 210mm;
            margin: 0 auto;
            border: {{ $forPdf ? 'none' : '1px solid #bbb' }};
            padding: {{ $forPdf ? '0' : '10mm 8mm' }};
        }

        .top-bar {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .top-bar td { vertical-align: top; padding: 0; }
        .top-date { width: 28%; font-size: {{ $forPdf ? '9pt' : '11px' }}; }
        .top-title {
            width: 44%;
            text-align: center;
            font-size: {{ $forPdf ? '15pt' : '18px' }};
            font-weight: 700;
            padding-top: 2px;
        }
        .top-photo { width: 28%; text-align: {{ $forPdf ? 'center' : 'left' }}; }
        .photo-box {
            width: {{ $forPdf ? '85px' : '88px' }};
            height: {{ $forPdf ? '108px' : '112px' }};
            border: 1px solid #000;
            display: {{ $forPdf ? 'block' : 'inline-block' }};
            overflow: hidden;
            background: #fafafa;
            text-align: center;
            margin: {{ $forPdf ? '0 auto' : '0' }};
        }
        .photo-box img {
            width: {{ $forPdf ? '85px' : '100%' }};
            height: {{ $forPdf ? '108px' : '100%' }};
            @unless ($forPdf)
            object-fit: cover;
            @endunless
            display: block;
        }
        .photo-placeholder {
            width: 100%;
            height: 100%;
            @if ($forPdf)
            display: block;
            line-height: 108px;
            @else
            display: flex;
            align-items: center;
            justify-content: center;
            @endif
            font-size: {{ $forPdf ? '20pt' : '22px' }};
            font-weight: 700;
            color: #666;
        }
        .company {
            text-align: center;
            font-size: {{ $forPdf ? '8.5pt' : '10px' }};
            color: #333;
            margin-bottom: 8px;
        }

        .section { margin-top: 8px; }
        .section-title {
            font-weight: 700;
            margin: 0 0 4px;
            font-size: {{ $forPdf ? '10pt' : '12px' }};
        }

        .field-table, .data-table, .line-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .field-table td, .data-table th, .data-table td, .line-table td {
            border: 1px solid #000;
            padding: 3px 5px;
            vertical-align: top;
        }
        .field-label {
            white-space: {{ $forPdf ? 'normal' : 'nowrap' }};
            font-weight: 700;
            width: {{ $forPdf ? '12%' : '1%' }};
            @if ($forPdf)
            background: #f5f5f5;
            @endif
        }
        .field-value {
            min-height: 16px;
            word-break: break-word;
            @if ($forPdf)
            width: 21%;
            @endif
        }
        .data-table th {
            background: #efefef;
            font-weight: 700;
            text-align: center;
            font-size: {{ $forPdf ? '8.5pt' : '11px' }};
        }
        .data-table td {
            text-align: center;
            min-height: 18px;
            font-size: {{ $forPdf ? '8.5pt' : '11px' }};
        }
        .table-caption {
            font-weight: 700;
            margin: 2px 0 3px;
            font-size: {{ $forPdf ? '9.5pt' : '12px' }};
        }

        .options-line {
            border: 1px solid #000;
            padding: 4px 6px;
            margin-bottom: 4px;
        }
        .options-line .label { font-weight: 700; margin-left: 6px; }
        .option {
            display: inline-block;
            margin-left: 10px;
            white-space: nowrap;
        }
        .option-box {
            display: inline-block;
            width: 11px;
            height: 11px;
            border: 1px solid #000;
            margin-left: 3px;
            vertical-align: -1px;
            text-align: center;
            line-height: 9px;
            font-size: 9px;
        }
        .option-box.checked::after { content: '✓'; }

        .full-line {
            border: 1px solid #000;
            padding: 4px 6px;
            margin-bottom: 4px;
        }
        .full-line .label { font-weight: 700; }
        .full-line .value { margin-top: 2px; white-space: pre-wrap; }

        .declaration {
            border: 1px solid #000;
            padding: 6px;
            margin-top: 10px;
            font-size: {{ $forPdf ? '8.5pt' : '11px' }};
            line-height: 1.7;
        }

        .process-block {
            margin-top: 12px;
            border: 1px solid #000;
            padding: 6px;
        }
        .process-block h3 {
            margin: 0 0 6px;
            font-size: {{ $forPdf ? '9.5pt' : '12px' }};
            font-weight: 700;
        }
        .process-interview {
            border: 1px solid #000;
            margin-top: 6px;
            padding: 4px 6px;
        }
        .process-interview-title {
            font-weight: 700;
            margin-bottom: 4px;
            font-size: {{ $forPdf ? '9pt' : '11px' }};
        }
        .process-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .process-grid td {
            border: 1px solid #000;
            padding: 3px 5px;
            vertical-align: top;
            font-size: {{ $forPdf ? '8.5pt' : '11px' }};
        }
        .process-grid .label {
            font-weight: 700;
            white-space: {{ $forPdf ? 'normal' : 'nowrap' }};
            width: {{ $forPdf ? '25%' : '1%' }};
        }

        @media print {
            body { padding: 0; }
            .toolbar { display: none !important; }
            .sheet { border: none; padding: 0; }
        }
    </style>
</head>
<body>
    @if ($showToolbar)
        <div class="toolbar no-print">
            <button type="button" class="primary" onclick="window.print()">چاپ</button>
            <a href="{{ route('admin.applications.download', $application) }}">دانلود PDF</a>
            <a href="{{ route('admin.applications.show', $application) }}">بازگشت</a>
        </div>
    @endif

    <div class="sheet">
        <table class="top-bar">
            <tr>
                <td class="top-date">تاریخ درخواست: <strong>{{ $layout['submitted_at'] }}</strong></td>
                <td class="top-title">فرم متقاضی</td>
                <td class="top-photo">
                    <div class="photo-box">
                        @if ($forPdf && $profilePhotoDataUri)
                            <img src="{{ $profilePhotoDataUri }}" alt="عکس">
                        @elseif (! $forPdf && $profilePhotoUrl)
                            <img src="{{ $profilePhotoUrl }}" alt="عکس">
                        @else
                            <div class="photo-placeholder">{{ $initials }}</div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <div class="company">{{ $layout['company_name'] }} — {{ $layout['application_number'] }}</div>

        @foreach ($layout['sections'] as $section)
            @php
                $blocks = collect($section['blocks'] ?? [])->filter();
            @endphp
            @if ($blocks->isEmpty())
                @continue
            @endif

            <div class="section">
                @if ($section['title'])
                    <h2 class="section-title">{{ $section['title'] }}:</h2>
                @endif

                @foreach ($blocks as $block)
                    @if ($block['type'] === 'row')
                        <table class="field-table">
                            <tr>
                                @foreach ($block['cells'] as $cell)
                                    <td class="field-label">{{ $cell['label'] }}:</td>
                                    <td class="field-value">{{ $cell['value'] }}</td>
                                @endforeach
                            </tr>
                        </table>
                    @elseif ($block['type'] === 'full')
                        @if ($forPdf)
                            <table class="field-table">
                                <tr>
                                    <td class="field-label" width="20%">{{ $block['label'] }}:</td>
                                    <td class="field-value" colspan="5">{{ $block['value'] }}</td>
                                </tr>
                            </table>
                        @else
                            <div class="full-line">
                                <span class="label">{{ $block['label'] }}:</span>
                                <div class="value">{{ $block['value'] }}</div>
                            </div>
                        @endif
                    @elseif ($block['type'] === 'options')
                        @if ($forPdf)
                            <table class="field-table">
                                <tr>
                                    <td>
                                        <strong>{{ $block['label'] }}:</strong>
                                        @foreach ($block['options'] as $option)
                                            {{ $option['selected'] ? '■' : '□' }} {{ $option['text'] }}&nbsp;&nbsp;
                                        @endforeach
                                        @if ($block['value'] && ! collect($block['options'])->contains(fn ($o) => $o['selected']))
                                            — {{ $block['value'] }}
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        @else
                            <div class="options-line">
                                <span class="label">{{ $block['label'] }}:</span>
                                @foreach ($block['options'] as $option)
                                    <span class="option">
                                        <span @class(['option-box', 'checked' => $option['selected']])></span>
                                        {{ $option['text'] }}
                                    </span>
                                @endforeach
                                @if ($block['value'] && ! collect($block['options'])->contains(fn ($o) => $o['selected']))
                                    <span class="option">— {{ $block['value'] }}</span>
                                @endif
                            </div>
                        @endif
                    @elseif ($block['type'] === 'table')
                        @if ($block['label'] && ! $section['title'])
                            <div class="table-caption">{{ $block['label'] }}:</div>
                        @endif
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width:36px">ردیف</th>
                                    @foreach ($block['columns'] as $column)
                                        <th>{{ $column }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($block['rows'] as $rowIndex => $row)
                                    <tr>
                                        <td>{{ $rowIndex + 1 }}</td>
                                        @foreach ($row as $value)
                                            <td>{{ $value !== '—' ? $value : '' }}</td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($block['columns']) + 1 }}">&nbsp;</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @endif
                @endforeach
            </div>
        @endforeach

        <div class="declaration">
            بدینوسیله صحت کلیه اطلاعات مندرج در این فرم را تأیید و گواهی می‌نمایم و مسئولیت اطلاعات نادرست اعلام‌شده را می‌پذیرم.
            <br>
            <strong>نام و نام خانوادگی:</strong> {{ $applicantName }}
            &nbsp;&nbsp;
            <strong>امضا:</strong> ........................................
        </div>

        @if (! empty($layout['process']))
            @if ($forPdf)
                <table class="process-grid" style="margin-top:8px;">
                    <tr>
                        <td>
                            <strong>{{ $layout['process']['title'] }}</strong>
                            @if (! empty($layout['process']['summary']))
                                <table class="process-grid" style="margin-top:5px;">
                                    @foreach ($layout['process']['summary'] as $item)
                                        <tr>
                                            <td class="label">{{ $item['label'] }}:</td>
                                            <td>{{ $item['value'] }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endif
                            @foreach ($layout['process']['interviews'] ?? [] as $interview)
                                <table class="process-grid" style="margin-top:6px;">
                                    <tr>
                                        <td class="label" colspan="2"><strong>{{ $interview['title'] }}</strong></td>
                                    </tr>
                                    @foreach ($interview['fields'] as $field)
                                        <tr>
                                            <td class="label">{{ $field['label'] }}:</td>
                                            <td>{{ $field['value'] }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endforeach
                        </td>
                    </tr>
                </table>
            @else
                <div class="process-block">
                    <h3>{{ $layout['process']['title'] }}</h3>

                    @if (! empty($layout['process']['summary']))
                        <table class="process-grid">
                            @foreach ($layout['process']['summary'] as $item)
                                <tr>
                                    <td class="label">{{ $item['label'] }}:</td>
                                    <td>{{ $item['value'] }}</td>
                                </tr>
                            @endforeach
                        </table>
                    @endif

                    @foreach ($layout['process']['interviews'] ?? [] as $interview)
                        <div class="process-interview">
                            <div class="process-interview-title">{{ $interview['title'] }}</div>
                            <table class="process-grid">
                                @foreach ($interview['fields'] as $field)
                                    <tr>
                                        <td class="label">{{ $field['label'] }}:</td>
                                        <td>{{ $field['value'] }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</body>
</html>
