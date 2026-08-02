@php
    /* Sengaja TIADA "use" — lihat tools/check-blade-use.py.
       DomPDF: tiada flexbox, tiada <svg> sebaris, tiada transform.
       Susun atur menggunakan jadual, yang DomPDF papar dengan tepat. */
    $marun = '#5C1240';
    $marunGelap = '#3D0F2B';
    $emas = '#E8B93C';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('task.title') }} — {{ $monthLabel }}</title>
    <style>
        @page { margin: 16pt 20pt; }

        body {
            margin: 0;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 7pt;
            color: #1A1420;
        }

        table { border-collapse: collapse; width: 100%; }
        td, th { padding: 0; }

        .kepala { width: 100%; margin-bottom: 8pt; }
        .tajuk { font-size: 19pt; font-weight: bold; color: {{ $marunGelap }}; letter-spacing: 0.6pt; }
        .tagline { font-size: 8pt; color: #6E6473; letter-spacing: 2pt; margin-top: 2pt; }

        .meta { border: 0.8pt solid #D8CEDA; border-radius: 4pt; padding: 5pt 8pt; }
        .meta-baris { font-size: 6.8pt; padding: 1.4pt 0; }
        .meta-label { color: #6E6473; }
        .meta-nilai { font-weight: bold; color: #1A1420; }

        .petunjuk { margin-bottom: 6pt; }
        .petunjuk-tag {
            background: {{ $marun }}; color: #ffffff;
            font-size: 6.6pt; font-weight: bold;
            padding: 3pt 7pt; border-radius: 8pt;
        }
        .chip {
            display: inline-block;
            font-size: 6.4pt; font-weight: bold;
            padding: 1.4pt 3pt; border-radius: 2pt;
            text-align: center; min-width: 9pt;
        }

        .jadual { border: 0.7pt solid #C9BDCB; }
        .kepala-jadual td {
            background: {{ $marunGelap }}; color: #ffffff;
            font-size: 6.4pt; font-weight: bold;
            padding: 3pt 3pt; text-align: center;
            border-right: 0.5pt solid #6C4C63;
        }
        .jalur-jabatan td {
            background: {{ $marun }}; color: #ffffff;
            font-size: 7.4pt; font-weight: bold;
            padding: 3.4pt 5pt; letter-spacing: 0.4pt;
        }
        .baris td {
            border-top: 0.5pt solid #DCD2DD;
            border-right: 0.5pt solid #ECE5EC;
            padding: 2.6pt 3pt;
            font-size: 6.6pt;
        }
        .sel-hari { text-align: center; padding: 1.6pt 0 !important; }
        .tengah { text-align: center; }

        .panel { border: 0.8pt solid #D8CEDA; border-radius: 4pt; }
        .panel-kepala {
            background: {{ $marun }}; color: #ffffff;
            font-size: 7pt; font-weight: bold;
            padding: 4pt 7pt; letter-spacing: 0.5pt;
        }
        .panel-isi { padding: 6pt 8pt; font-size: 6.8pt; line-height: 1.5; }
        .stat-nombor { font-size: 13pt; font-weight: bold; color: {{ $marunGelap }}; }
        .stat-label { font-size: 6.2pt; color: #6E6473; font-weight: bold; }

        .kaki {
            background: {{ $marunGelap }}; color: #ffffff;
            font-size: 7.4pt; font-weight: bold;
            padding: 6pt; text-align: center; letter-spacing: 1pt;
            border-radius: 4pt;
        }
    </style>
</head>
<body>

    {{-- ══ Kepala ══ --}}
    <table class="kepala">
        <tr>
            <td style="width: 130pt; vertical-align: middle">
                @if ($logo)
                    {{-- Data-URI, bukan laluan fail: DomPDF menolak laluan di
                         luar chroot dan gagal SENYAP. --}}
                    <img src="{{ $logo }}" alt="DBENA" style="width: 112pt">
                @endif
            </td>
            <td style="vertical-align: middle; padding-left: 14pt">
                <div class="tajuk">{{ __('task.title') }}</div>
                <div class="tagline">{{ __('task.tagline') }}</div>
            </td>
            <td style="width: 210pt; vertical-align: middle">
                <table class="meta">
                    <tr><td class="meta-baris meta-label">{{ __('task.month') }}</td>
                        <td class="meta-baris meta-nilai">: {{ $monthLabel }}</td></tr>
                    <tr><td class="meta-baris meta-label">{{ __('task.prepared_by') }}</td>
                        <td class="meta-baris meta-nilai">: {{ $board?->prepared_by ?: '—' }}</td></tr>
                    <tr><td class="meta-baris meta-label">{{ __('task.date_prepared') }}</td>
                        <td class="meta-baris meta-nilai">: {{ $board?->prepared_on?->translatedFormat('j F Y') ?: '—' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ══ Petunjuk ══ --}}
    <table class="petunjuk">
        <tr>
            <td style="width: 92pt"><span class="petunjuk-tag">{{ __('task.legend') }}</span></td>
            @foreach ($marks as $m)
                <td style="padding-left: 10pt">
                    <span class="chip" style="background: {{ $m->color() }}; color: {{ $m->textColor() }}">{{ $m->letter() }}</span>
                    <span style="font-size: 6.6pt; color: #4C4451; padding-left: 3pt">{{ $m->label() }}</span>
                </td>
            @endforeach
            <td></td>
        </tr>
    </table>

    {{-- ══ Jadual ══ --}}
    <table class="jadual">
        <tr class="kepala-jadual">
            <td style="width: 26pt">{{ __('task.col.no') }}</td>
            <td style="width: 274pt; text-align: left">{{ __('task.col.task') }}</td>
            <td style="width: 58pt">{{ __('task.col.action_by') }}</td>
            <td style="width: 58pt">{{ __('task.col.monitor_by') }}</td>
            @foreach ($days as $d)
                <td style="width: {{ $dayWidth }}pt">{{ $d }}</td>
            @endforeach
            <td style="width: 128pt">{{ __('task.col.remark') }}</td>
        </tr>

        @foreach ($departments as $dept)
            @php $senarai = $tasksByDepartment[$dept->id] ?? collect(); @endphp

            <tr class="jalur-jabatan">
                <td colspan="{{ 5 + count($days) }}">{{ $dept->name }}</td>
            </tr>

            @foreach ($senarai as $task)
                @php $tandaIkutHari = $task->marks->keyBy('day'); @endphp

                <tr class="baris">
                    <td class="tengah">{{ $loop->iteration }}</td>
                    <td>{{ $task->title }}</td>
                    <td class="tengah">{{ $task->action_by ?: '—' }}</td>
                    <td class="tengah">{{ $task->monitor_by ?: '—' }}</td>

                    @foreach ($days as $d)
                        @php $tanda = $tandaIkutHari->get($d)?->mark; @endphp
                        <td class="sel-hari">
                            @if ($tanda)
                                <span class="chip"
                                      style="background: {{ $tanda->color() }}; color: {{ $tanda->textColor() }}">{{ $tanda->letter() }}</span>
                            @endif
                        </td>
                    @endforeach

                    <td style="font-size: 6.2pt; color: #4C4451">{{ $task->remark ?: '' }}</td>
                </tr>
            @endforeach
        @endforeach
    </table>

    {{-- ══ Panel bawah ══ --}}
    <table style="margin-top: 9pt">
        <tr>
            <td style="width: 33%; vertical-align: top; padding-right: 6pt">
                <table class="panel">
                    <tr><td class="panel-kepala">{{ __('task.priority') }}</td></tr>
                    <tr><td class="panel-isi">
                        @forelse ($board?->priorities ?? [] as $poin)
                            <div>&bull; {{ $poin }}</div>
                        @empty
                            <div style="color: #8A8090">—</div>
                        @endforelse
                    </td></tr>
                </table>
            </td>

            <td style="width: 34%; vertical-align: top; padding: 0 6pt">
                <table class="panel">
                    <tr><td class="panel-kepala">{{ __('task.summary') }}</td></tr>
                    <tr><td class="panel-isi">
                        <table>
                            <tr>
                                <td style="width: 33%"><span class="stat-nombor">{{ $summary['total'] }}</span>
                                    <span class="stat-label">{{ __('task.stat.total') }}</span></td>
                                <td style="width: 33%"><span class="stat-nombor">{{ $summary['inProgress'] }}</span>
                                    <span class="stat-label">{{ __('task.stat.in_progress') }}</span></td>
                                <td><span class="stat-nombor">{{ $summary['cancelled'] }}</span>
                                    <span class="stat-label">{{ __('task.stat.cancelled') }}</span></td>
                            </tr>
                            <tr>
                                <td style="padding-top: 5pt"><span class="stat-nombor">{{ $summary['completed'] }}</span>
                                    <span class="stat-label">{{ __('task.stat.completed') }}</span></td>
                                <td style="padding-top: 5pt"><span class="stat-nombor">{{ $summary['pending'] }}</span>
                                    <span class="stat-label">{{ __('task.stat.pending') }}</span></td>
                                <td style="padding-top: 5pt"><span class="stat-nombor">{{ $summary['focus'] }}%</span>
                                    <span class="stat-label">{{ __('task.stat.focus') }}</span></td>
                            </tr>
                        </table>
                    </td></tr>
                </table>
            </td>

            <td style="width: 33%; vertical-align: top; padding-left: 6pt">
                <table class="panel">
                    <tr><td class="panel-kepala">{{ __('task.notes') }}</td></tr>
                    <tr><td class="panel-isi">
                        @forelse ($board?->notes ?? [] as $nota)
                            <div>&bull; {{ $nota }}</div>
                        @empty
                            <div style="color: #8A8090">—</div>
                        @endforelse
                    </td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ══ Kaki ══ --}}
    <div class="kaki" style="margin-top: 9pt">
        {{ __('task.footer.plan') }} &nbsp;&nbsp;|&nbsp;&nbsp;
        {{ __('task.footer.work') }} &nbsp;&nbsp;|&nbsp;&nbsp;
        {{ __('task.footer.achieve') }}
    </div>
</body>
</html>
