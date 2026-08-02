@php
    /* Sengaja TIADA "use". DomPDF: tiada flexbox, tiada <svg> sebaris.
       Grid kalendar dilukis dengan jadual, yang DomPDF papar dengan tepat. */
    $marun = '#5C1240';
    $marunGelap = '#3D0F2B';
    $stats = $cal['stats'];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('calendar_task.title') }} — {{ $cal['monthLabel'] }}</title>
    <style>
        @page { margin: 16pt 20pt; }
        body { margin: 0; font-family: "DejaVu Sans", sans-serif; font-size: 7pt; color: #1A1420; }
        table { border-collapse: collapse; width: 100%; }
        td, th { padding: 0; }

        .tajuk { font-size: 19pt; font-weight: bold; color: {{ $marunGelap }}; letter-spacing: 0.6pt; }
        .sub { font-size: 8pt; color: #6E6473; margin-top: 2pt; }

        .stat { border: 0.8pt solid #D8CEDA; border-radius: 4pt; padding: 5pt 7pt; }
        .stat-label { font-size: 5.8pt; font-weight: bold; color: #6E6473; letter-spacing: 0.4pt; }
        .stat-nombor { font-size: 15pt; font-weight: bold; color: {{ $marunGelap }}; }

        .grid { border: 0.7pt solid #C9BDCB; margin-top: 9pt; }
        .grid td { border: 0.5pt solid #DCD2DD; vertical-align: top; }
        .kepala-hari {
            background: {{ $marunGelap }}; color: #ffffff;
            font-size: 6.4pt; font-weight: bold;
            text-align: center; padding: 3.4pt 0;
        }
        .no-hari { font-size: 7pt; font-weight: bold; padding: 2.4pt 3pt 1pt; }
        .luar { color: #B6ADB8; }
        .hari-ini { background: {{ $marun }}; color: #ffffff; border-radius: 6pt; padding: 0.6pt 3pt; }
        .acara {
            font-size: 5.6pt; font-weight: bold;
            margin: 0 2.4pt 1.4pt; padding: 1.4pt 2.4pt;
            border-radius: 1.6pt; line-height: 1.2;
        }
        .kaki {
            background: {{ $marunGelap }}; color: #ffffff;
            font-size: 7.4pt; font-weight: bold;
            padding: 6pt; text-align: center; letter-spacing: 1pt;
            border-radius: 4pt; margin-top: 9pt;
        }
    </style>
</head>
<body>

    <table>
        <tr>
            <td style="width: 130pt; vertical-align: middle">
                @if ($logo)
                    <img src="{{ $logo }}" alt="DBENA" style="width: 112pt">
                @endif
            </td>
            <td style="vertical-align: middle; padding-left: 14pt">
                <div class="tajuk">{{ __('calendar_task.title') }}</div>
                <div class="sub">
                    {{ mb_strtoupper($cal['monthLabel']) }}
                    @if ($pic) &nbsp;|&nbsp; {{ $pic }} @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- ══ Statistik ══ --}}
    <table style="margin-top: 8pt">
        <tr>
            @foreach ([
                ['total', $stats['total']],
                ['in_progress', $stats['inProgress']],
                ['completed', $stats['completed']],
                ['pending', $stats['pending']],
                ['cancelled', $stats['cancelled']],
            ] as [$kunci, $nilai])
                <td style="width: 16.6%; padding-right: 6pt">
                    <table class="stat">
                        <tr><td class="stat-label">{{ __('calendar_task.stat.'.$kunci) }}</td></tr>
                        <tr><td class="stat-nombor">{{ $nilai }}</td></tr>
                    </table>
                </td>
            @endforeach
            <td style="width: 16.6%">
                <table class="stat">
                    <tr><td class="stat-label">{{ __('calendar_task.stat.rate') }}</td></tr>
                    <tr><td class="stat-nombor">{{ $stats['rate'] }}%</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ══ Grid kalendar ══ --}}
    <table class="grid">
        <tr>
            @foreach (__('calendar.days_full') as $h)
                <td class="kepala-hari" style="width: 14.28%">{{ $h }}</td>
            @endforeach
        </tr>

        @foreach ($cal['grid'] as $minggu)
            <tr>
                @foreach ($minggu as $sel)
                    {{-- Ketinggian tetap: baris yang mengecut mengikut
                         kandungan menghasilkan grid bergerigi yang tidak
                         lagi dibaca sebagai kalendar. --}}
                    <td style="height: 92pt; {{ $sel['isWeekend'] ? 'background: #FAF7FA;' : '' }}">
                        <div class="no-hari {{ $sel['inMonth'] ? '' : 'luar' }}">
                            <span class="{{ $sel['isToday'] ? 'hari-ini' : '' }}">{{ $sel['day'] }}</span>
                        </div>

                        @foreach ($sel['events']->take(4) as $acara)
                            <div class="acara"
                                 style="background: {{ $acara['mark']->color() }}; color: {{ $acara['mark']->textColor() }}">
                                @if ($acara['time']){{ $acara['time'] }} @endif{{ \Illuminate\Support\Str::limit($acara['title'], 34) }}
                            </div>
                        @endforeach

                        @if ($sel['events']->count() > 4)
                            <div style="font-size: 5.4pt; color: #7A7180; padding-left: 3pt">
                                +{{ $sel['events']->count() - 4 }}
                            </div>
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>

    <div class="kaki">
        {{ __('calendar_task.footer.plan') }} &nbsp;&nbsp;|&nbsp;&nbsp;
        {{ __('calendar_task.footer.work') }} &nbsp;&nbsp;|&nbsp;&nbsp;
        {{ __('calendar_task.footer.achieve') }}
    </div>
</body>
</html>
