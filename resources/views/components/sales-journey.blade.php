@props(['journey'])

@php
    $stages = $journey['stages'];
    $break = $journey['firstBreak'];

    // Warna status. Aksen peringkat kekal walau apa pun status supaya
    // peta boleh dikenali sekali pandang; cincin status yang berubah.
    $ring = fn (string $s) => match ($s) {
        'red' => 'oklch(0.62 0.22 25)',
        'amber' => 'oklch(0.78 0.15 85)',
        'green' => 'oklch(0.6 0.16 150)',
        default => 'var(--border2)',
    };
@endphp

<div class="dbena-card overflow-hidden p-5 sm:p-6">

    {{-- ══ Tajuk ══ --}}
    <div class="mb-1 flex flex-wrap items-center gap-x-3 gap-y-1">
        <h2 class="text-base font-bold">{{ __('journey.title') }}</h2>
        <span class="text-[12px] text-t55">{{ __('journey.subtitle') }}</span>
    </div>

    {{-- ══ Kesimpulan — perkara pertama yang dibaca ══ --}}
    @if ($journey['healthy'])
        <div class="mb-5 mt-3.5 flex gap-3 rounded-xl px-4 py-3"
             style="background: oklch(0.6 0.16 150/0.09); border: 1px solid oklch(0.6 0.16 150/0.28)">
            <i class="ph-duotone ph-check-circle mt-px text-lg shrink-0"
               style="color: oklch(0.6 0.16 150)" aria-hidden="true"></i>
            <div>
                <div class="text-[13px] font-bold" style="color: oklch(0.62 0.16 150)">
                    {{ __('journey.healthy_title') }}
                </div>
                <p class="mt-0.5 text-[12px] leading-relaxed text-t70">{{ __('journey.healthy_body') }}</p>
            </div>
        </div>
    @else
        <div class="mb-5 mt-3.5 rounded-xl px-4 py-3.5"
             style="background: oklch(0.62 0.22 25/0.09); border: 1px solid oklch(0.62 0.22 25/0.3)">
            <div class="flex gap-3">
                <i class="ph-duotone ph-warning-octagon mt-px text-lg shrink-0"
                   style="color: oklch(0.65 0.21 25)" aria-hidden="true"></i>
                <div class="min-w-0">
                    <div class="text-[13px] font-bold" style="color: oklch(0.68 0.2 25)">
                        {{ __('journey.break_title', ['stage' => $break['title']]) }}
                    </div>
                    <p class="mt-1 text-[12px] leading-relaxed text-t75">
                        {{ $journey['blockedCount'] > 0
                            ? __('journey.break_body', ['stage' => $break['title'], 'count' => $journey['blockedCount']])
                            : __('journey.break_body_single', ['stage' => $break['title']]) }}
                    </p>
                    <p class="mt-2 text-[12px] font-semibold leading-relaxed" style="color: oklch(0.72 0.17 60)">
                        <i class="ph-duotone ph-flag-banner" aria-hidden="true"></i>
                        {{ __('journey.break_action', ['stage' => $break['title']]) }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- ══ Jalan raya ══
         Menatal mendatar pada skrin kecil dan bukan dilipat menjadi
         senarai — urutan itulah maksud peta ini. --}}
    <div class="-mx-1 overflow-x-auto px-1 pb-1">
        <div class="flex min-w-[900px] items-stretch gap-0">

            {{-- Papan tanda MULA --}}
            <div class="flex shrink-0 flex-col items-center justify-center pr-1">
                <div class="rounded-lg px-2.5 py-1.5 text-[10.5px] font-extrabold tracking-wide text-t70"
                     style="background: var(--hover-bg3); border: 1px solid var(--border2)">
                    {{ __('journey.start') }}
                </div>
            </div>

            @foreach ($stages as $i => $s)
                @php $c = $ring($s['status']); @endphp

                {{-- Penyambung jalan --}}
                <div class="flex shrink-0 items-center" style="width: 34px">
                    <div class="h-[3px] w-full rounded-full"
                         @style([
                             'background: repeating-linear-gradient(90deg, var(--border2) 0 6px, transparent 6px 11px)',
                             'background: repeating-linear-gradient(90deg, color-mix(in oklch, '.$ring('red').' 55%, transparent) 0 6px, transparent 6px 11px)' => $s['blocked'] || $s['broken'],
                         ])></div>
                </div>

                {{-- Kad peringkat --}}
                <div class="flex min-w-0 flex-1 flex-col">

                    <div class="relative flex h-full flex-col rounded-xl p-3.5"
                         style="background: var(--hover-bg3);
                                border: 1px solid color-mix(in oklch, {{ $c }} 45%, transparent)">

                        {{-- Nombor + tajuk --}}
                        <div class="mb-2.5 flex items-center gap-2">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-extrabold text-white"
                                  style="background: {{ $s['accent'] }}">{{ $s['no'] }}</span>
                            <span class="truncate text-[11.5px] font-extrabold tracking-wide"
                                  style="color: {{ $s['accent'] }}">{{ $s['title'] }}</span>
                            <i class="ph-duotone {{ $s['icon'] }} ml-auto text-base shrink-0"
                               style="color: {{ $s['accent'] }}" aria-hidden="true"></i>
                        </div>

                        {{-- Sebenar / sasaran --}}
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-[19px] font-extrabold leading-none" style="color: {{ $c }}">
                                {{ $s['actualLabel'] }}
                            </span>
                            <span class="text-[11.5px] text-t55">/ {{ $s['targetLabel'] }}</span>
                            @if ($s['pct'] !== null)
                                <span class="ml-auto rounded px-1.5 py-0.5 text-[10.5px] font-bold"
                                      style="background: color-mix(in oklch, {{ $c }} 16%, transparent); color: {{ $c }}">
                                    {{ $s['pctLabel'] }}
                                </span>
                            @endif
                        </div>

                        {{-- Bar kemajuan --}}
                        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full" style="background: var(--border3)">
                            <div class="h-full rounded-full"
                                 style="width: {{ min(100, max(0, (float) ($s['pct'] ?? 0))) }}%; background: {{ $c }}"></div>
                        </div>

                        {{-- Rentak mingguan --}}
                        @if ($s['perWeekLabel'])
                            <div class="mt-2 text-[10.5px] text-t55">
                                {{ __('journey.target') }}: <b class="text-t70">{{ $s['perWeekLabel'] }}</b>
                            </div>
                        @endif

                        {{-- Nilai RM berkaitan --}}
                        @if ($s['amountLabel'])
                            <div class="mt-1 truncate text-[10.5px] text-t55" title="{{ $s['amountTitle'] }}">
                                {{ $s['amountTitle'] }}:
                                <b class="text-t70">{{ $s['amountLabel'] }}</b>
                                <span class="text-t50">/ {{ $s['amountTargetLabel'] }}</span>
                            </div>
                        @endif

                        {{-- Jurang --}}
                        @if ($s['gapLabel'])
                            <div class="mt-1 text-[10.5px] font-semibold" style="color: {{ $c }}">
                                {{ __('journey.gap') }} {{ $s['gapLabel'] }}
                            </div>
                        @endif
                    </div>

                    {{-- Kotak punca — hanya di bawah peringkat yang benar-benar
                         terputus, bukan di bawah gejala hiliran. --}}
                    @if ($s['broken'] && ! $s['blocked'])
                        <div class="mt-2 flex flex-col items-center">
                            <div class="h-3 w-px"
                                 style="background: color-mix(in oklch, {{ $ring('red') }} 45%, transparent)"></div>
                            <div class="w-full rounded-lg px-3 py-2.5"
                                 style="background: oklch(0.62 0.22 25/0.08); border: 1px solid oklch(0.62 0.22 25/0.3)">
                                <div class="mb-1 flex items-start gap-1.5">
                                    <i class="ph-fill ph-x-circle mt-px shrink-0 text-[13px]"
                                       style="color: oklch(0.65 0.21 25)" aria-hidden="true"></i>
                                    <span class="text-[11px] font-extrabold leading-tight"
                                          style="color: oklch(0.68 0.2 25)">{{ $s['causeTitle'] }}</span>
                                </div>
                                <p class="text-[10.5px] leading-snug text-t70">{{ $s['cause'] }}</p>
                            </div>
                        </div>
                    @elseif ($s['blocked'])
                        <div class="mt-2 flex flex-col items-center">
                            <div class="h-3 w-px" style="background: var(--border2)"></div>
                            <div class="w-full rounded-lg px-3 py-2.5"
                                 style="background: var(--hover-bg3); border: 1px dashed var(--border2)">
                                <div class="mb-1 flex items-start gap-1.5">
                                    <i class="ph-duotone ph-link-break mt-px shrink-0 text-[13px] text-t55" aria-hidden="true"></i>
                                    <span class="text-[11px] font-bold leading-tight text-t65">
                                        {{ __('journey.blocked_by', ['stage' => $s['blockedBy']]) }}
                                    </span>
                                </div>
                                <p class="text-[10.5px] leading-snug text-t50">
                                    {{ __('journey.blocked_note', ['stage' => $s['blockedBy']]) }}
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach

            {{-- Penyambung akhir + bendera SASARAN --}}
            <div class="flex shrink-0 items-center" style="width: 34px">
                <div class="h-[3px] w-full rounded-full"
                     style="background: repeating-linear-gradient(90deg, var(--border2) 0 6px, transparent 6px 11px)"></div>
            </div>
            <div class="flex shrink-0 flex-col items-center justify-center pl-1">
                <div class="rounded-lg px-2.5 py-1.5 text-[10.5px] font-extrabold tracking-wide"
                     style="background: oklch(0.72 0.17 60/0.15); border: 1px solid oklch(0.72 0.17 60/0.45); color: oklch(0.75 0.16 60)">
                    {{ __('journey.goal') }}
                </div>
            </div>
        </div>
    </div>

    {{-- ══ Petunjuk ══ --}}
    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-[10.5px] text-t55">
        @foreach ([
            ['legend_ok', 'oklch(0.6 0.16 150)'],
            ['legend_warn', 'oklch(0.78 0.15 85)'],
            ['legend_break', 'oklch(0.62 0.22 25)'],
        ] as [$kunci, $warna])
            <span class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full" style="background: {{ $warna }}"></span>
                {{ __('journey.'.$kunci) }}
            </span>
        @endforeach
        <span class="flex items-center gap-1.5">
            <span class="h-2 w-2 rounded-full border border-dashed" style="border-color: var(--t50)"></span>
            {{ __('journey.legend_blocked') }}
        </span>
    </div>
</div>
