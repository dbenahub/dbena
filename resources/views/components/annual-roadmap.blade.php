@props(['roadmap', 'yearNav' => true])

@php
    $plan = $roadmap['plan'];
    $rows = $roadmap['rows'];
    $events = $roadmap['events'];
    $bulan = __('calendar.months_short');

    $ungu = 'oklch(0.35 0.13 330)';
    $unguTua = 'oklch(0.26 0.11 330)';

    $ringkasan = collect($plan->summary ?? [])->filter()->values();

    $duit = fn (float $v) => 'RM'.number_format($v);
@endphp

<div class="dbena-card overflow-hidden"
     x-data="{
        paparan: 'grid',
        bulanTerbuka: null,
        buka(m) { this.bulanTerbuka = this.bulanTerbuka === m ? null : m },
     }">

    {{-- ══ Kepala ══ --}}
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-4 sm:px-6"
         style="background: linear-gradient(120deg, {{ $unguTua }}, {{ $ungu }} 60%, oklch(0.22 0.09 330));
                border-bottom: 2px solid oklch(0.72 0.16 330)">
        <div class="min-w-0 flex-1">
            <h2 class="text-[16px] font-extrabold leading-tight text-white sm:text-[21px]">
                {{ $plan->title ?: __('roadmap.title') }}
            </h2>
            <div class="mt-0.5 text-[11.5px] italic text-white/75 sm:text-[12.5px]">
                {{ $plan->subtitle ?: __('roadmap.subtitle') }}
            </div>
        </div>

        <div class="text-right">
            <div class="text-[10.5px] font-extrabold tracking-wide text-white/85">{{ __('roadmap.journey') }}</div>
            <div class="text-[10px] italic text-white/60">{{ __('roadmap.journey_note') }}</div>
        </div>
    </div>

    {{-- ══ Kawalan ══
         Tahun dan sudut paparan tersedia untuk KEDUA-DUA peranan. Melihat
         data dari sudut lain bukan suntingan — mengunci pengguna kepada
         satu susun atur bermakna mereka mengeksport ke Excel untuk
         menjawab soalan yang sepatutnya dijawab di sini. --}}
    <div class="flex flex-wrap items-center gap-2 px-4 py-3 sm:px-5"
         style="background: var(--hover-bg3); border-bottom: 1px solid var(--border3)">

        <div class="flex items-center gap-1 rounded-[10px] p-1" style="background: var(--hover-bg2)">
            @foreach (['grid' => 'ph-grid-four', 'quarter' => 'ph-squares-four', 'list' => 'ph-list-bullets'] as $mod => $ikon)
                <button type="button" x-on:click="paparan = '{{ $mod }}'"
                        class="flex items-center gap-1.5 rounded-[7px] px-2.5 py-1.5 text-[11.5px] font-bold transition-colors"
                        :class="paparan === '{{ $mod }}' ? 'text-t94' : 'text-t60'"
                        :style="paparan === '{{ $mod }}' ? 'background: {{ $ungu }}' : ''">
                    <i class="ph-duotone {{ $ikon }} text-sm" aria-hidden="true"></i>
                    <span class="hidden sm:inline">{{ __('roadmap.view.'.$mod) }}</span>
                </button>
            @endforeach
        </div>

        {{-- Nav tahun dimatikan apabila papan ini dibenamkan dalam editor:
             wire:click diselesaikan terhadap komponen HOS, dan editor tiada
             showRoadmapYear. Butang yang meletup apabila diklik lebih teruk
             daripada butang yang tiada. --}}
        @if ($yearNav)
            <div class="flex items-center gap-1.5">
                <button type="button" wire:click="showRoadmapYear({{ $roadmap['year'] - 1 }})"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-t75"
                        style="border: 1px solid var(--border2)"
                        aria-label="{{ $roadmap['year'] - 1 }}">
                    <i class="ph-duotone ph-caret-left text-sm" aria-hidden="true"></i>
                </button>

                <span class="min-w-[52px] text-center text-[13px] font-extrabold text-t94">{{ $roadmap['year'] }}</span>

                <button type="button" wire:click="showRoadmapYear({{ $roadmap['year'] + 1 }})"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-t75"
                        style="border: 1px solid var(--border2)"
                        aria-label="{{ $roadmap['year'] + 1 }}">
                    <i class="ph-duotone ph-caret-right text-sm" aria-hidden="true"></i>
                </button>
            </div>
        @else
            <span class="text-[13px] font-extrabold text-t94">{{ $roadmap['year'] }}</span>
        @endif

        <span class="ml-auto flex flex-wrap items-center gap-x-3 gap-y-1 text-[11.5px]">
            <span class="text-t60">{{ __('roadmap.target.company') }}</span>
            <b class="text-[13px] text-t94">{{ $duit((float) $roadmap['annualTarget']) }}</b>

            @if ($roadmap['eventCount'] > 0)
                <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-[11px] font-semibold"
                      style="background: oklch(0.62 0.19 255/0.16); color: oklch(0.72 0.15 255)">
                    <i class="ph-duotone ph-calendar-dots text-[13px]" aria-hidden="true"></i>
                    {{ __('roadmap.calendar.events', ['count' => $roadmap['eventCount']]) }}
                </span>
            @endif
        </span>
    </div>

    @if ($roadmap['calendarError'])
        {{-- Kalendar yang gagal tidak menjatuhkan roadmap; ia hanya
             melaporkan dirinya. Grid hidup dalam pangkalan data kita. --}}
        <div class="px-4 py-2 text-[11.5px] sm:px-5"
             style="background: oklch(0.63 0.22 25/0.1); color: oklch(0.75 0.16 25)">
            <i class="ph-duotone ph-warning-circle text-sm" aria-hidden="true"></i>
            {{ __('roadmap.calendar.failed', ['message' => $roadmap['calendarError']]) }}
        </div>
    @endif

    <div class="p-4 sm:p-5">

        {{-- ══════════ GRID TAHUNAN ══════════ --}}
        <div x-show="paparan === 'grid'" x-cloak>
            <div class="overflow-x-auto">
                <div class="min-w-[1000px]">

                    {{-- Tajuk bulan --}}
                    <div class="grid gap-1.5" style="grid-template-columns: 170px repeat(12, 1fr)">
                        <div class="flex items-center rounded-lg px-3 py-2 text-[11px] font-extrabold tracking-wide text-white"
                             style="background: {{ $unguTua }}">{{ __('roadmap.service_col') }}</div>

                        @foreach ($roadmap['months'] as $m)
                            <button type="button" x-on:click="buka({{ $m }})"
                                    class="relative rounded-lg px-1 py-2 text-[11px] font-extrabold text-white transition-opacity hover:opacity-85"
                                    style="background: {{ $ungu }}">
                                {{ $bulan[$m - 1] }}

                                {{-- Titik acara. Grid kekal bersih; klik
                                     membuka senarai bulan itu dengan tarikh
                                     penuh. --}}
                                @if (! empty($events[$m]))
                                    <span class="absolute right-1 top-1 h-1.5 w-1.5 rounded-full"
                                          style="background: oklch(0.85 0.16 90)"
                                          title="{{ __('roadmap.calendar.events', ['count' => count($events[$m])]) }}"></span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    {{-- Baris servis --}}
                    @foreach ($rows as $row)
                        @php $s = $row['service']; @endphp

                        <div class="mt-1.5 grid gap-1.5" style="grid-template-columns: 170px repeat(12, 1fr)">
                            <div class="flex items-center gap-2 rounded-lg px-3 py-2.5"
                                 style="background: var(--hover-bg2); border: 1px solid var(--border3)">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md"
                                      style="background: {{ $s->chart_color }}">
                                    <i class="ph-duotone {{ $s->icon_class }} text-[15px] text-white" aria-hidden="true"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate text-[12.5px] font-bold text-t94">
                                        {{ $loop->iteration }}. {{ $s->name }}
                                    </span>
                                    <span class="block text-[10px] text-t60">
                                        {{ $duit((float) $row['annualTarget']) }} · {{ __('roadmap.target.active_months', ['count' => $row['activeMonths']]) }}
                                    </span>
                                </span>
                            </div>

                            @if ($row['allYear'])
                                {{-- Satu bar, bukan dua belas petak serupa. Bar
                                     menyampaikan satu keputusan; dua belas petak
                                     menjemput mata mencari perbezaan yang tiada. --}}
                                <div class="flex items-center justify-center gap-2 rounded-lg px-3 py-2.5"
                                     style="grid-column: span 12; background: {{ \App\Enums\RoadmapStatus::ActiveAllYear->color() }}">
                                    <i class="ph-duotone ph-check-circle text-base text-white" aria-hidden="true"></i>
                                    <span class="text-[12.5px] font-bold text-white">
                                        {{ __('roadmap.status.active_all_year') }}
                                    </span>
                                </div>
                            @else
                                @foreach ($row['months'] as $cell)
                                    @php $st = $cell['status']; @endphp
                                    <div class="flex flex-col items-center justify-center gap-1 rounded-lg px-1 py-2.5"
                                         style="background: {{ $st->color() }}; border: {{ $st->border() }}"
                                         title="{{ $s->name }} — {{ $bulan[$cell['month'] - 1] }}: {{ $st->label() }}">
                                        <i class="ph-duotone {{ $st->icon() }} text-[16px]"
                                           style="color: {{ $st->textColor() }}" aria-hidden="true"></i>
                                        <span class="text-center text-[9.5px] font-bold leading-tight"
                                              style="color: {{ $st->textColor() }}">{{ $st->label() }}</span>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ══════════ SUKU TAHUN ══════════ --}}
        <div x-show="paparan === 'quarter'" x-cloak class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($roadmap['quarters'] as $q)
                <div class="rounded-xl p-4" style="background: var(--hover-bg3); border: 1px solid var(--border3)">
                    <div class="flex items-center justify-between">
                        <span class="text-[12px] font-extrabold text-t94">Q{{ $q['quarter'] }}</span>
                        <span class="text-[10.5px] text-t60">
                            {{ $bulan[$q['months'][0] - 1] }}–{{ $bulan[$q['months'][2] - 1] }}
                        </span>
                    </div>

                    <div class="mt-2 text-[19px] font-extrabold leading-none text-t94">{{ $duit((float) $q['target']) }}</div>
                    <div class="mt-1 text-[10.5px] text-t60">{{ __('roadmap.target.annual') }}</div>

                    <div class="mt-3 flex flex-col gap-1.5">
                        @foreach ($rows as $row)
                            <div class="flex items-center gap-1.5">
                                <span class="w-[86px] shrink-0 truncate text-[10.5px] text-t70">{{ $row['service']->name }}</span>
                                @foreach ($q['months'] as $m)
                                    @php $st = $row['months'][$m]['status']; @endphp
                                    <span class="h-4 flex-1 rounded"
                                          style="background: {{ $st->color() }}; border: {{ $st->border() }}"
                                          title="{{ $bulan[$m - 1] }}: {{ $st->label() }}"></span>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ══════════ SENARAI BULAN ══════════
             Ini juga susun atur telefon bagi grid: dua belas lajur pada
             skrin 390px ialah tatal mendatar melalui teks yang tidak boleh
             dibaca. --}}
        <div x-show="paparan === 'list'" x-cloak class="flex flex-col gap-2">
            @foreach ($roadmap['months'] as $m)
                <div class="overflow-hidden rounded-xl" style="border: 1px solid var(--border3)">
                    <button type="button" x-on:click="buka({{ $m }})"
                            class="flex w-full items-center gap-2 px-3 py-2.5 text-left"
                            style="background: var(--hover-bg2)">
                        <span class="text-[12px] font-extrabold text-t94">{{ __('calendar.months_full')[$m - 1] }}</span>

                        @if (! empty($events[$m]))
                            <span class="rounded-md px-1.5 py-0.5 text-[10px] font-bold"
                                  style="background: oklch(0.62 0.19 255/0.18); color: oklch(0.74 0.15 255)">
                                {{ __('roadmap.calendar.events', ['count' => count($events[$m])]) }}
                            </span>
                        @endif

                        <i class="ph-duotone ph-caret-down ml-auto text-t55 transition-transform"
                           :class="bulanTerbuka === {{ $m }} && 'rotate-180'" aria-hidden="true"></i>
                    </button>

                    <div class="flex flex-col gap-1 px-3 py-2.5">
                        @foreach ($rows as $row)
                            @php $st = $row['months'][$m]['status']; @endphp
                            <div class="flex items-center gap-2">
                                <span class="w-[110px] shrink-0 truncate text-[11.5px] font-semibold text-t80">
                                    {{ $row['service']->name }}
                                </span>
                                <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-[10.5px] font-bold"
                                      style="background: {{ $st->color() }}; color: {{ $st->textColor() }}">
                                    <i class="ph-duotone {{ $st->icon() }} text-[12px]" aria-hidden="true"></i>
                                    {{ $st->label() }}
                                </span>
                                @if ($row['months'][$m]['target'] > 0)
                                    <span class="ml-auto text-[11px] font-bold text-t75">
                                        {{ $duit((float) $row['months'][$m]['target']) }}
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ══ Acara bulan yang dibuka ══ --}}
        @foreach ($roadmap['months'] as $m)
            <div x-show="bulanTerbuka === {{ $m }}" x-cloak
                 class="mt-3 overflow-hidden rounded-xl"
                 style="background: var(--hover-bg3); border: 1px solid oklch(0.62 0.19 255/0.35)">
                <div class="flex items-center gap-2 px-4 py-2.5"
                     style="background: oklch(0.62 0.19 255/0.12)">
                    <i class="ph-duotone ph-calendar-dots text-base"
                       style="color: oklch(0.72 0.15 255)" aria-hidden="true"></i>
                    <span class="text-[12px] font-extrabold text-t90">
                        {{ __('roadmap.calendar.events_month', [
                            'count' => count($events[$m] ?? []),
                            'month' => __('calendar.months_full')[$m - 1].' '.$roadmap['year'],
                        ]) }}
                    </span>
                    <button type="button" x-on:click="bulanTerbuka = null"
                            class="ml-auto text-t55" aria-label="{{ __('app.cancel') }}">
                        <i class="ph-duotone ph-x text-sm" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="flex flex-col gap-1.5 px-4 py-3">
                    @forelse ($events[$m] ?? [] as $event)
                        <div class="flex flex-wrap items-baseline gap-x-2.5 gap-y-0.5 text-[12px]">
                            <span class="font-bold text-t90" style="min-width: 96px">
                                {{ $event['start']->translatedFormat('d M') }}
                                @unless ($event['allDay'])
                                    <span class="font-normal text-t60">{{ $event['start']->format('H:i') }}</span>
                                @endunless
                            </span>
                            <span class="font-medium text-t85">{{ $event['title'] }}</span>
                            @if ($event['location'])
                                <span class="text-t55">· {{ $event['location'] }}</span>
                            @endif
                        </div>
                    @empty
                        <div class="text-[12px] text-t60">
                            {{ filled($plan->calendar_id)
                                ? __('roadmap.calendar.none')
                                : __('roadmap.calendar.not_connected') }}
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach

        {{-- ══ Petunjuk & Ringkasan Strategi ══ --}}
        <div class="mt-4 grid gap-3 lg:grid-cols-2">
            <div class="flex gap-3 rounded-xl p-4" style="background: var(--hover-bg3); border: 1px solid var(--border3)">
                <div class="flex w-[86px] shrink-0 flex-col items-center justify-center gap-1.5 rounded-lg py-3"
                     style="background: {{ $unguTua }}">
                    <i class="ph-duotone ph-book-open text-[22px] text-white" aria-hidden="true"></i>
                    <span class="text-[10px] font-extrabold tracking-wide text-white">{{ __('roadmap.legend') }}</span>
                </div>

                <div class="grid min-w-0 flex-1 gap-2.5 sm:grid-cols-2">
                    @foreach ([\App\Enums\RoadmapStatus::ActiveAllYear, \App\Enums\RoadmapStatus::Campaign, \App\Enums\RoadmapStatus::Paused, \App\Enums\RoadmapStatus::Resumed] as $st)
                        <div class="flex items-start gap-2">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full"
                                  style="background: {{ $st->color() }}">
                                <i class="ph-duotone {{ $st->icon() }} text-[14px]"
                                   style="color: {{ $st->textColor() }}" aria-hidden="true"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-[11.5px] font-bold text-t90">{{ $st->label() }}</span>
                                <span class="block text-[10.5px] leading-snug text-t60">
                                    {{ __('roadmap.legend_note.'.$st->value) }}
                                </span>
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-3 rounded-xl p-4" style="background: var(--hover-bg3); border: 1px solid var(--border3)">
                <div class="flex w-[86px] shrink-0 flex-col items-center justify-center gap-1.5 rounded-lg py-3"
                     style="background: {{ $unguTua }}">
                    <i class="ph-duotone ph-target text-[22px] text-white" aria-hidden="true"></i>
                    <span class="text-center text-[9.5px] font-extrabold leading-tight tracking-wide text-white">
                        {{ __('roadmap.summary') }}
                    </span>
                </div>

                <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                    @forelse ($ringkasan as $poin)
                        <div class="flex items-baseline gap-2">
                            <span class="flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-full text-[10px] font-extrabold text-white"
                                  style="background: {{ $ungu }}">{{ $loop->iteration }}</span>
                            <span class="text-[11.5px] leading-relaxed text-t85">{{ $poin }}</span>
                        </div>
                    @empty
                        <span class="text-[11.5px] text-t60">{{ __('roadmap.summary_empty') }}</span>
                    @endforelse
                </div>
            </div>
        </div>

        <p class="mt-3 text-[10.5px] text-t55">
            <i class="ph-duotone ph-info text-[12px]" aria-hidden="true"></i>
            {{ __('roadmap.target.note') }}
        </p>
    </div>
</div>
