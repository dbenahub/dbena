@php
    /*
     * Sengaja TIADA pernyataan "use" di sini — lihat tools/check-blade-use.py.
     *
     * DomPDF: tiada flexbox, tiada CSS grid, tiada <svg> sebaris, tiada
     * transform. Setiap kedudukan mutlak dan dikira dahulu dalam
     * OrgChartPdfLayout.
     */
    $marun = '#6B1F47';
    $marunGelap = '#4A1236';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('org.title') }} — DBENA SDN BHD</title>
    <style>
        /* A3 landskap. Saiz halaman TETAP, dan carta diskalakan untuk muat.
           Versi pertama menetapkan halaman kepada saiz kanvas, yang
           menghasilkan PDF bersaiz pelik yang setiap pencetak tafsir
           berbeza — sesetengahnya memotong tepi tanpa amaran. */
        @page { margin: 0; }

        body {
            margin: 0;
            padding: 0;
            font-family: "DejaVu Sans", sans-serif;
            background: #ffffff;
            color: #1A1420;
        }

        .bingkai {
            position: absolute;
            left: 14pt; top: 14pt;
            width: 1162pt; height: 813pt;
            border: 1.6pt solid {{ $marun }};
            border-radius: 10pt;
        }

        .kepala-garis {
            position: absolute;
            left: 40pt; top: 96pt;
            width: 1110pt; height: 0.7pt;
            background: #DED6DE;
        }

        .tajuk {
            position: absolute;
            left: 190pt; top: 34pt;
            font-size: 20pt; font-weight: bold;
            letter-spacing: 1.6pt;
            color: #241826;
        }
        .subtajuk {
            position: absolute;
            left: 192pt; top: 60pt;
            font-size: 9pt; color: #6E6473;
            letter-spacing: 0.6pt;
        }
        .garis-tajuk {
            position: absolute;
            left: 192pt; top: 79pt;
            width: 200pt; height: 2.6pt;
            background: {{ $marun }};
        }

        .meta {
            position: absolute;
            right: 40pt; top: 36pt;
            width: 300pt;
            text-align: right;
        }
        .meta-tajuk { font-size: 8.5pt; font-weight: bold; color: #3A2C3E; letter-spacing: 0.8pt; }
        .meta-baris { font-size: 7.5pt; color: #776E7C; margin-top: 3pt; }

        .kaki {
            position: absolute;
            left: 40pt; bottom: 26pt;
            font-size: 7.5pt; color: #8A8090;
        }
        .kaki-kanan {
            position: absolute;
            right: 40pt; bottom: 26pt;
            font-size: 7.5pt; color: #8A8090;
        }
        .kaki-garis {
            position: absolute;
            left: 40pt; bottom: 42pt;
            width: 1110pt; height: 0.7pt;
            background: #DED6DE;
        }
        .kaki-blok {
            position: absolute;
            right: 14pt; bottom: 14pt;
            width: 200pt; height: 7pt;
            background: {{ $marun }};
            border-radius: 0 0 9pt 0;
        }

        .kotak { position: absolute; overflow: hidden; }
        .isi   { display: block; text-align: center; }
        .lencana { position: absolute; border-radius: 50%; }
        .garis { position: absolute; }
    </style>
</head>
<body>

    <div class="bingkai"></div>

    {{-- ══ Kepala ══ --}}
    @if ($logo)
        {{-- Logo dibenamkan sebagai data-URI, bukan laluan fail. DomPDF
             menolak laluan di luar chroot dan gagal SENYAP: imej hilang,
             tiada ralat, dan PDF kelihatan hampir betul. --}}
        <img src="{{ $logo }}" alt="DBENA"
             style="position: absolute; left: 40pt; top: 30pt; width: 128pt;">
    @endif

    <div class="tajuk">{{ __('org.pdf.title') }}</div>
    <div class="subtajuk">{{ __('org.pdf.subtitle') }}</div>
    <div class="garis-tajuk"></div>

    <div class="meta">
        <div class="meta-tajuk">{{ __('org.pdf.governance') }}</div>
        <div class="meta-baris">{{ __('org.pdf.registration', ['no' => '1518035-A']) }}</div>
        <div class="meta-baris">{{ __('org.pdf.effective', ['date' => $effective]) }}</div>
    </div>

    <div class="kepala-garis"></div>

    {{-- ══ Garisan penyambung ══
         Dilukis SEBELUM kotak supaya hujungnya tersembunyi di belakang
         kotak dan bukan terkeluar di atasnya. --}}
    @foreach ($layout['segments'] as $seg)
        <div class="garis"
             style="left: {{ $seg['left'] }}pt; top: {{ $seg['top'] }}pt;
                    width: {{ $seg['width'] }}pt; height: {{ $seg['height'] }}pt;
                    background: {{ $seg['dashed'] ? '#A79BAB' : $marun }}"></div>
    @endforeach

    {{-- ══ Kotak ══ --}}
    @foreach ($layout['boxes'] as $box)
        <div class="kotak"
             style="left: {{ $box['left'] }}pt; top: {{ $box['top'] }}pt;
                    width: {{ $box['width'] }}pt; height: {{ $box['height'] }}pt;
                    background: {{ $box['background'] }};
                    border: 0.7pt solid {{ $box['border'] }};
                    border-radius: {{ $box['radius'] }}pt;">

            <div class="isi" style="padding-top: {{ $box['padTop'] }}pt">
                @if (filled($box['title']))
                    <span style="display: block; font-size: {{ $box['titleSize'] }}pt;
                                 font-weight: bold; color: {{ $box['titleColor'] }};
                                 line-height: 1.18">{{ $box['title'] }}</span>
                @endif

                @if (filled($box['subtitle']))
                    <span style="display: block; font-size: {{ $box['subtitleSize'] }}pt;
                                 color: {{ $box['subtitleColor'] }};
                                 line-height: 1.18">{{ $box['subtitle'] }}</span>
                @endif

                @if (filled($box['name']))
                    <span style="display: block; font-size: {{ $box['nameSize'] }}pt;
                                 font-weight: bold; color: {{ $box['nameColor'] }};
                                 line-height: 1.24">{{ $box['name'] }}</span>
                @endif
            </div>
        </div>

        {{-- Lencana dilukis SELEPAS kotak dan di luar aliran kotak: ia
             menonjol melepasi tepi atas, dan overflow:hidden pada kotak
             akan memotongnya separuh. --}}
        @if ($box['hasBadge'])
            <div class="lencana"
                 style="left: {{ round($box['left'] + $box['width'] / 2 - $box['badgeSize'] / 2, 2) }}pt;
                        top: {{ round($box['top'] - $box['badgeSize'] / 2, 2) }}pt;
                        width: {{ $box['badgeSize'] }}pt; height: {{ $box['badgeSize'] }}pt;
                        background: {{ $box['badge'] }};
                        border: 1pt solid {{ $box['border'] }};"></div>
        @endif
    @endforeach

    {{-- ══ Kaki ══ --}}
    <div class="kaki-garis"></div>
    <div class="kaki">{{ __('org.pdf.footer_left') }}</div>
    <div class="kaki-kanan">{{ __('org.pdf.footer_right') }}</div>
    <div class="kaki-blok"></div>
</body>
</html>
