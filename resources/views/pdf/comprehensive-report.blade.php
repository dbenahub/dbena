@php
    /* Sengaja TIADA "use". DomPDF: tiada flexbox, tiada <svg> sebaris,
       tiada transform. Carta dilukis dengan <div> berkedudukan mutlak,
       susun atur dengan jadual. */
    $marun = '#5C1240';
    $marunGelap = '#3D0F2B';
    $emas = '#B8860B';
    $rm = fn ($v) => 'RM'.number_format((float) $v);
    $s = $data['summary'];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('report.title') }} — {{ $data['scope']['label'] }}</title>
    <style>
        /* Kepala dan kaki diulang pada SETIAP halaman melalui position:
           fixed, yang DomPDF tafsir sebagai "pada setiap halaman". Laporan
           lapan halaman yang hanya berlogo pada muka depan tidak boleh
           dikenali apabila satu halaman difotokopi berasingan. */
        @page { margin: 96pt 46pt 68pt 46pt; }

        body { margin: 0; font-family: "DejaVu Sans", sans-serif; font-size: 8.4pt; color: #1A1420; line-height: 1.45; }

        .kepala-tetap { position: fixed; top: -74pt; left: 0; right: 0; height: 60pt; }
        .kaki-tetap { position: fixed; bottom: -50pt; left: 0; right: 0; height: 40pt; }

        table { border-collapse: collapse; width: 100%; }
        td, th { padding: 0; vertical-align: top; }

        .kepala-garis { height: 2pt; background: {{ $marun }}; }
        .kepala-tajuk { font-size: 8.6pt; font-weight: bold; color: {{ $marunGelap }}; letter-spacing: 0.6pt; }
        .kepala-sub { font-size: 6.8pt; color: #7A7180; }

        .kaki-garis { height: 0.7pt; background: #DED6DE; margin-bottom: 4pt; }
        .kaki-teks { font-size: 6.6pt; color: #8A8090; }

        h2.seksyen {
            font-size: 10.4pt; font-weight: bold; color: #ffffff;
            background: {{ $marunGelap }};
            padding: 5pt 9pt; margin: 0 0 8pt 0;
            letter-spacing: 0.8pt;
        }
        h3.sub { font-size: 8.8pt; font-weight: bold; color: {{ $marunGelap }}; margin: 10pt 0 4pt; }

        .kad { border: 0.8pt solid #D8CEDA; border-radius: 3pt; padding: 7pt 9pt; }
        .kad-label { font-size: 6.6pt; font-weight: bold; color: #7A7180; letter-spacing: 0.4pt; }
        .kad-nilai { font-size: 15pt; font-weight: bold; color: {{ $marunGelap }}; }
        .kad-nota { font-size: 6.4pt; color: #8A8090; }

        .jadual th {
            background: {{ $marunGelap }}; color: #ffffff;
            font-size: 7pt; font-weight: bold; text-align: left;
            padding: 4pt 6pt; letter-spacing: 0.3pt;
        }
        .jadual td { border-bottom: 0.5pt solid #E2DAE3; padding: 4pt 6pt; font-size: 7.6pt; }
        .jadual tr.selang td { background: #FAF7FA; }
        .kanan { text-align: right; }
        .tengah { text-align: center; }

        .pil {
            display: inline-block; font-size: 6.4pt; font-weight: bold;
            padding: 1.4pt 4pt; border-radius: 6pt; color: #ffffff;
        }

        .naratif {
            border-left: 3pt solid {{ $marun }};
            background: #FAF7FA;
            padding: 7pt 10pt; font-size: 8.2pt;
        }

        .bar-luar { background: #EFE9EF; height: 7pt; border-radius: 3.5pt; }
        .bar-dalam { height: 7pt; border-radius: 3.5pt; }

        .nota { font-size: 7pt; color: #6E6473; font-style: italic; }
        .kosong { font-size: 7.6pt; color: #8A8090; padding: 6pt 0; }
        .putus { page-break-before: always; }
    </style>
</head>
<body>

    {{-- ══ Kepala berulang ══ --}}
    <div class="kepala-tetap">
        <table>
            <tr>
                <td style="width: 96pt; vertical-align: middle">
                    @if ($logo)
                        <img src="{{ $logo }}" alt="DBENA" style="width: 86pt">
                    @endif
                </td>
                <td style="vertical-align: middle; padding-left: 10pt">
                    <div class="kepala-tajuk">{{ __('report.title') }}</div>
                    <div class="kepala-sub">{{ __('report.company') }} &nbsp;|&nbsp; {{ $data['scope']['label'] }}</div>
                </td>
                <td style="vertical-align: middle; text-align: right">
                    <div class="kepala-sub">{{ __('report.confidential') }}</div>
                </td>
            </tr>
        </table>
        <div class="kepala-garis" style="margin-top: 6pt"></div>
    </div>

    {{-- ══ Kaki berulang ══ --}}
    <div class="kaki-tetap">
        <div class="kaki-garis"></div>
        <table>
            <tr>
                <td class="kaki-teks">
                    {{ __('report.generated', [
                        'date' => $data['generatedAt']->translatedFormat('j F Y, H:i'),
                        'by' => $data['generatedBy'],
                    ]) }}
                </td>
                <td class="kaki-teks" style="text-align: right">{{ __('report.reg') }}</td>
            </tr>
        </table>
    </div>

    {{-- ══════════ 1. RINGKASAN EKSEKUTIF ══════════ --}}
    <h2 class="seksyen">1. {{ __('report.section.summary') }}</h2>

    <table style="margin-bottom: 9pt">
        <tr>
            @foreach ([
                [__('report.summary.actual'), $rm($s['actual']), null],
                [__('report.summary.target'), $rm($s['target']), null],
                [__('report.summary.achievement'), number_format($s['pct'], 1).'%', $s['status']['label']],
                [__('report.summary.gap'), $rm($s['gap']), null],
            ] as $i => [$label, $nilai, $nota])
                <td style="width: 25%; padding-right: {{ $i < 3 ? '6pt' : '0' }}">
                    <table class="kad">
                        <tr><td class="kad-label">{{ $label }}</td></tr>
                        <tr><td class="kad-nilai">{{ $nilai }}</td></tr>
                        @if ($nota)
                            <tr><td class="kad-nota" style="color: {{ $s['status']['color'] }}">{{ $nota }}</td></tr>
                        @endif
                    </table>
                </td>
            @endforeach
        </tr>
    </table>

    {{-- Naratif dalam ayat, bukan hanya nombor. Laporan yang menyenaraikan
         nombor tanpa memberitahu maksudnya memindahkan kerja analisis
         kepada pembaca — dan pembaca ialah orang yang meminta laporan itu
         kerana dia tiada masa untuk analisis. --}}
    <div class="naratif">
        {{ __('report.summary.narrative_'.$s['status']['key'], [
            'pct' => number_format($s['pct'], 1).'%',
            'gap' => number_format($s['gap']),
        ]) }}
    </div>

    {{-- ══════════ 2. PERBANDINGAN ══════════ --}}
    <h2 class="seksyen" style="margin-top: 14pt">2. {{ __('report.section.comparison') }}</h2>

    @if ($s['change'] === null)
        <div class="kosong">{{ __('report.summary.no_previous') }}</div>
    @else
        <table>
            <tr>
                <td style="width: 46%">
                    <table class="kad">
                        <tr><td class="kad-label">{{ __('report.summary.vs_previous', ['period' => $data['previous']['label']]) }}</td></tr>
                        <tr>
                            <td class="kad-nilai" style="color: {{ $s['change'] >= 0 ? '#1E8449' : '#C0392B' }}">
                                {{ $s['change'] >= 0 ? '+' : '' }}{{ number_format($s['change'], 1) }}%
                            </td>
                        </tr>
                        <tr>
                            <td class="kad-nota">
                                {{ $s['changeAmount'] >= 0 ? '+' : '−' }}{{ $rm(abs($s['changeAmount'])) }}
                                &nbsp;·&nbsp; {{ $data['previous']['label'] }}: {{ $rm($data['previous']['actual']) }}
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width: 8pt"></td>
                <td>
                    <table class="kad">
                        <tr><td class="kad-label">{{ __('report.summary.verdict') }}</td></tr>
                        <tr>
                            <td style="padding-top: 3pt">
                                <span class="pil" style="background: {{ $s['status']['color'] }}">{{ $s['status']['label'] }}</span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    @endif

    {{-- ══════════ 3. TREND ══════════ --}}
    <h2 class="seksyen" style="margin-top: 14pt">3. {{ __('report.section.trend') }}</h2>

    @php $puncak = max(1, $data['trend']['peak']); @endphp

    <table>
        <tr>
            @foreach ($data['trend']['series'] as $titik)
                @php
                    $tinggiActual = (int) round($titik['actual'] / $puncak * 96);
                    $tinggiTarget = (int) round($titik['target'] / $puncak * 96);
                @endphp
                <td style="width: 8.33%; vertical-align: bottom; text-align: center">
                    {{-- Bar dilukis sebagai div bertinggi tetap. DomPDF
                         tidak memaparkan <svg> sebaris, jadi carta yang
                         dilukis begitu keluar sebagai halaman kosong. --}}
                    <div style="height: 100pt; position: relative">
                        <div style="position: absolute; bottom: 0; left: 22%; width: 30%;
                                    height: {{ max(1, $tinggiActual) }}pt; background: {{ $marun }}"></div>
                        <div style="position: absolute; bottom: 0; right: 22%; width: 30%;
                                    height: {{ max(1, $tinggiTarget) }}pt; background: #D9C89A"></div>
                    </div>
                    <div style="font-size: 6.2pt; color: #6E6473; padding-top: 2pt">{{ $titik['label'] }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    <div class="nota" style="margin-top: 4pt">
        <span style="display: inline-block; width: 7pt; height: 7pt; background: {{ $marun }}"></span>
        {{ __('report.legend_actual') }} &nbsp;&nbsp;
        <span style="display: inline-block; width: 7pt; height: 7pt; background: #D9C89A"></span>
        {{ __('report.legend_target') }}
    </div>

    {{-- ══════════ 4. PECAHAN SERVIS ══════════ --}}
    <div class="putus"></div>
    <h2 class="seksyen">4. {{ __('report.section.breakdown') }}</h2>

    <table class="jadual">
        <tr>
            <th>{{ __('report.col.service') }}</th>
            <th class="kanan">{{ __('report.col.actual') }}</th>
            <th class="kanan">{{ __('report.col.target') }}</th>
            <th class="kanan">{{ __('report.col.pct') }}</th>
            <th class="kanan">{{ __('report.col.gap') }}</th>
            <th style="width: 108pt">{{ __('report.col.status') }}</th>
        </tr>

        @foreach ($data['breakdown'] as $baris)
            <tr class="{{ $loop->even ? 'selang' : '' }}">
                <td style="font-weight: bold">{{ $baris['service']->name }}</td>
                <td class="kanan">{{ $rm($baris['actual']) }}</td>
                <td class="kanan">{{ $rm($baris['target']) }}</td>
                <td class="kanan" style="font-weight: bold">{{ number_format($baris['pct'], 1) }}%</td>
                <td class="kanan">{{ $rm($baris['gap']) }}</td>
                <td>
                    <div class="bar-luar">
                        <div class="bar-dalam"
                             style="width: {{ min(100, max(2, (int) round($baris['pct']))) }}%;
                                    background: {{ $baris['status']['color'] }}"></div>
                    </div>
                    <div style="font-size: 6.2pt; color: {{ $baris['status']['color'] }}; padding-top: 1.6pt">
                        {{ $baris['status']['label'] }}
                    </div>
                </td>
            </tr>
        @endforeach
    </table>

    {{-- ══════════ 5. CORONG ══════════ --}}
    <h2 class="seksyen" style="margin-top: 14pt">5. {{ __('report.section.funnel') }}</h2>

    @if (empty($data['funnel']))
        <div class="kosong">{{ __('report.none') }}</div>
    @else
        <table class="jadual">
            <tr>
                <th>{{ __('report.col.stage') }}</th>
                <th class="kanan">{{ __('report.col.actual') }}</th>
                <th class="kanan">{{ __('report.col.target') }}</th>
                <th class="kanan">{{ __('report.col.pct') }}</th>
                <th style="width: 130pt">{{ __('report.col.status') }}</th>
            </tr>

            @foreach ($data['funnel'] as $peringkat)
                <tr class="{{ $loop->even ? 'selang' : '' }}">
                    <td style="font-weight: bold">{{ $peringkat['title'] }}</td>
                    <td class="kanan">{{ number_format($peringkat['actual']) }}</td>
                    <td class="kanan">{{ number_format($peringkat['target']) }}</td>
                    <td class="kanan" style="font-weight: bold">{{ number_format($peringkat['pct'], 0) }}%</td>
                    <td>
                        <div class="bar-luar">
                            <div class="bar-dalam"
                                 style="width: {{ min(100, max(2, (int) round($peringkat['pct']))) }}%;
                                        background: {{ $peringkat['status']['color'] }}"></div>
                        </div>
                    </td>
                </tr>
            @endforeach
        </table>

        <div class="nota" style="margin-top: 5pt">{{ __('report.funnel_note') }}</div>
    @endif

    {{-- ══════════ 6. PUNCA ══════════ --}}
    <div class="putus"></div>
    <h2 class="seksyen">6. {{ __('report.section.causes') }}</h2>

    @if (empty($data['causes']))
        <div class="kosong">{{ __('report.none') }}</div>
    @else
        <table class="jadual">
            <tr>
                <th style="width: 74pt">{{ __('report.col.service') }}</th>
                <th style="width: 84pt">{{ __('report.col.stage') }}</th>
                <th style="width: 62pt">{{ __('report.col.owner') }}</th>
                <th>{{ __('report.col.reason') }}</th>
                <th style="width: 120pt">{{ __('report.col.effect') }}</th>
            </tr>

            @foreach ($data['causes'] as $punca)
                <tr class="{{ $loop->even ? 'selang' : '' }}">
                    <td style="font-weight: bold">{{ $punca['service'] }}</td>
                    <td>{{ $punca['stage'] }}</td>
                    <td>{{ $punca['owner'] }}</td>
                    <td>{{ $punca['reason'] }}</td>
                    <td style="font-size: 7pt; color: #6E6473">
                        {{ $punca['effect'] }}
                        @if ($punca['blocked'])
                            <br><span style="font-size: 6.4pt">{{ $punca['blocked'] }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

    {{-- ══════════ 7. PEMILIK ══════════ --}}
    <h2 class="seksyen" style="margin-top: 14pt">7. {{ __('report.section.owners') }}</h2>

    @if (empty($data['owners']))
        <div class="kosong">{{ __('report.none') }}</div>
    @else
        <table class="jadual">
            <tr>
                <th>{{ __('report.col.owner') }}</th>
                <th>{{ __('report.col.service') }}</th>
                <th class="tengah">{{ __('report.col.metrics') }}</th>
                <th class="tengah">{{ __('report.col.red') }}</th>
                <th class="tengah">{{ __('report.col.amber') }}</th>
                <th class="tengah">{{ __('report.col.green') }}</th>
                <th class="kanan">{{ __('report.col.score') }}</th>
            </tr>

            @foreach ($data['owners'] as $pemilik)
                <tr class="{{ $loop->even ? 'selang' : '' }}">
                    <td style="font-weight: bold">{{ $pemilik['name'] }}</td>
                    <td style="font-size: 7pt; color: #6E6473">{{ $pemilik['services'] }}</td>
                    <td class="tengah">{{ $pemilik['total'] }}</td>
                    <td class="tengah" style="color: #C0392B; font-weight: bold">{{ $pemilik['red'] }}</td>
                    <td class="tengah" style="color: #C98A12">{{ $pemilik['amber'] }}</td>
                    <td class="tengah" style="color: #1E8449">{{ $pemilik['green'] }}</td>
                    <td class="kanan" style="font-weight: bold">{{ $pemilik['score'] }}%</td>
                </tr>
            @endforeach
        </table>
    @endif

    {{-- ══════════ 8. TINDAKAN ══════════ --}}
    <div class="putus"></div>
    <h2 class="seksyen">8. {{ __('report.section.actions') }}</h2>

    @if (empty($data['actions']))
        <div class="kosong">{{ __('report.none') }}</div>
    @else
        <table class="jadual">
            <tr>
                <th style="width: 26pt">#</th>
                <th style="width: 58pt">{{ __('report.col.urgency') }}</th>
                <th style="width: 70pt">{{ __('report.col.service') }}</th>
                <th style="width: 58pt">{{ __('report.col.owner') }}</th>
                <th>{{ __('report.col.what') }}</th>
                <th style="width: 140pt">{{ __('report.col.why') }}</th>
                <th style="width: 50pt">{{ __('report.col.when') }}</th>
            </tr>

            @foreach ($data['actions'] as $tindakan)
                <tr class="{{ $loop->even ? 'selang' : '' }}">
                    <td class="tengah" style="font-weight: bold">{{ $loop->iteration }}</td>
                    <td>
                        <span class="pil"
                              style="background: {{ $tindakan['priority'] <= 2 ? '#C0392B' : '#C98A12' }}">
                            {{ $tindakan['urgency'] }}
                        </span>
                    </td>
                    <td>{{ $tindakan['service'] }}</td>
                    <td>{{ $tindakan['owner'] }}</td>
                    <td style="font-weight: bold">{{ $tindakan['what'] }}</td>
                    <td style="font-size: 7pt; color: #6E6473">{{ $tindakan['why'] }}</td>
                    <td style="font-size: 7pt">{{ $tindakan['when'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif
</body>
</html>
