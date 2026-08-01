@php
    /*
     * Sengaja TIADA pernyataan "use" di sini. Blok PHP dikompil ke dalam
     * fail paparan yang dijana, dan pernyataan use di dalamnya berkelakuan
     * berbeza mengikut versi Blade — kegagalannya senyap dan sukar dikesan.
     * Nama kelas penuh lebih panjang tetapi tidak pernah mengejutkan.
     *
     * Status dibandingkan mengikut NILAI dan bukan contoh enum, supaya
     * baris yang membawa rentetan biasa tidak menghempaskan laporan.
     */
    $owners = $report['owners'];
    $summary = $report['summary'];
    $x = $exec;

    $periodLabel = $report['periodLabel'];

    $sevColor = match ($x['severityKey']) {
        'critical' => '#c0392b',
        'attention' => '#c98a12',
        default => '#1e8449',
    };

    $nilaiStatus = fn ($s) => $s instanceof \App\Enums\MetricStatus ? $s->value : (string) $s;

    $statusColor = fn ($s) => match ($nilaiStatus($s)) {
        'green' => '#1e8449',
        'yellow' => '#c98a12',
        'red' => '#c0392b',
        default => '#8a8f9c',
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('exec.title') }} — {{ $periodLabel }}</title>
    <style>
        /* DomPDF tidak menyokong oklch() mahupun flexbox. Semua susun atur
           menggunakan jadual, dan warna dalam hex yang dipadankan secara
           visual dengan token jenama DBENA. */
        @page { margin: 20mm 15mm 18mm 15mm; }

        * { box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            line-height: 1.5;
            color: #22272f;
            margin: 0;
        }

        p { margin: 0 0 7px; }

        .cover { text-align: center; padding: 4px 0 12px; border-bottom: 3px solid #c8a028; }
        .cover .co { font-size: 12px; font-weight: bold; color: #8a6d1a; letter-spacing: 2.5px; }
        .cover .ttl { font-size: 21px; font-weight: bold; color: #1a1f2b; letter-spacing: 1px; margin-top: 6px; }
        .cover .sub { font-size: 10px; color: #5b6270; letter-spacing: 1.4px; margin-top: 5px; }
        .rule { height: 3px; background: #c8a028; width: 90px; margin: 10px auto 0; }
        .kicker { text-align: center; font-size: 9px; color: #7b8290; margin: 9px 0 13px; font-style: italic; }

        table { width: 100%; border-collapse: collapse; }

        .meta-tbl td { border: 1px solid #dde1e8; padding: 5px 8px; font-size: 8.5px; }
        .meta-tbl td.k { background: #f4f6f9; font-weight: bold; width: 32%; color: #4a5160; }

        .focus {
            background: #f9f7ef; border-left: 3px solid #c8a028;
            padding: 7px 10px; font-size: 8.5px; color: #5b6270; margin: 12px 0 4px;
        }

        h1 {
            font-size: 13px; color: #1a1f2b; margin: 0 0 9px;
            padding: 6px 0 5px; border-bottom: 2px solid #c8a028;
        }
        h2 { font-size: 10.5px; color: #2f3644; margin: 13px 0 6px; }

        .tiles td {
            width: 25%; text-align: center; padding: 9px 5px;
            border: 1px solid #dde1e8; background: #f7f9fb;
        }
        .tiles .big { font-size: 16px; font-weight: bold; display: block; }
        .tiles .lbl { font-size: 7.5px; letter-spacing: 0.9px; color: #6b7280; display: block; margin-top: 3px; }
        .tiles .note { font-size: 7.5px; color: #9096a3; display: block; margin-top: 1px; }

        .data { margin: 6px 0 4px; }
        .data th {
            background: #2f3644; color: #fff; font-size: 8px; font-weight: bold;
            text-align: left; padding: 5px 6px; border: 1px solid #2f3644;
        }
        .data td { border: 1px solid #dde1e8; padding: 4.5px 6px; font-size: 8.5px; vertical-align: top; }
        .data td.n { text-align: right; }
        .data td.c { text-align: center; }
        .rank {
            display: inline-block; width: 15px; height: 15px; line-height: 15px;
            text-align: center; border-radius: 8px; background: #c0392b;
            color: #fff; font-size: 8px; font-weight: bold;
        }
        .pill {
            display: inline-block; padding: 1px 5px; border-radius: 3px;
            font-size: 7.5px; font-weight: bold; color: #fff;
        }

        ul { margin: 4px 0 8px; padding-left: 15px; }
        li { font-size: 8.5px; margin-bottom: 3px; line-height: 1.45; }

        .callout {
            border-left: 3px solid #c0392b; background: #fdf3f2;
            padding: 8px 10px; margin: 7px 0 10px; font-size: 8.5px; color: #4a5160;
        }
        .callout.ok { border-left-color: #1e8449; background: #f1f9f4; }

        .chain td {
            text-align: center; padding: 8px 4px;
            border: 1px solid #dde1e8; background: #f7f9fb;
        }
        .chain .st { font-size: 8px; font-weight: bold; letter-spacing: 0.5px; display: block; }
        .chain .vl { font-size: 8.5px; color: #2f3644; display: block; margin-top: 3px; }
        .chain .pc { font-size: 11px; font-weight: bold; display: block; margin-top: 2px; }
        .chain td.ar { width: 3%; border: none; background: none; font-size: 11px; color: #b8bec9; }

        .footer {
            position: fixed; bottom: -12mm; left: 0; right: 0;
            text-align: center; font-size: 7px; color: #a4aab5;
            border-top: 1px solid #e6e9ee; padding-top: 4px;
        }
        .break { page-break-before: always; }
        .avoid { page-break-inside: avoid; }
    </style>
</head>
<body>

<div class="footer">
    {{ __('exec.company') }} · {{ __('exec.title') }} · {{ $periodLabel }}@if ($report['owner']) · {{ $report['owner']->name }}@endif
</div>

{{-- ══════════ MUKA HADAPAN ══════════ --}}
<div class="cover">
    <div class="co">{{ __('exec.company') }}</div>
    <div class="ttl">{{ __('exec.title') }}</div>
    <div class="sub">
        {{ $report['service']
            ? __('exec.subtitle_service', ['service' => mb_strtoupper($report['service']->name), 'period' => mb_strtoupper($periodLabel)])
            : __('exec.subtitle_all', ['period' => mb_strtoupper($periodLabel)]) }}
    </div>
    <div class="rule"></div>
</div>

<div class="kicker">{{ __('exec.kicker') }}</div>

<table class="meta-tbl avoid">
    <tr>
        <td class="k">{{ __('exec.meta.period') }}</td>
        <td>{{ $periodLabel }}</td>
    </tr>
    <tr>
        <td class="k">{{ __('exec.meta.owner') }}</td>
        <td>{{ $report['owner']->name ?? __('exec.meta.all_owners') }}</td>
    </tr>
    <tr>
        <td class="k">{{ __('exec.meta.generated') }}</td>
        <td>{{ $report['generatedAt']->translatedFormat('j F Y, H:i') }}</td>
    </tr>
    <tr>
        <td class="k">{{ __('exec.meta.prepared_for') }}</td>
        <td>{{ __('exec.meta.prepared_for_value') }}@if ($user) · {{ $user->name }}@endif</td>
    </tr>
    <tr>
        <td class="k">{{ __('exec.meta.status') }}</td>
        <td>{{ __('exec.meta.status_value') }}</td>
    </tr>
</table>

<div class="focus">{{ __('exec.focus') }}</div>

{{-- ══════════ 1. RINGKASAN EKSEKUTIF ══════════ --}}
<h1>{{ __('exec.s1') }}</h1>

<p>{{ __('exec.exec_lead', [
    'period' => $periodLabel,
    'severity' => $x['severity'],
    'green' => $summary['totalGreen'],
    'total' => $summary['totalMetrics'],
    'red' => $summary['totalRed'],
]) }}</p>

<table class="tiles avoid">
    <tr>
        <td>
            <span class="big" style="color: {{ $sevColor }}">{{ $summary['teamScore'] }}%</span>
            <span class="lbl">{{ __('exec.tile.score') }}</span>
            <span class="note">{{ __('exec.tile.score_note', ['severity' => $x['severity']]) }}</span>
        </td>
        <td>
            <span class="big" style="color: #2f3644">{{ $summary['totalGreen'] }}/{{ $summary['totalMetrics'] }}</span>
            <span class="lbl">{{ __('exec.tile.achieved') }}</span>
            <span class="note">{{ __('exec.tile.achieved_note', ['red' => $summary['totalRed']]) }}</span>
        </td>
        <td>
            <span class="big" style="color: #2f3644">{{ $summary['ownerCount'] }}</span>
            <span class="lbl">{{ __('exec.tile.owners') }}</span>
            <span class="note">{{ __('exec.tile.owners_note') }}</span>
        </td>
        <td>
            <span class="big" style="color: {{ $x['gapTotal'] > 0 ? '#c0392b' : '#1e8449' }}">RM{{ number_format($x['gapTotal']) }}</span>
            <span class="lbl">{{ __('exec.tile.gap') }}</span>
            <span class="note">{{ __('exec.tile.gap_note') }}</span>
        </td>
    </tr>
</table>

@if ($x['priorities'])
    <table class="data avoid">
        <tr>
            <th style="width: 9%">{{ __('exec.priority_table.rank') }}</th>
            <th style="width: 26%">{{ __('exec.priority_table.issue') }}</th>
            <th style="width: 24%">{{ __('exec.priority_table.evidence') }}</th>
            <th>{{ __('exec.priority_table.implication') }}</th>
        </tr>
        @foreach ($x['priorities'] as $p)
            <tr>
                <td class="c"><span class="rank">{{ $p['rank'] }}</span></td>
                <td><b>{{ $p['issue'] }}</b></td>
                <td>{{ $p['evidence'] }}</td>
                <td>{{ $p['implication'] }}</td>
            </tr>
        @endforeach
    </table>
@endif

<h2>{{ __('exec.summary_heading') }}</h2>
<ul>
    @foreach ($x['priorities'] as $p)
        <li><b>{{ $p['issue'] }}</b> — {{ $p['implication'] }}</li>
    @endforeach
    @if ($x['noPlanCount'] > 0)
        <li>{{ __('exec.no_plan_note', ['count' => $x['noPlanCount']]) }}</li>
    @endif
    @if ($x['missingTargets']->isNotEmpty())
        <li>{{ __('exec.no_target_note', ['metrics' => $x['missingTargets']->implode(', ')]) }}</li>
    @endif
</ul>

{{-- ══════════ 2. SCORECARD ══════════ --}}
<h1 class="break">{{ __('exec.s2') }}</h1>

<table class="data">
    <tr>
        <th style="width: 26%">{{ __('exec.scorecard.metric') }}</th>
        <th style="width: 12%">{{ __('exec.scorecard.pic') }}</th>
        <th style="width: 13%">{{ __('exec.scorecard.actual') }}</th>
        <th style="width: 13%">{{ __('exec.scorecard.target') }}</th>
        <th style="width: 11%">{{ __('exec.scorecard.pct') }}</th>
        <th style="width: 11%">{{ __('exec.scorecard.status') }}</th>
        <th>{{ __('exec.scorecard.gap') }}</th>
    </tr>
    @foreach ($x['scorecard'] as $m)
        <tr>
            <td>{{ $m['label'] }}</td>
            <td>{{ $m['ownerName'] }}</td>
            <td class="n">{{ $m['actualLabel'] }}</td>
            <td class="n">{{ $m['targetLabel'] }}</td>
            <td class="n"><b>{{ $m['pctLabel'] }}</b></td>
            <td class="c"><span class="pill" style="background: {{ $statusColor($m['status']) }}">{{ $m['statusLabel'] }}</span></td>
            <td class="n">{{ $m['gapLabel'] }}</td>
        </tr>
    @endforeach
</table>

<h2>{{ __('exec.pic_heading') }}</h2>
<table class="data avoid">
    <tr>
        <th style="width: 15%">{{ __('exec.pic_table.pic') }}</th>
        <th style="width: 9%">{{ __('exec.pic_table.score') }}</th>
        <th style="width: 8%">{{ __('exec.pic_table.grade') }}</th>
        <th style="width: 13%">{{ __('exec.pic_table.achieved') }}</th>
        <th style="width: 11%">{{ __('exec.pic_table.no_plan') }}</th>
        <th>{{ __('exec.pic_table.verdict') }}</th>
    </tr>
    @foreach ($owners as $o)
        <tr>
            <td><b>{{ $o['name'] }}</b></td>
            <td class="n">{{ $o['scorePct'] }}%</td>
            <td class="c"><b>{{ $o['grade'] }}</b></td>
            <td class="c">{{ $o['green'] }} / {{ $o['total'] }}</td>
            <td class="c">{{ $o['pending'] }}</td>
            {{-- commentary() memulangkan SENARAI ayat, bukan satu rentetan.
                 Paparan dashboard membacanya sebagai senarai; di sini ia
                 dicantumkan menjadi satu perenggan supaya muat dalam sel. --}}
            <td>{{ collect($o['commentary'])->implode(' ') }}</td>
        </tr>
    @endforeach
</table>

@if ($x['observations'])
    <h2>{{ __('exec.observations_heading') }}</h2>
    <ul>
        @foreach ($x['observations'] as $obs)
            <li>{{ $obs }}</li>
        @endforeach
    </ul>
@endif

{{-- ══════════ 3. PUNCA AKAR ══════════ --}}
<h1 class="break">{{ __('exec.s3') }}</h1>

<h2>{{ __('exec.s3_1') }}</h2>
@if (! empty($x['journey']['stages']))
    <table class="chain avoid">
        <tr>
            @foreach ($x['journey']['stages'] as $i => $st)
                @if ($i > 0)
                    <td class="ar">&#8594;</td>
                @endif
                @php
                    $sc = match ($st['status']) {
                        'red' => '#c0392b',
                        'amber' => '#c98a12',
                        'green' => '#1e8449',
                        default => '#8a8f9c',
                    };
                @endphp
                <td style="border-top: 3px solid {{ $sc }}">
                    <span class="st" style="color: {{ $sc }}">{{ $st['title'] }}</span>
                    <span class="vl">{{ $st['actualLabel'] }} / {{ $st['targetLabel'] }}</span>
                    <span class="pc" style="color: {{ $sc }}">{{ $st['pctLabel'] }}</span>
                </td>
            @endforeach
        </tr>
    </table>
@endif

@if ($x['rootCauses'])
    <table class="data">
        <tr>
            <th style="width: 32%">{{ __('exec.root_table.cause') }}</th>
            <th style="width: 21%">{{ __('exec.root_table.evidence') }}</th>
            <th>{{ __('exec.root_table.effect') }}</th>
            <th style="width: 13%">{{ __('exec.root_table.level') }}</th>
        </tr>
        @foreach ($x['rootCauses'] as $rc)
            <tr>
                <td>{{ $rc['cause'] }}</td>
                <td>{{ $rc['evidence'] }}</td>
                <td>{{ $rc['effect'] }}</td>
                <td class="c">{{ $rc['level'] }}</td>
            </tr>
        @endforeach
    </table>
@endif

<h2>{{ __('exec.s3_2') }}</h2>
<div class="callout {{ $x['journey']['healthy'] ? 'ok' : '' }}">
    {{ $x['journey']['healthy']
        ? __('exec.diagnosis_clear')
        : __('exec.diagnosis_break', ['stage' => $x['journey']['firstBreak']['title']]) }}
</div>

<h2>{{ __('exec.s3_3') }}</h2>
<ul>
    @foreach ((array) __('exec.risks') as $risk)
        <li>{{ $risk }}</li>
    @endforeach
</ul>

{{-- ══════════ 4. PELAN TINDAKAN ══════════ --}}
<h1 class="break">{{ __('exec.s4') }}</h1>
<p>{{ __('exec.s4_note') }}</p>

<h2>{{ __('exec.weekly_heading') }}</h2>
@if ($x['weeklyTargets'])
    <table class="data">
        <tr>
            <th style="width: 27%">{{ __('exec.weekly_table.metric') }}</th>
            <th style="width: 17%">{{ __('exec.weekly_table.weekly') }}</th>
            <th style="width: 15%">{{ __('exec.weekly_table.owner') }}</th>
            <th style="width: 17%">{{ __('exec.weekly_table.cadence') }}</th>
            <th>{{ __('exec.weekly_table.trigger') }}</th>
        </tr>
        @foreach ($x['weeklyTargets'] as $w)
            <tr>
                <td>{{ $w['label'] }}</td>
                <td class="n"><b>{{ $w['weekly'] }}</b></td>
                <td>{{ $w['owner'] }}</td>
                <td>{{ $w['cadence'] }}</td>
                <td>{{ $w['trigger'] }}</td>
            </tr>
        @endforeach
    </table>
@endif

{{-- ══════════ 5. MENGIKUT PIC ══════════ --}}
<h1 class="break">{{ __('exec.s5') }}</h1>

@foreach ($owners as $i => $o)
    <h2>5.{{ $i + 1 }} {{ $o['name'] }} — {{ $o['scorePct'] }}% ({{ $o['grade'] }})</h2>

    @php
        $kritikal = collect($o['metrics'])
            ->filter(fn (array $m) => $nilaiStatus($m['status']) === 'red')
            ->take(6);
    @endphp

    @if ($kritikal->isNotEmpty())
        <table class="data avoid">
            <tr>
                <th style="width: 26%">{{ __('exec.pic_focus_table.focus') }}</th>
                <th style="width: 20%">{{ __('exec.pic_focus_table.problem') }}</th>
                <th>{{ __('exec.pic_focus_table.action') }}</th>
                <th style="width: 16%">{{ __('exec.pic_focus_table.kpi') }}</th>
            </tr>
            @foreach ($kritikal as $m)
                <tr>
                    <td><b>{{ $m['label'] }}</b></td>
                    <td>{{ $m['actualLabel'] }} / {{ $m['targetLabel'] }}</td>
                    <td>{{ filled($m['actionPlan']) ? $m['actionPlan'] : __('exec.no_action_recorded') }}</td>
                    <td class="n">{{ $m['targetLabel'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif
@endforeach

<h2>{{ __('exec.accountability_heading') }}</h2>
<ul>
    @foreach ((array) __('exec.accountability') as $rule)
        <li>{{ $rule }}</li>
    @endforeach
</ul>

{{-- ══════════ 6. PEMANTAUAN ══════════ --}}
<h1 class="break">{{ __('exec.s6') }}</h1>

<h2>{{ __('exec.s6_1') }}</h2>
@if (! empty($x['journey']['stages']))
    <table class="data avoid">
        <tr>
            <th style="width: 24%">{{ __('exec.huddle_table.agenda') }}</th>
            <th style="width: 32%">{{ __('exec.huddle_table.data') }}</th>
            <th style="width: 16%">{{ __('exec.huddle_table.owner') }}</th>
            <th>{{ __('exec.huddle_table.decision') }}</th>
        </tr>
        @foreach ($x['journey']['stages'] as $st)
            <tr>
                <td><b>{{ $st['title'] }}</b></td>
                <td>{{ $st['metricLabel'] }} — {{ $st['actualLabel'] }} / {{ $st['targetLabel'] }}</td>
                <td>{{ $st['owner'] ?? __('exec.none') }}</td>
                <td>{{ $st['perWeekLabel'] ? __('journey.target').': '.$st['perWeekLabel'] : '—' }}</td>
            </tr>
        @endforeach
    </table>
@endif

<h2>{{ __('exec.s6_2') }}</h2>
<ul>
    @foreach ((array) __('exec.weekly_review') as $step)
        <li>{{ $step }}</li>
    @endforeach
</ul>

{{-- ══════════ 7. KEPUTUSAN ══════════ --}}
<h1>{{ __('exec.s7') }}</h1>

<p>{{ __('exec.s7_lead', [
    'period' => $periodLabel,
    'score' => $summary['teamScore'],
    'red' => $summary['totalRed'],
    'total' => $summary['totalMetrics'],
]) }}</p>

@if ($x['weeklyTargets'])
    <table class="data avoid">
        <tr>
            <th style="width: 30%">{{ __('exec.decision_table.decision') }}</th>
            <th>{{ __('exec.decision_table.proposal') }}</th>
            <th style="width: 18%">{{ __('exec.decision_table.approver') }}</th>
            <th style="width: 12%">{{ __('exec.decision_table.date') }}</th>
        </tr>
        @foreach ($x['weeklyTargets'] as $w)
            <tr>
                <td>{{ $w['label'] }}</td>
                <td>{{ $w['weekly'] }} · {{ $w['owner'] }} · {{ $w['cadence'] }}</td>
                <td>{{ __('exec.approver') }}</td>
                <td>{{ __('exec.immediately') }}</td>
            </tr>
        @endforeach
    </table>
@endif

<h2>{{ __('exec.conclusion_heading') }}</h2>
<ul>
    <li>{{ __('exec.conclusion_cause', ['cause' => $x['rootCauses'][0]['cause'] ?? __('exec.diagnosis_clear')]) }}</li>
    <li>{{ __('exec.conclusion_action') }}</li>
    <li>{{ __('exec.conclusion_success') }}</li>
</ul>

<div class="focus" style="margin-top: 14px">
    {{ __('exec.source', ['period' => $periodLabel]) }}
</div>

</body>
</html>
