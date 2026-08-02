@props(['nodes', 'links', 'editable' => false, 'selectedId' => null, 'selectedIds' => [], 'connectFrom' => null])

@php
    $nodes = collect($nodes);
    $links = collect($links);

    /*
     * Saiz kanvas dikira daripada kotak yang PALING JAUH, bukan ditetapkan.
     *
     * Kanvas tetap bermakna kotak yang diseret melepasi tepinya menjadi
     * tidak boleh dicapai — kelihatan seolah-olah ia telah dipadam. Kanvas
     * yang berkembang mengikut kandungan tidak boleh kehilangan apa-apa.
     */
    $lebar = max(1200, (int) $nodes->max(fn ($n) => $n->x + $n->width) + 80);
    $tinggi = max(560, (int) $nodes->max(fn ($n) => $n->bottomY()) + 90);

    $byId = $nodes->keyBy('id');
@endphp

<div class="dbena-card overflow-hidden">

    {{-- ══ Kepala ══ --}}
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-4 sm:px-6"
         style="background: linear-gradient(120deg, oklch(0.22 0.09 340), oklch(0.32 0.13 335) 60%, oklch(0.20 0.07 330));
                border-bottom: 2px solid oklch(0.72 0.16 340)">
        <div class="min-w-0 flex-1">
            <h2 class="text-[16px] font-extrabold uppercase leading-tight tracking-[0.18em] text-white sm:text-[19px]">
                {{ __('org.title') }}
            </h2>
            <div class="mt-0.5 text-[11.5px] text-white/70">{{ __('org.subtitle') }}</div>
        </div>

        {{ $actions ?? '' }}
    </div>

    @if ($nodes->isEmpty())
        <div class="flex flex-col items-center gap-2 px-6 py-12 text-center">
            <i class="ph-duotone ph-tree-structure text-[30px] text-t50" aria-hidden="true"></i>
            <div class="text-[13px] font-bold text-t80">{{ __('org.empty') }}</div>
            @can('manage-org-chart')
                <p class="text-[12px] text-t60">{{ __('org.empty_admin') }}</p>
            @endcan
        </div>
    @else
        {{-- Kanvas menatal mendatar. Carta organisasi memang lebih lebar
             daripada skrin; memaksanya muat bermakna teks mengecil sehingga
             tidak boleh dibaca. --}}
        <div class="overflow-auto p-4 sm:p-5" style="max-height: 78vh">
            <div class="relative"
                 style="width: {{ $lebar }}px; height: {{ $tinggi }}px"
                 @if ($editable)
                     x-data="cartaOrganisasi()"
                     x-on:pointermove.window="gerak($event)"
                     x-on:pointerup.window="lepas($event)"
                 @endif>

                {{-- ══ Garisan ══
                     Dilukis DI BAWAH kotak supaya hujung garisan tersembunyi
                     di belakang kotak dan bukan terkeluar di atasnya. --}}
                <svg class="pointer-events-none absolute inset-0" width="{{ $lebar }}" height="{{ $tinggi }}"
                     aria-hidden="true">
                    @foreach ($links as $link)
                        @php
                            $a = $byId->get($link->from_node_id);
                            $b = $byId->get($link->to_node_id);
                        @endphp
                        @continue (! $a || ! $b)

                        @php
                            $x1 = $a->centerX();
                            $y1 = $a->bottomY();
                            $x2 = $b->centerX();
                            $y2 = $b->y;

                            /*
                             * Laluan siku, bukan garisan lurus pepenjuru.
                             * Carta organisasi dibaca sebagai hierarki, dan
                             * pepenjuru mencadangkan hubungan sisi.
                             *
                             * Apabila kotak sasaran berada DI ATAS puncanya
                             * (freelancer yang digantung ke tepi), siku
                             * dilukis dari tepi dan bukan dari bawah.
                             */
                            $tengah = $y2 > $y1 ? $y1 + (int) round(($y2 - $y1) / 2) : $y1 + 20;
                            $d = "M {$x1},{$y1} L {$x1},{$tengah} L {$x2},{$tengah} L {$x2},{$y2}";
                        @endphp

                        <path d="{{ $d }}" fill="none"
                              stroke="{{ $link->style->dashArray() ? 'var(--t50)' : 'oklch(0.55 0.12 335)' }}"
                              stroke-width="{{ $link->style->dashArray() ? 1.5 : 2 }}"
                              @if ($link->style->dashArray())
                                  stroke-dasharray="{{ $link->style->dashArray() }}"
                              @endif
                              stroke-linecap="round" stroke-linejoin="round"></path>

                        <circle cx="{{ $x1 }}" cy="{{ $y1 }}" r="3"
                                fill="{{ $link->style->dashArray() ? 'var(--t50)' : 'oklch(0.62 0.14 335)' }}"></circle>
                    @endforeach
                </svg>

                {{-- ══ Kotak ══ --}}
                @foreach ($nodes as $node)
                    @php
                        // Warna per-kotak jika ditetapkan, kalau tidak warna
                        // gaya. Satu tempat memutuskan; paparan hanya
                        // menggunakan apa yang diberi.
                        $warna = $node->palette();
                        $dipilih = in_array($node->id, (array) $selectedIds, true);
                        $utama = $selectedId === $node->id;
                        $sumber = $connectFrom === $node->id;
                    @endphp

                    {{-- Satu atribut class sahaja. Dua atribut class pada
                         elemen yang sama bermakna pelayar mengabaikan yang
                         kedua secara senyap — kursor seret hilang tanpa
                         sebarang ralat untuk dikesan. --}}
                    <div @class([
                            'absolute select-none rounded-md px-2.5 py-2',
                            'cursor-grab active:cursor-grabbing' => $editable,
                         ])
                         style="left: {{ $node->x }}px; top: {{ $node->y }}px;
                                width: {{ $node->width }}px; height: {{ $node->boxHeight() }}px;
                                background: {{ $warna['background'] }};
                                border: {{ $sumber
                                    ? '2px solid oklch(0.82 0.15 85)'
                                    : ($utama
                                        ? '2px solid oklch(0.82 0.16 340)'
                                        : ($dipilih ? '2px solid oklch(0.62 0.14 200)' : $warna['border'])) }};
                                box-shadow: 0 4px 14px -8px oklch(0.1 0 0 / 0.8);
                                touch-action: none"
                         @if ($editable)
                             wire:key="node-{{ $node->id }}"
                             data-node="{{ $node->id }}"
                             {{-- Alpine membaca pilihan daripada DOM dan bukan
                                  daripada keadaannya sendiri: Livewire memapar
                                  semula kanvas selepas setiap pilihan, dan
                                  salinan Alpine akan menjadi lapuk. --}}
                             @if ($dipilih) data-selected="1" @endif
                             x-on:pointerdown="mula($event)"
                         @endif>

                        {{-- Lencana hanya apabila kotak MEMPUNYAI ikon.
                             Ia duduk di atas tepi kotak, jadi memaksa lencana
                             pada setiap kotak akan menolak semua teks ke bawah
                             16px dan memusnahkan penjajaran baris yang diukur
                             dengan teliti dalam seeder. --}}
                        @if (filled($node->icon))
                            <span class="absolute left-1/2 flex h-8 w-8 -translate-x-1/2 items-center justify-center rounded-full"
                                  style="top: -16px; background: {{ $warna['badge'] }};
                                         border: 2px solid {{ $warna['badgeRing'] }}">
                                <i class="ph-duotone {{ $node->icon }} text-[16px]"
                                   style="color: {{ $warna['badgeIcon'] }}" aria-hidden="true"></i>
                            </span>
                        @endif

                        <div @class([
                                'flex h-full flex-col items-center justify-center gap-px text-center',
                                'pt-2' => filled($node->icon),
                             ])>
                            @if (filled($node->title))
                                <span class="line-clamp-2 w-full text-[11px] font-bold leading-tight"
                                      style="color: {{ $warna['title'] }}">{{ $node->title }}</span>
                            @endif

                            {{-- "Head of Dept." — baris tengah yang lebih kecil
                                 dan lebih pudar. Memampatkannya ke dalam tajuk
                                 menghasilkan satu baris panjang yang membalut
                                 dengan hodoh dan kehilangan hierarki yang
                                 menjadikan carta boleh diimbas. --}}
                            @if (filled($node->subtitle))
                                <span class="w-full truncate text-[9.5px] leading-tight"
                                      style="color: {{ $warna['subtitle'] }}">{{ $node->subtitle }}</span>
                            @endif

                            @if (filled($node->name))
                                <span class="line-clamp-2 w-full text-[10.5px] font-extrabold leading-tight"
                                      style="color: {{ $warna['name'] }}">{{ $node->name }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @unless ($editable)
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 px-5 pb-4 text-[11px] text-t60 sm:px-6">
                <span class="inline-flex items-center gap-1.5">
                    <i class="ph-duotone ph-lock-simple text-[13px]" aria-hidden="true"></i>
                    {{ __('org.view_only') }}
                </span>
            </div>
        @endunless
    @endif
</div>
