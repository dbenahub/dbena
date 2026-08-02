@props(['journey'])

@php
    $stages = $journey['stages'];
    $break = $journey['firstBreak'];

    $ring = fn (string $s) => match ($s) {
        'red' => 'oklch(0.63 0.22 25)',
        'amber' => 'oklch(0.79 0.15 85)',
        'green' => 'oklch(0.62 0.16 150)',
        default => 'oklch(0.55 0.02 260)',
    };

    /*
     * Jalan raya dilukis sebagai satu laluan SVG dengan liku, dan kad
     * diletak di atasnya pada kedudukan mutlak. Pin mesti duduk TEPAT di
     * atas laluan, jadi kedua-duanya dijana daripada senarai titik yang
     * sama di bawah — bukan dua set nombor yang perlu diselaraskan dengan
     * tangan setiap kali reka bentuk berubah.
     *
     * Puncak dan lurah berselang-seli supaya jalan berliku, dan kad
     * berselang atas/bawah mengikutnya.
     *
     * Ketinggian bekas mesti memuatkan kad PENUH di kedua-dua belah jalan,
     * bukan hanya jalan itu sendiri. Versi pertama memberi 112px di atas
     * jalan untuk kad setinggi ~210px, jadi peringkat di atas kehilangan
     * kepala berwarnanya — bahagian yang menamakannya.
     *
     * Belanjawan menegak:
     *   340  ruang di atas jalan   (kad ~210 + pin 54 + selang)
     *   100  amplitud liku
     *   320  ruang di bawah jalan  (kad ~210 + selang)
     *   ---
     *   760
     */
    $W = 1240;
    $H = 760;
    $atas = 340;    // paras y untuk puncak liku
    $bawah = 440;   // paras y untuk lurah liku

    /** Jarak menegak dari titik jalan ke tepi kad. */
    $selang = 66;

    $n = max(1, count($stages));
    $mula = 150;
    $jarak = $n > 1 ? (940 / ($n - 1)) : 0;

    $titik = [];
    foreach ($stages as $i => $_) {
        $titik[] = [
            'x' => $mula + $i * $jarak,
            'y' => $i % 2 === 0 ? $bawah : $atas,
        ];
    }

    // Bina laluan: mendatar dari tepi, kemudian kubik antara setiap titik.
    $d = 'M 30,'.$titik[0]['y'].' L '.($titik[0]['x'] - 70).','.$titik[0]['y'];
    foreach ($titik as $i => $t) {
        if ($i === 0) {
            $d .= ' L '.$t['x'].','.$t['y'];

            continue;
        }
        $s0 = $titik[$i - 1];
        $c = ($t['x'] - $s0['x']) * 0.5;
        $d .= ' C '.($s0['x'] + $c).','.$s0['y'].' '.($t['x'] - $c).','.$t['y'].' '.$t['x'].','.$t['y'];
    }
    $akhir = end($titik);
    $d .= ' L '.($W - 40).','.$akhir['y'];
@endphp

