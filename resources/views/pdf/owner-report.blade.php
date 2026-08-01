<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('owner_report.page_title') }} — {{ $report['periodLabel'] }}</title>
    <style>
        /* PDF tidak menyokong oklch() — nilai hex di bawah dipadankan secara visual
           dengan token jenama supaya laporan bercetak kekal berjenama DBENA. */
        @page { margin: 26mm 16mm 20mm 16mm; }

        * { box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.5;
            color: #1f2430;
            margin: 0;
        }

        .header {
            border-bottom: 2px solid #d4a437;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .brand { font-size: 17px; font-weight: bold; color: #b8860b; letter-spacing: 0.6px; }
        .subtitle { font-size: 10px; color: #6b7280; margin-top: 2px; }
        .meta { font-size: 8.5px; color: #9096a3; margin-top: 6px; }

        h2 { font-size: 12px; margin: 0 0 8px; color: #1f2430; }
        h3 { font-size: 10.5px; margin: 12px 0 6px; color: #374151; }

        .summary-box {
            background: #f7f8fa;
            border: 1px solid #e3e6ec;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 14px;
        }
        .headline { font-size: 10.5px; color: #374151; margin: 0 0 8px; }

        table { width: 100%; border-collapse: collapse; }
        .stats td {
            width: 16.6%;
            text-align: center;
            padding: 6px 4px;
            border-right: 1px solid #e3e6ec;
        }
        .stats td:last-child { border-right: 0; }
        .stat-label { font-size: 7.5px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.3px; }
        .stat-value { font-size: 15px; font-weight: bold; margin-top: 2px; }

        .owner {
            border: 1px solid #e3e6ec;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 14px;
            page-break-inside: avoid;
        }
        .owner-head { margin-bottom: 8px; }
        .grade {
            display: inline-block;
            width: 30px; height: 30px; line-height: 30px;
            text-align: center; font-size: 15px; font-weight: bold;
            border-radius: 6px; margin-right: 8px;
        }
        .owner-name { font-size: 13px; font-weight: bold; vertical-align: middle; }
        .owner-score { font-size: 11px; color: #6b7280; vertical-align: middle; margin-left: 6px; }

        .counts { font-size: 9px; color: #4b5563; margin-top: 5px; }
        .counts b { font-size: 10px; }

        .bar-track { background: #e3e6ec; height: 6px; border-radius: 3px; margin: 7px 0 2px; }
        .bar-fill { height: 6px; border-radius: 3px; }

        .comment {
            background: #f7f8fa;
            border-left: 2px solid #d4d8e0;
            padding: 5px 8px;
            margin-bottom: 4px;
            font-size: 9.5px;
            color: #374151;
        }

        .action { padding: 5px 8px; margin-bottom: 4px; font-size: 9.5px; border-left: 3px solid #9096a3; background: #fafbfc; }
        .action-label { font-weight: bold; color: #1f2430; }
        .action-detail { color: #5b6270; margin-top: 2px; }
        .pill {
            display: inline-block; font-size: 7px; font-weight: bold;
            text-transform: uppercase; padding: 1px 4px; border-radius: 3px; margin-right: 4px;
        }
        .p-high { background: #fde8e8; color: #b42318; border-left-color: #b42318; }
        .p-medium { background: #fef4e2; color: #b45309; border-left-color: #b45309; }
        .p-low { background: #e7f6ec; color: #15803d; border-left-color: #15803d; }

        .diag {
            border: 1px solid #e3e6ec;
            border-left: 3px solid #9096a3;
            background: #fafbfc;
            padding: 6px 8px;
            margin-bottom: 5px;
            page-break-inside: avoid;
        }
        .diag-head { font-size: 10px; font-weight: bold; margin-bottom: 3px; }
        .diag-nums { font-weight: normal; color: #6b7280; font-size: 8.5px; margin-left: 5px; }
        .diag-points { margin: 0 0 8px 16px; padding: 0; }
        .diag-points li { font-size: 10.5px; line-height: 1.45; margin-bottom: 2px; }
        .diag-text { font-size: 9px; color: #374151; line-height: 1.45; }
        .diag-impact {
            font-size: 8.5px; color: #b42318; margin-top: 4px;
            background: #fdf3f2; padding: 4px 6px; border-radius: 3px;
        }
        .diag-action { font-size: 8.5px; color: #4b5563; margin-top: 4px; line-height: 1.45; }

        .metrics { margin-top: 8px; font-size: 8.5px; }
        .metrics th {
            text-align: left; font-size: 7.5px; text-transform: uppercase;
            color: #6b7280; border-bottom: 1px solid #d4d8e0; padding: 3px 4px;
        }
        .metrics td { padding: 3px 4px; border-bottom: 1px solid #eef0f4; }
        .dot { display: inline-block; width: 6px; height: 6px; border-radius: 50%; margin-right: 3px; }

        .footer {
            position: fixed; bottom: -12mm; left: 0; right: 0;
            font-size: 7.5px; color: #9096a3; text-align: center;
        }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>

@php
    // Terjemah token oklch → hex untuk enjin PDF.
    $hex = fn (string $token) => match (true) {
        str_contains($token, '145') => '#15803d',
        str_contains($token, '0.78 0.15 85'), str_contains($token, '70') => '#b45309',
        str_contains($token, '25') => '#b42318',
        str_contains($token, '85') => '#b8860b',
        default => '#6b7280',
    };
    $summary = $report['summary'];
@endphp

<div class="header">
    <div class="brand">DBENA SDN BHD</div>
    <div class="subtitle">{{ __('owner_report.page_title') }} — {{ $report['periodLabel'] }}</div>
    <div class="meta">
        {{ __('owner_report.filter_period') }}: {{ $report['period']->label() }}
        @if ($report['service']) · {{ __('owner_report.filter_service') }}: {{ $report['service']->name }} @endif
        · {{ __('owner_report.generated_at') }}: {{ $report['generatedAt']->translatedFormat('d M Y, H:i') }}
        @if ($user) · {{ __('owner_report.generated_by') }}: {{ $user->name }} @endif
    </div>
</div>

{{-- Ringkasan pasukan --}}
<div class="summary-box">
    <h2 style="margin-bottom:6px">{{ __('owner_report.summary.title') }}</h2>
    <p class="headline">{{ $summary['headline'] }}</p>

    <table class="stats">
        <tr>
            <td>
                <div class="stat-label">{{ __('owner_report.summary.team_score') }}</div>
                <div class="stat-value" style="color: {{ $hex($summary['teamScoreColor']) }}">{{ $summary['teamScore'] }}%</div>
            </td>
            <td>
                <div class="stat-label">{{ __('owner_report.summary.owners') }}</div>
                <div class="stat-value">{{ $summary['ownerCount'] }}</div>
            </td>
            <td>
                <div class="stat-label">{{ __('owner_report.summary.metrics_tracked') }}</div>
                <div class="stat-value">{{ $summary['totalMetrics'] }}</div>
            </td>
            <td>
                <div class="stat-label">{{ __('owner_report.summary.on_track') }}</div>
                <div class="stat-value" style="color:#15803d">{{ $summary['totalGreen'] }}</div>
            </td>
            <td>
                <div class="stat-label">{{ __('owner_report.summary.critical') }}</div>
                <div class="stat-value" style="color:#b42318">{{ $summary['totalRed'] }}</div>
            </td>
            <td>
                <div class="stat-label">{{ __('owner_report.summary.pending') }}</div>
                <div class="stat-value" style="color:#6b7280">{{ $summary['totalPending'] }}</div>
            </td>
        </tr>
    </table>
</div>

@if ($report['owners']->isEmpty())
    <p style="text-align:center;color:#6b7280;padding:30px 0">{{ __('owner_report.no_data') }}</p>
@endif

{{-- Setiap PIC --}}
@foreach ($report['owners'] as $index => $block)
    <div class="owner">
        <div class="owner-head">
            <span class="grade" style="background: {{ $hex($block['scoreColor']) }}1a; color: {{ $hex($block['scoreColor']) }}">
                {{ $block['grade'] }}
            </span>
            <span class="owner-name">{{ $block['name'] }}</span>
            <span class="owner-score">
                {{ $block['scorePct'] }}% · {{ $block['green'] }}/{{ $block['total'] }} {{ __('owner_report.owner.on_track') }}
                @if ($block['trend']['available'])
                    · {{ $block['trend']['delta'] > 0 ? '+' : '' }}{{ $block['trend']['delta'] }} {{ __('owner_report.owner.vs_previous') }}
                @endif
            </span>
        </div>

        <div class="bar-track">
            <div class="bar-fill" style="width: {{ min(100, $block['scorePct']) }}%; background: {{ $hex($block['scoreColor']) }}"></div>
        </div>

        <div class="counts">
            <b style="color:#15803d">{{ $block['green'] }}</b> {{ __('owner_report.owner.on_track') }} ·
            <b style="color:#b45309">{{ $block['yellow'] }}</b> {{ __('owner_report.owner.has_plan') }} ·
            <b style="color:#b42318">{{ $block['red'] }}</b> {{ __('owner_report.owner.no_plan') }}
            @if ($block['pending'] > 0) · <b style="color:#6b7280">{{ $block['pending'] }}</b> {{ __('owner_report.owner.pending') }} @endif
        </div>

        {{-- Ulasan --}}
        <h3>{{ __('owner_report.commentary_title') }}</h3>
        @foreach ($block['commentary'] as $line)
            <div class="comment">{{ $line }}</div>
        @endforeach

        {{-- Analisis punca --}}
        @if ($block['diagnoses']->isNotEmpty())
            <h3>{{ __('funnel.title') }}</h3>
            @foreach ($block['diagnoses'] as $d)
                @php $dHex = $d['severity'] === 'critical' ? '#b42318' : '#b45309'; @endphp
                <div class="diag" style="border-left-color: {{ $dHex }}">
                    <div class="diag-head" style="color: {{ $dHex }}">
                        {{ $d['label'] }}
                        <span class="diag-nums">{{ $d['actualLabel'] }} / {{ $d['targetLabel'] }}</span>
                    </div>
                    <ul class="diag-points">
                        @foreach ($d['points'] as $pt)
                            <li>{{ $pt['text'] }}</li>
                        @endforeach
                    </ul>

                    @if (! empty($d['impacts']))
                        <div class="diag-impact">
                            <b>{{ __('funnel.downstream_impact') }}:</b>
                            {{ collect($d['impacts'])->map(fn ($i) => $i['label'].' ('.($i['pct'] !== null ? number_format((float) $i['pct'], 1).'%' : '—').')')->implode(', ') }}
                        </div>
                    @endif

                    @foreach ($d['actions'] as $act)
                        <div class="diag-action">
                            <span class="pill p-{{ $act['priority'] }}">{{ __('owner_report.priority.'.$act['priority']) }}</span>
                            <b>{{ $act['label'] }}</b> — {{ $act['detail'] }}
                        </div>
                    @endforeach
                </div>
            @endforeach
        @endif

        {{-- Tindakan umum --}}
        <h3>{{ __('owner_report.actions_title') }}</h3>
        @foreach ($block['actions'] as $action)
            <div class="action p-{{ $action['priority'] }}">
                <span class="pill p-{{ $action['priority'] }}">{{ __('owner_report.priority.'.$action['priority']) }}</span>
                <span class="action-label">{{ $action['label'] }}</span>
                <div class="action-detail">{{ $action['detail'] }}</div>
            </div>
        @endforeach

        {{-- Metrik --}}
        <h3>{{ __('owner_report.metrics_title') }}</h3>
        <table class="metrics">
            <thead>
                <tr>
                    <th style="width:34%">{{ __('owner_report.col.metric') }}</th>
                    <th style="width:16%">{{ __('owner_report.col.service') }}</th>
                    <th style="width:14%">{{ __('owner_report.col.actual') }}</th>
                    <th style="width:14%">{{ __('owner_report.col.target') }}</th>
                    <th style="width:10%">{{ __('owner_report.col.achievement') }}</th>
                    <th style="width:12%">{{ __('owner_report.col.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($block['metrics'] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td style="color:#6b7280">{{ $row['service'] }}</td>
                        <td>{{ $row['actualLabel'] }}</td>
                        <td style="color:#5b6270">{{ $row['targetLabel'] }}</td>
                        <td>{{ $row['pct'] !== null ? number_format($row['pct'], 1).'%' : '—' }}</td>
                        <td>
                            <span class="dot" style="background: {{ $hex($row['statusColor']) }}"></span>{{ $row['statusLabel'] }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if (($index + 1) % 2 === 0 && ! $loop->last)
        <div class="page-break"></div>
    @endif
@endforeach

<div class="footer">
    DBENA SDN BHD · {{ __('owner_report.page_title') }} · {{ $report['periodLabel'] }}
</div>

</body>
</html>
