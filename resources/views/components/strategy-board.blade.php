@props(['service', 'plan' => null, 'tiles' => null, 'rows' => null, 'mismatch' => []])

@php
    $tiles = collect($tiles ?? []);
    $rows = collect($rows ?? []);

    /*
     * Warna pil PIC dipilih daripada nama, bukan daripada kedudukan baris.
     *
     * Mengikut kedudukan bermakna Zikri bertukar warna sebaik sahaja satu
     * baris disisipkan di atasnya, dan seluruh gunanya — mengimbas turun
     * jadual dan melihat siapa memiliki apa — hilang. Nama yang sama
     * sentiasa mendapat warna yang sama, dalam semua servis.
     */
    $picColor = function (?string $name): string {
        $palet = [
            'oklch(0.48 0.16 255)',   // biru
            'oklch(0.48 0.18 25)',    // merah
            'oklch(0.55 0.16 60)',    // oren
            'oklch(0.42 0.13 300)',   // ungu
            'oklch(0.42 0.12 190)',   // teal
        ];

        return $palet[abs(crc32(mb_strtolower(trim((string) $name)))) % count($palet)];
    };

    $emas = 'oklch(0.82 0.14 85)';
    $marun = 'oklch(0.30 0.11 18)';
@endphp

<div class="dbena-card overflow-hidden">

    {{-- ══ Kepala ══ --}}
    <div class="px-5 py-4 text-center sm:px-6 sm:py-5"
         style="background: linear-gradient(135deg, oklch(0.24 0.10 18), oklch(0.34 0.13 20) 55%, oklch(0.22 0.08 18));
                border-bottom: 2px solid {{ $emas }}">
        <h2 class="text-[15px] font-extrabold leading-tight text-white sm:text-[19px]">
            {{ __('strategy.title') }}
            <span style="color: {{ $emas }}">— {{ mb_strtoupper($service->name) }}</span>
        </h2>
        <div class="mt-0.5 text-[11px] font-bold tracking-wide sm:text-[12px]" style="color: {{ $emas }}">
            {{ __('strategy.subtitle') }}
        </div>
    </div>

    @if ($rows->isEmpty())
        {{-- Keadaan kosong menerangkan SIAPA yang boleh membetulkannya.
             "Tiada data" menghantar pengguna mencari butang yang tidak
             wujud untuk mereka. --}}
        <div class="flex flex-col items-center gap-2 px-6 py-10 text-center">
            <i class="ph-duotone ph-clipboard-text text-[30px] text-t50" aria-hidden="true"></i>
            <div class="text-[13px] font-bold text-t80">{{ __('strategy.empty_title') }}</div>
            <p class="max-w-md text-[12px] leading-relaxed text-t55">
                {{ auth()->user()?->can('manage-strategy')
                    ? __('strategy.empty_body_admin')
                    : __('strategy.empty_body') }}
            </p>
        </div>
    @else
        <div class="flex flex-col gap-4 p-4 sm:p-5 lg:flex-row">

            {{-- ══ VISI ══ --}}
            @if (filled($plan?->vision))
                <div class="flex shrink-0 flex-col items-center gap-3 rounded-xl px-5 py-6 text-center lg:w-[230px]"
                     style="background: linear-gradient(180deg, {{ $marun }}, oklch(0.22 0.08 18));
                            border: 1px solid oklch(0.45 0.12 20)">
                    <i class="ph-duotone {{ $service->icon_class }} text-[34px]"
                       style="color: {{ $emas }}" aria-hidden="true"></i>

                    <div class="text-[15px] font-extrabold tracking-[0.18em] text-white">
                        {{ __('strategy.vision') }}
                    </div>

                    <div class="h-px w-10" style="background: {{ $emas }}"></div>

                    <p class="text-[12px] leading-relaxed text-white/85">
                        <span class="mr-0.5 text-[18px] font-black leading-none" style="color: {{ $emas }}">&ldquo;</span>{{ $plan->vision }}<span class="ml-0.5 text-[18px] font-black leading-none" style="color: {{ $emas }}">&rdquo;</span>
                    </p>
                </div>
            @endif

            <div class="flex min-w-0 flex-1 flex-col gap-4">

                {{-- ══ Petak ringkasan ══ --}}
                @if ($tiles->isNotEmpty())
                    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4 xl:grid-cols-8">
                        @foreach ($tiles as $tile)
                            <div class="flex flex-col items-center gap-1.5 rounded-xl px-2.5 py-3 text-center"
                                 style="background: var(--hover-bg2); border: 1px solid var(--border2)">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                                      style="background: {{ $marun }}">
                                    <i class="ph-duotone {{ $tile->icon ?? 'ph-target' }} text-[17px]"
                                       style="color: {{ $emas }}" aria-hidden="true"></i>
                                </span>

                                {{-- line-clamp-2: label datang daripada lajur KPI, yang
                                     ditulis sebagai ayat penuh. Membiarkannya membalut
                                     bebas menjadikan lapan petak berbeza tinggi. --}}
                                <div class="line-clamp-2 text-[9.5px] font-bold uppercase leading-tight tracking-wide text-t60">
                                    {{ $tile->position }}. {{ $tile->label }}
                                </div>

                                <div class="text-[15px] font-extrabold leading-none text-t94">{{ $tile->value }}</div>

                                @if (filled($tile->unit))
                                    <div class="text-[9.5px] leading-tight text-t50">{{ $tile->unit }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- ══ Jadual — desktop ══ --}}
                @php
                    $grid = 'grid-template-columns: 190px 170px 165px minmax(210px,1.2fr) minmax(210px,1.2fr) 105px 135px;';
                @endphp

                <div class="hidden overflow-x-auto lg:block">
                    <div class="min-w-[1180px] overflow-hidden rounded-xl" style="border: 1px solid var(--border2)">
                        <div class="grid gap-px px-0 py-0 text-[10.5px] font-extrabold uppercase tracking-wide text-white"
                             style="{{ $grid }} background: linear-gradient(180deg, oklch(0.30 0.07 250), oklch(0.24 0.06 250))">
                            @foreach (['kra', 'kpi', 'target', 'tactics', 'initiatives', 'timeline', 'pic'] as $c)
                                <div class="flex items-center gap-1.5 px-3 py-2.5">{{ __('strategy.col.'.$c) }}</div>
                            @endforeach
                        </div>

                        @foreach ($rows as $row)
                            <div class="grid gap-px text-[11.5px] leading-relaxed"
                                 style="{{ $grid }} background: {{ $loop->even ? 'var(--hover-bg3)' : 'transparent' }};
                                        border-top: 1px solid var(--border3)">
                                {{-- whitespace-pre-line: satu sel boleh membawa beberapa
                                     baris. Lead Management memegang 150 seminggu DAN 25
                                     sehari dalam sel Target yang bergabung. Tanpa ini
                                     kedua-duanya bercantum menjadi satu ayat. --}}
                                <div class="whitespace-pre-line px-3 py-2.5 font-bold text-t90">{{ $row->kra }}</div>
                                <div class="whitespace-pre-line px-3 py-2.5 text-t75">{{ $row->kpi ?? '—' }}</div>
                                <div class="whitespace-pre-line px-3 py-2.5 font-bold text-t90">{{ $row->target ?? '—' }}</div>
                                <div class="whitespace-pre-line px-3 py-2.5 text-t70">{{ $row->tactics ?? '—' }}</div>
                                <div class="whitespace-pre-line px-3 py-2.5 text-t70">{{ $row->initiatives ?? '—' }}</div>
                                <div class="whitespace-pre-line px-3 py-2.5 text-t70">{{ $row->timeline ?? '—' }}</div>
                                <div class="px-3 py-2.5">
                                    @if (filled($row->pic))
                                        <span class="inline-flex w-full items-center justify-center rounded-md px-2 py-1.5 text-[10.5px] font-extrabold text-white"
                                              style="background: {{ $picColor($row->pic) }}">
                                            {{ __('strategy.pic_prefix') }} : {{ $row->pic }}
                                        </span>
                                    @else
                                        <span class="text-t50">—</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- ══ Kad bertindan — telefon ══
                     Jadual tujuh lajur pada skrin 390px ialah tatal
                     mendatar melalui teks yang tidak boleh dibaca. Di sini
                     setiap KRA menjadi satu kad dengan label pada setiap
                     medan, supaya tiada lajur yang hilang konteksnya. --}}
                <div class="flex flex-col gap-2.5 lg:hidden">
                    @foreach ($rows as $row)
                        <div class="overflow-hidden rounded-xl" style="border: 1px solid var(--border2)">
                            <div class="flex flex-wrap items-center justify-between gap-2 px-3 py-2.5"
                                 style="background: linear-gradient(180deg, oklch(0.30 0.07 250), oklch(0.24 0.06 250))">
                                <span class="text-[12px] font-extrabold text-white">{{ $row->kra }}</span>

                                @if (filled($row->pic))
                                    <span class="rounded-md px-2 py-1 text-[10px] font-extrabold text-white"
                                          style="background: {{ $picColor($row->pic) }}">
                                        {{ __('strategy.pic_prefix') }} : {{ $row->pic }}
                                    </span>
                                @endif
                            </div>

                            <div class="flex flex-col gap-2 px-3 py-3">
                                @foreach ([
                                    'kpi' => $row->kpi,
                                    'target' => $row->target,
                                    'tactics' => $row->tactics,
                                    'initiatives' => $row->initiatives,
                                    'timeline' => $row->timeline,
                                ] as $key => $value)
                                    @if (filled($value))
                                        <div>
                                            <div class="text-[9.5px] font-extrabold uppercase tracking-wide text-t50">
                                                {{ __('strategy.col.'.$key) }}
                                            </div>
                                            <div class="mt-0.5 whitespace-pre-line text-[12px] leading-relaxed text-t80">{{ $value }}</div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- ══ Sasaran yang tidak sepadan ══
                     Disenaraikan di sini DAN ditandakan pada jadual Data
                     Kritikal. Penanda pada jadual memberitahu nombor mana
                     yang dipertikaikan; senarai ini memberitahu sheet mana
                     yang perlu disunting, kerana kedua-dua nombor dimiliki
                     oleh Google Sheet dan bukan oleh dashboard. --}}
                @if (count($mismatch) > 0)
                    <div class="overflow-hidden rounded-xl"
                         style="background: oklch(0.79 0.15 85/0.08); border: 1px solid oklch(0.79 0.15 85/0.4)">
                        <div class="flex gap-2.5 px-4 pb-2 pt-3">
                            <i class="ph-duotone ph-warning-diamond mt-px shrink-0 text-[17px]"
                               style="color: oklch(0.82 0.14 85)" aria-hidden="true"></i>
                            <div class="min-w-0">
                                <div class="text-[12.5px] font-extrabold" style="color: oklch(0.85 0.13 85)">
                                    {{ __('align.title', ['count' => count($mismatch)]) }}
                                </div>
                                <p class="mt-0.5 text-[11.5px] leading-relaxed text-t65">
                                    {{ auth()->user()?->can('manage-strategy')
                                        ? __('align.body_admin')
                                        : __('align.body') }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1 px-4 pb-3 pt-1">
                            @foreach ($mismatch as $m)
                                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5 text-[11.5px]">
                                    <span class="font-bold text-t85">{{ $m['label'] }}</span>
                                    <span class="text-t55">{{ __('align.critical') }}</span>
                                    <span class="font-bold" style="color: oklch(0.72 0.15 145)">{{ $m['criticalLabel'] }}</span>
                                    <span class="text-t45">·</span>
                                    <span class="text-t55">{{ __('align.plan') }}</span>
                                    <span class="font-bold" style="color: oklch(0.82 0.14 85)">{{ $m['plannedLabel'] }}</span>
                                    <span class="text-t45">({{ $m['planTargetText'] }})</span>
                                    @if (filled($m['planPic']))
                                        <span class="text-t50">— {{ $m['planPic'] }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Jalur kaki: menyatakan sheet ialah penulisnya. Tanpa
                     ini pengguna mencari butang edit yang sengaja tiada. --}}
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[10.5px] text-t50">
                    <span class="inline-flex items-center gap-1.5">
                        <i class="ph-duotone ph-lock-simple text-[13px]" aria-hidden="true"></i>
                        {{ __('strategy.view_only') }}
                    </span>

                    @if ($plan?->synced_at)
                        <span>· {{ __('strategy.synced_at', ['time' => $plan->synced_at->translatedFormat('d M Y, H:i')]) }}</span>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