<div class="dbena-card overflow-hidden p-5 sm:p-6">

    {{-- ══ Tajuk ══ --}}
    <div class="mb-3.5 flex flex-wrap items-center gap-x-3 gap-y-1">
        <h2 class="text-base font-bold">{{ __('journey.title') }}</h2>
        <span class="text-[12px] text-t55">{{ __('journey.subtitle') }}</span>
    </div>

    {{-- ══ Arahan tindakan ══ --}}
    @if ($journey['healthy'])
        <div class="mb-4 flex gap-3 rounded-xl px-4 py-3"
             style="background: linear-gradient(180deg, oklch(0.62 0.16 150/0.14), oklch(0.62 0.16 150/0.06));
                    border: 1px solid oklch(0.62 0.16 150/0.35);
                    box-shadow: inset 0 1px 0 oklch(0.62 0.16 150/0.25)">
            <i class="ph-duotone ph-check-circle mt-px text-xl shrink-0"
               style="color: oklch(0.62 0.16 150)" aria-hidden="true"></i>
            <div>
                <div class="text-[13px] font-extrabold" style="color: oklch(0.66 0.16 150)">
                    {{ __('journey.healthy_title') }}
                </div>
                <p class="mt-0.5 text-[12px] leading-relaxed text-t70">{{ __('journey.healthy_body') }}</p>
            </div>
        </div>
    @else
        <div class="mb-4 overflow-hidden rounded-xl"
             style="background: linear-gradient(180deg, oklch(0.63 0.22 25/0.15), oklch(0.63 0.22 25/0.05));
                    border: 1px solid oklch(0.63 0.22 25/0.4);
                    box-shadow: 0 6px 18px -10px oklch(0.63 0.22 25/0.7), inset 0 1px 0 oklch(0.63 0.22 25/0.3)">

            @php
                /*
                 * Tiada rekod langsung dan rekod yang rendah menuntut soalan
                 * yang berbeza. "Kenapa tidak cukup?" dan "Kenapa tiada
                 * langsung?" bukan perbualan yang sama, dan menggabungkan
                 * kedua-duanya menghasilkan arahan yang tidak bermakna
                 * kepada kedua-dua pemilik.
                 */
                $hilang = ($break['breakReason'] ?? null) === 'missing';
                $adaPemilik = filled($break['owner']) && $break['owner'] !== '—';
                $seterusnya = $journey['nextStage'];
            @endphp

            <div class="flex gap-3 px-4 pb-3 pt-3.5">
                <i class="ph-duotone ph-warning-octagon mt-px text-xl shrink-0"
                   style="color: oklch(0.66 0.21 25)" aria-hidden="true"></i>
                <div class="min-w-0">
                    <div class="text-[13.5px] font-extrabold" style="color: oklch(0.7 0.2 25)">
                        {{ $hilang
                            ? __('journey.break_missing_title', ['stage' => $break['title']])
                            : __('journey.break_title', ['stage' => $break['title']]) }}
                    </div>
                    <p class="mt-1 text-[12px] leading-relaxed text-t75">
                        @if ($hilang && $seterusnya && $journey['blockedCount'] > 0)
                            {{ __('journey.break_body_missing', [
                                'stage' => $break['title'],
                                'next' => $seterusnya['title'],
                                'count' => $journey['blockedCount'],
                            ]) }}
                        @elseif ($hilang)
                            {{ __('journey.break_body_missing_single', ['stage' => $break['title']]) }}
                        @elseif ($journey['blockedCount'] > 0)
                            {{ __('journey.break_body', ['stage' => $break['title'], 'count' => $journey['blockedCount']]) }}
                        @else
                            {{ __('journey.break_body_single', ['stage' => $break['title']]) }}
                        @endif
                    </p>
                </div>
            </div>

            {{-- Jalur justifikasi — SATU nama, pemilik peringkat yang
                 terputus. Peringkat yang gagal tanpa nama ialah masalah
                 yang setiap orang harap orang lain uruskan. --}}
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 px-4 py-2.5"
                 style="background: oklch(0.72 0.17 60/0.12); border-top: 1px solid oklch(0.72 0.17 60/0.3)">
                <span class="flex items-center gap-1.5 rounded-md px-2 py-1 text-[10.5px] font-extrabold tracking-wide"
                      style="background: oklch(0.72 0.17 60); color: oklch(0.18 0.02 260)">
                    <i class="ph-duotone ph-flag-banner" aria-hidden="true"></i>
                    {{ $adaPemilik
                        ? __('journey.justify_owner', ['owner' => $break['owner']])
                        : __('journey.justify_owner_none', ['stage' => $break['title']]) }}
                </span>
                <span class="text-[12px] font-semibold text-t80">
                    @if (! $adaPemilik)
                        {{ __('journey.justify_missing_none', ['stage' => $break['title']]) }}
                    @elseif ($hilang)
                        {{ __('journey.justify_missing', ['owner' => $break['owner'], 'stage' => $break['title']]) }}
                    @else
                        {{ __('journey.justify_below', ['owner' => $break['owner'], 'stage' => $break['title']]) }}
                    @endif
                </span>
            </div>

            {{-- Jalur menunggu — pemilik hilir dinamakan supaya jelas mereka
                 BUKAN orang yang perlu menjawab. Tanpa jalur ini, empat kad
                 merah kelihatan seperti empat orang yang gagal, dan
                 mesyuarat menghabiskan masa pada tiga orang yang tersekat. --}}
            @if (count($journey['waiting']) > 0)
                <div class="flex flex-wrap items-start gap-x-3 gap-y-1.5 px-4 py-2.5"
                     style="background: var(--hover-bg3); border-top: 1px solid var(--border3)">
                    <span class="flex shrink-0 items-center gap-1.5 rounded-md px-2 py-1 text-[10.5px] font-extrabold tracking-wide"
                          style="background: var(--hover-bg2); border: 1px solid var(--border2); color: var(--t70)">
                        <i class="ph-duotone ph-pause-circle" aria-hidden="true"></i>
                        {{ __('journey.waiting_title', ['stage' => $break['title']]) }}
                    </span>

                    <span class="min-w-0 text-[12px] text-t70">
                        <span class="font-semibold text-t80">
                            @foreach ($journey['waiting'] as $w)
                                {{ filled($w['owner']) && $w['owner'] !== '—'
                                    ? __('journey.waiting_owner', ['owner' => $w['owner'], 'stage' => $w['title']])
                                    : __('journey.waiting_owner_none', ['stage' => $w['title']]) }}{{ ! $loop->last ? ' · ' : '' }}
                            @endforeach
                        </span>
                        <span class="text-t55">— {{ __('journey.waiting_note', ['stage' => $break['title']]) }}</span>
                    </span>
                </div>
            @endif
        </div>
    @endif

    {{-- ══ Senarai menegak — telefon ══
         Jalan raya 1240px pada skrin 390px ialah tatal mendatar melalui
         kanvas yang enam kali lebih lebar daripada tetingkap. Bentuk liku
         itu maksud peta pada desktop; pada telefon ia hanya menyembunyikan
         nombor. Di sini urutan ditunjukkan menegak sebagai ganti. --}}
    <div class="flex flex-col gap-0 md:hidden">
        @foreach ($stages as $i => $s)
            @php $c = $ring($s['status']); @endphp

            @if ($i > 0)
                <div class="ml-[17px] h-4 w-0.5"
                     style="background: repeating-linear-gradient(180deg, var(--border2) 0 4px, transparent 4px 8px)"></div>
            @endif

            <div class="flex gap-2.5">
                <div class="flex flex-col items-center">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-[13px] font-extrabold text-white"
                          style="background: {{ $s['accent'] }}; box-shadow: 0 3px 8px -3px {{ $s['accent'] }}">
                        {{ $s['no'] }}
                    </span>
                </div>

                <div class="min-w-0 flex-1 overflow-hidden rounded-xl"
                     style="background: var(--hover-bg3);
                            border: 1px solid color-mix(in oklch, {{ $c }} 45%, transparent)">
                    <div class="flex items-center gap-2 px-3 py-2"
                         style="background: color-mix(in oklch, {{ $s['accent'] }} 16%, transparent)">
                        <span class="truncate text-[11.5px] font-extrabold tracking-wide"
                              style="color: {{ $s['accent'] }}">{{ $s['title'] }}</span>
                        @if ($s['pct'] !== null)
                            <span class="ml-auto shrink-0 rounded px-1.5 py-0.5 text-[10.5px] font-extrabold"
                                  style="background: color-mix(in oklch, {{ $c }} 22%, transparent); color: {{ $c }}">
                                {{ $s['pctLabel'] }}
                            </span>
                        @endif
                    </div>

                    <div class="px-3 py-2.5">
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-[18px] font-extrabold leading-none" style="color: {{ $c }}">
                                {{ $s['actualLabel'] }}
                            </span>
                            <span class="text-[11px] text-t55">/ {{ $s['targetLabel'] }}</span>
                        </div>

                        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full" style="background: var(--border3)">
                            <div class="h-full rounded-full"
                                 style="width: {{ min(100, max(0, (float) ($s['pct'] ?? 0))) }}%; background: {{ $c }}"></div>
                        </div>

                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[10.5px] text-t55">
                            @if ($s['perWeekLabel'])
                                <span>{{ __('journey.target') }}: <b class="text-t75">{{ $s['perWeekLabel'] }}</b></span>
                            @endif
                            @if ($s['gapLabel'])
                                <span style="color: {{ $c }}"><b>{{ __('journey.gap') }} {{ $s['gapLabel'] }}</b></span>
                            @endif
                            @if (filled($s['owner']) && $s['owner'] !== '—')
                                <span class="ml-auto rounded px-1.5 py-0.5 text-[10px] font-bold text-t65"
                                      style="background: var(--card-bg); border: 1px solid var(--border3)">
                                    {{ $s['owner'] }}
                                </span>
                            @endif
                        </div>
                    </div>

                    @if ($s['broken'] && ! $s['blocked'])
                        <div class="px-3 py-2"
                             style="background: oklch(0.63 0.22 25/0.14); border-top: 1px solid oklch(0.63 0.22 25/0.35)">
                            <div class="text-[10.5px] font-extrabold" style="color: oklch(0.72 0.2 25)">
                                {{ $s['causeTitle'] }}
                            </div>
                            <p class="mt-0.5 text-[10px] leading-snug text-t65">{{ $s['cause'] }}</p>
                        </div>
                    @elseif ($s['blocked'])
                        <div class="px-3 py-2" style="background: var(--card-bg); border-top: 1px dashed var(--border2)">
                            <span class="text-[10.5px] font-bold text-t60">
                                {{ filled($s['blockedByOwner']) && $s['blockedByOwner'] !== '—'
                                    ? __('journey.blocked_by_owner', ['stage' => $s['blockedBy'], 'owner' => $s['blockedByOwner']])
                                    : __('journey.blocked_by', ['stage' => $s['blockedBy']]) }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- ══ Jalan raya — tablet ke atas ══ --}}
    <div class="-mx-1 hidden overflow-x-auto px-1 md:block">
        <div class="relative" style="width: {{ $W }}px; height: {{ $H }}px">

            {{-- ── Lapisan jalan ── --}}
            <svg viewBox="0 0 {{ $W }} {{ $H }}" width="{{ $W }}" height="{{ $H }}"
                 class="absolute inset-0" aria-hidden="true">
                <defs>
                    <linearGradient id="jalan-atas" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="oklch(0.42 0.01 260)"/>
                        <stop offset="55%" stop-color="oklch(0.33 0.01 260)"/>
                        <stop offset="100%" stop-color="oklch(0.26 0.01 260)"/>
                    </linearGradient>
                    <filter id="jalan-bayang" x="-20%" y="-20%" width="140%" height="160%">
                        <feDropShadow dx="0" dy="7" stdDeviation="7"
                                      flood-color="oklch(0.1 0.02 260)" flood-opacity="0.55"/>
                    </filter>
                    <filter id="pin-bayang" x="-60%" y="-60%" width="220%" height="220%">
                        <feDropShadow dx="0" dy="4" stdDeviation="4"
                                      flood-color="oklch(0.1 0.02 260)" flood-opacity="0.6"/>
                    </filter>
                </defs>

                {{-- Bayang di bawah jalan — memberi ketinggian --}}
                <path d="{{ $d }}" fill="none" stroke="oklch(0.12 0.02 260)" stroke-width="60"
                      stroke-linecap="round" opacity="0.5" transform="translate(0,9)"/>

                {{-- Bahu jalan --}}
                <path d="{{ $d }}" fill="none" stroke="oklch(0.5 0.03 250)" stroke-width="58"
                      stroke-linecap="round" filter="url(#jalan-bayang)"/>

                {{-- Permukaan turapan --}}
                <path d="{{ $d }}" fill="none" stroke="url(#jalan-atas)" stroke-width="50"
                      stroke-linecap="round"/>

                {{-- Kilauan tepi atas — sumber cahaya dari atas --}}
                <path d="{{ $d }}" fill="none" stroke="oklch(0.62 0.02 260)" stroke-width="1.5"
                      stroke-linecap="round" opacity="0.5" transform="translate(0,-24)"/>

                {{-- Garis tepi putih --}}
                <path d="{{ $d }}" fill="none" stroke="oklch(0.95 0 0)" stroke-width="2"
                      stroke-linecap="round" opacity="0.28" transform="translate(0,-19)"/>
                <path d="{{ $d }}" fill="none" stroke="oklch(0.95 0 0)" stroke-width="2"
                      stroke-linecap="round" opacity="0.28" transform="translate(0,19)"/>

                {{-- Garis tengah putus-putus --}}
                <path d="{{ $d }}" fill="none" stroke="oklch(0.92 0.02 95)" stroke-width="3.5"
                      stroke-linecap="round" stroke-dasharray="26 22" opacity="0.75"/>

                {{-- Pin peta pada setiap peringkat --}}
                @foreach ($stages as $i => $s)
                    @php $t = $titik[$i]; $c = $ring($s['status']); @endphp
                    <g filter="url(#pin-bayang)">
                        <path d="M {{ $t['x'] }},{{ $t['y'] + 4 }}
                                 c -13,-16 -20,-24 -20,-34
                                 a 20,20 0 1 1 40,0
                                 c 0,10 -7,18 -20,34 z"
                              fill="{{ $s['accent'] }}"
                              stroke="oklch(0.98 0 0)" stroke-width="2.5" stroke-opacity="0.9"/>
                        <circle cx="{{ $t['x'] }}" cy="{{ $t['y'] - 30 }}" r="12"
                                fill="oklch(0.99 0 0)" fill-opacity="0.94"/>
                        <text x="{{ $t['x'] }}" y="{{ $t['y'] - 25 }}" text-anchor="middle"
                              font-size="14" font-weight="800"
                              fill="{{ $s['accent'] }}" font-family="system-ui, sans-serif">{{ $s['no'] }}</text>
                    </g>

                    {{-- Tiang penyambung ke kad --}}
                    <line x1="{{ $t['x'] }}" y1="{{ $t['y'] + ($t['y'] === $bawah ? 26 : -56) }}"
                          x2="{{ $t['x'] }}" y2="{{ $t['y'] === $bawah ? $bawah + $selang : $atas - ($selang + 30) }}"
                          stroke="{{ $c }}" stroke-width="2" stroke-dasharray="4 4" opacity="0.6"/>
                @endforeach
            </svg>

            {{-- ── Papan tanda MULA ── --}}
            <div class="absolute -translate-y-1/2"
                 style="left: 14px; top: {{ $titik[0]['y'] }}px">
                <div class="rounded-lg px-3 py-2 text-[11px] font-extrabold tracking-wide text-t80"
                     style="background: linear-gradient(180deg, oklch(0.42 0.04 60), oklch(0.32 0.04 60));
                            border: 1px solid oklch(0.5 0.05 60);
                            box-shadow: 0 4px 10px -4px oklch(0.1 0.02 260), inset 0 1px 0 oklch(0.6 0.05 60)">
                    {{ __('journey.start') }}
                </div>
            </div>

            {{-- ── Bendera SASARAN ── --}}
            <div class="absolute -translate-y-1/2"
                 style="right: 8px; top: {{ $akhir['y'] }}px">
                <div class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-[11px] font-extrabold tracking-wide"
                     style="background: linear-gradient(180deg, oklch(0.78 0.17 60), oklch(0.66 0.17 55));
                            color: oklch(0.18 0.02 260);
                            box-shadow: 0 5px 14px -6px oklch(0.72 0.17 60), inset 0 1px 0 oklch(0.88 0.12 70)">
                    <i class="ph-duotone ph-flag-checkered" aria-hidden="true"></i>
                    {{ __('journey.goal') }}
                </div>
            </div>

            {{-- ── Kad peringkat ── --}}
            @foreach ($stages as $i => $s)
                @php
                    $t = $titik[$i];
                    $c = $ring($s['status']);
                    $bawahJalan = $t['y'] === $bawah;
                @endphp

                <div class="absolute"
                     style="left: {{ $t['x'] }}px; width: 232px; margin-left: -116px;
                            {{ $bawahJalan
                                ? 'top: '.($bawah + $selang).'px'
                                : 'bottom: '.($H - $atas + $selang + 30).'px' }}">

                    <div class="overflow-hidden rounded-xl"
                         style="background: linear-gradient(180deg, var(--card-bg), color-mix(in oklch, var(--card-bg) 88%, black));
                                border: 1px solid color-mix(in oklch, {{ $c }} 50%, transparent);
                                box-shadow: 0 10px 22px -12px oklch(0.08 0.02 260),
                                            inset 0 1px 0 color-mix(in oklch, {{ $c }} 28%, transparent)">

                        {{-- Kepala berwarna aksen --}}
                        <div class="flex items-center gap-2 px-3 py-2"
                             style="background: linear-gradient(180deg,
                                        color-mix(in oklch, {{ $s['accent'] }} 26%, transparent),
                                        color-mix(in oklch, {{ $s['accent'] }} 10%, transparent));
                                    border-bottom: 1px solid color-mix(in oklch, {{ $s['accent'] }} 30%, transparent)">
                            <span class="truncate text-[11px] font-extrabold tracking-wide"
                                  style="color: {{ $s['accent'] }}">{{ $s['title'] }}</span>
                            <i class="ph-duotone {{ $s['icon'] }} ml-auto shrink-0 text-[15px]"
                               style="color: {{ $s['accent'] }}" aria-hidden="true"></i>
                        </div>

                        <div class="px-3 py-2.5">
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-[20px] font-extrabold leading-none" style="color: {{ $c }}">
                                    {{ $s['actualLabel'] }}
                                </span>
                                <span class="text-[11px] text-t55">/ {{ $s['targetLabel'] }}</span>
                                @if ($s['pct'] !== null)
                                    <span class="ml-auto rounded px-1.5 py-0.5 text-[10.5px] font-extrabold"
                                          style="background: color-mix(in oklch, {{ $c }} 20%, transparent); color: {{ $c }}">
                                        {{ $s['pctLabel'] }}
                                    </span>
                                @endif
                            </div>

                            {{-- Bar kemajuan dengan kedalaman --}}
                            <div class="mt-2 h-2 w-full overflow-hidden rounded-full"
                                 style="background: var(--border3); box-shadow: inset 0 1px 2px oklch(0.1 0.02 260/0.5)">
                                <div class="h-full rounded-full"
                                     style="width: {{ min(100, max(0, (float) ($s['pct'] ?? 0))) }}%;
                                            background: linear-gradient(180deg,
                                                color-mix(in oklch, {{ $c }} 78%, white),
                                                {{ $c }});
                                            box-shadow: 0 0 8px color-mix(in oklch, {{ $c }} 55%, transparent)"></div>
                            </div>

                            @if ($s['perWeekLabel'])
                                <div class="mt-2 text-[10.5px] text-t55">
                                    {{ __('journey.target') }}: <b class="text-t75">{{ $s['perWeekLabel'] }}</b>
                                </div>
                            @endif

                            @if ($s['amountLabel'])
                                <div class="mt-0.5 truncate text-[10.5px] text-t55">
                                    {{ $s['amountTitle'] }}: <b class="text-t75">{{ $s['amountLabel'] }}</b>
                                    <span class="text-t50">/ {{ $s['amountTargetLabel'] }}</span>
                                </div>
                            @endif

                            <div class="mt-1.5 flex items-center gap-2">
                                @if ($s['gapLabel'])
                                    <span class="text-[10.5px] font-bold" style="color: {{ $c }}">
                                        {{ __('journey.gap') }} {{ $s['gapLabel'] }}
                                    </span>
                                @endif
                                @if (filled($s['owner']) && $s['owner'] !== '—')
                                    <span class="ml-auto truncate rounded px-1.5 py-0.5 text-[10px] font-bold text-t65"
                                          style="background: var(--hover-bg3); border: 1px solid var(--border3)">
                                        {{ $s['owner'] }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Jalur punca / tersekat, di dalam kad supaya
                             ketinggian kekal boleh diramal. --}}
                        @if ($s['broken'] && ! $s['blocked'])
                            <div class="px-3 py-2"
                                 style="background: oklch(0.63 0.22 25/0.14); border-top: 1px solid oklch(0.63 0.22 25/0.35)">
                                <div class="flex items-start gap-1.5">
                                    <i class="ph-duotone ph-x-circle mt-px shrink-0 text-[13px]"
                                       style="color: oklch(0.68 0.21 25)" aria-hidden="true"></i>
                                    <div class="min-w-0">
                                        <div class="text-[10.5px] font-extrabold leading-tight"
                                             style="color: oklch(0.72 0.2 25)">{{ $s['causeTitle'] }}</div>
                                        <p class="mt-0.5 text-[10px] leading-snug text-t65">{{ $s['cause'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @elseif ($s['blocked'])
                            <div class="px-3 py-2"
                                 style="background: var(--hover-bg3); border-top: 1px dashed var(--border2)">
                                <div class="flex items-center gap-1.5">
                                    <i class="ph-duotone ph-link-break shrink-0 text-[13px] text-t50" aria-hidden="true"></i>
                                    <span class="truncate text-[10.5px] font-bold text-t60">
                                        {{ filled($s['blockedByOwner']) && $s['blockedByOwner'] !== '—'
                                    ? __('journey.blocked_by_owner', ['stage' => $s['blockedBy'], 'owner' => $s['blockedByOwner']])
                                    : __('journey.blocked_by', ['stage' => $s['blockedBy']]) }}
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ══ Petunjuk ══ --}}
    <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-[10.5px] text-t55">
        @foreach ([
            ['legend_ok', 'oklch(0.62 0.16 150)'],
            ['legend_warn', 'oklch(0.79 0.15 85)'],
            ['legend_break', 'oklch(0.63 0.22 25)'],
        ] as [$kunci, $warna])
            <span class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full"
                      style="background: {{ $warna }}; box-shadow: 0 0 6px {{ $warna }}"></span>
                {{ __('journey.'.$kunci) }}
            </span>
        @endforeach
        <span class="flex items-center gap-1.5">
            <span class="h-2 w-2 rounded-full border border-dashed" style="border-color: var(--t50)"></span>
            {{ __('journey.legend_blocked') }}
        </span>
    </div>
</div>
