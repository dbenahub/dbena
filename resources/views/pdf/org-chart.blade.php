<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('org.title') }} — DBENA SDN BHD</title>
    <style>
        /*
         * Warna PDF ditulis sebagai hex, bukan token tema.
         *
         * DomPDF tidak menyelesaikan pembolehubah CSS atau oklch(). Token
         * tema akan menjadi hitam pekat pada setiap elemen, dan carta itu
         * dicetak sebagai kotak hitam di atas putih.
         */
        @page { margin: 0; }
        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            background: #ffffff;
            color: #1a1420;
        }
        .kepala {
            padding: 14px 20px 10px;
            border-bottom: 2px solid #8e2a5f;
        }
        .tajuk {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 3px;
            color: #6b1f47;
            text-transform: uppercase;
        }
        .sub { font-size: 9px; color: #6b6570; margin-top: 2px; }
        .kanvas { position: relative; }
        .kotak {
            position: absolute;
            border-radius: 7px;
            padding: 6px 8px;
            overflow: hidden;
        }
        .eksekutif { background: #5d1c40; border: 1px solid #8e2a5f; }
        .jabatan   { background: #f6f2f6; border: 1px solid #d3c7d2; }
        .sokongan  { background: #ffffff; border: 1px dashed #b9adba; }
        .jawatan { font-size: 7.5px; line-height: 1.25; }
        .nama { font-size: 8px; font-weight: bold; line-height: 1.25; }
        .sub-baris { font-size: 6.5px; line-height: 1.2; }
        .terang .sub-baris { color: #e6d5e2; }
        .gelap .sub-baris { color: #857b8c; }
        .terang .jawatan, .terang .nama { color: #ffffff; }
        .gelap .jawatan { color: #5c5364; }
        .gelap .nama { color: #1a1420; }
    </style>
</head>
<body>
    <div class="kepala">
        <div class="tajuk">{{ __('org.title') }}</div>
        <div class="sub">DBENA SDN BHD (1518035-A) · {{ now()->translatedFormat('d F Y') }}</div>
    </div>

    <div class="kanvas" style="width: {{ $canvasWidth }}px; height: {{ $canvasHeight }}px">
        @php
            $byId = $nodes->keyBy('id');
        @endphp

        <svg width="{{ $canvasWidth }}" height="{{ $canvasHeight }}"
             style="position: absolute; left: 0; top: 0">
            @foreach ($links as $link)
                @php
                    $a = $byId->get($link->from_node_id);
                    $b = $byId->get($link->to_node_id);
                @endphp
                @continue (! $a || ! $b)

                @php
                    $x1 = $a->centerX(); $y1 = $a->bottomY();
                    $x2 = $b->centerX(); $y2 = $b->y;
                    $mid = $y2 > $y1 ? $y1 + (int) round(($y2 - $y1) / 2) : $y1 + 20;
                    $putus = $link->style->dashArray() !== null;
                @endphp

                <path d="M {{ $x1 }},{{ $y1 }} L {{ $x1 }},{{ $mid }} L {{ $x2 }},{{ $mid }} L {{ $x2 }},{{ $y2 }}"
                      fill="none" stroke="{{ $putus ? '#9b93a3' : '#8e2a5f' }}"
                      stroke-width="{{ $putus ? 1 : 1.4 }}"
                      @if ($putus) stroke-dasharray="4 3" @endif></path>
            @endforeach
        </svg>

        @foreach ($nodes as $node)
            @php
                /*
                 * Warna per-kotak dihormati dalam cetakan juga.
                 *
                 * PDF yang mengabaikan warna pilihan bermakna carta di
                 * skrin dan carta yang diedarkan ialah dua dokumen
                 * berbeza, dan yang diedarkan itulah yang orang simpan.
                 */
                $hex = \App\Support\OrgPalette::clean($node->color);

                $kelas = $hex !== null ? 'kotak' : match ($node->style->value) {
                    'executive' => 'kotak eksekutif terang',
                    'support' => 'kotak sokongan gelap',
                    default => 'kotak jabatan gelap',
                };

                $gayaWarna = $hex === null ? '' : sprintf(
                    'background: %s; border: 1px solid %s; color: %s;',
                    $hex,
                    \App\Support\OrgPalette::borderOn($hex),
                    \App\Support\OrgPalette::textOn($hex),
                );
            @endphp

            <div class="{{ $kelas }}"
                 style="left: {{ $node->x }}px; top: {{ $node->y }}px;
                        width: {{ $node->width - 18 }}px; height: {{ $node->boxHeight() - 14 }}px;
                        {{ $gayaWarna }}">
                @if (filled($node->title))
                    <div class="jawatan" @if ($hex) style="color: inherit" @endif>{{ $node->title }}</div>
                @endif
                @if (filled($node->subtitle))
                    <div class="sub-baris"
                         @if ($hex) style="color: {{ \App\Support\OrgPalette::mutedTextOn($hex) }}" @endif>{{ $node->subtitle }}</div>
                @endif
                @if (filled($node->name))
                    <div class="nama" @if ($hex) style="color: inherit" @endif>{{ $node->name }}</div>
                @endif
            </div>
        @endforeach
    </div>
</body>
</html>
