@php
    $monthName = $monthsFull[$month - 1];
    $threshold = (int) config('dbena.service_status_threshold');
@endphp

<div x-data="{
        showSheet: @entangle('showSheetModal'),
        showRaw: @entangle('showRawDataModal'),
        showAddOwner: @entangle('showAddOwnerModal'),
     }"
     class="flex flex-col gap-5 sm:gap-6">

    {{-- ══ Breadcrumb ══ --}}
    <div class="flex items-center gap-3.5">
        <a href="{{ route('dashboard') }}" wire:navigate
           class="flex h-[38px] w-[38px] items-center justify-center rounded-[10px] transition-colors hover:bg-hover"
           style="border: 1px solid var(--border2)" aria-label="{{ __('app.back') }}">
            <i class="ph-duotone ph-arrow-left text-lg" aria-hidden="true"></i>
        </a>
        <nav class="min-w-0 text-[13px] text-t60" aria-label="Breadcrumb">
            <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-t85">{{ __('service.breadcrumb_home') }}</a>
            <span class="text-t40"> / </span>
            <span class="font-semibold text-t90">{{ $service->name }}</span>
        </nav>
    </div>

    {{-- ══ Bar pemilih bulan/tahun ══ --}}
    <div class="dbena-card flex flex-col gap-3 px-4 py-3.5 sm:px-[18px]">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="text-[12.5px] text-t60">
                {{ __('service.view_month') }}: <b class="text-t90">{{ $monthName }} {{ $year }}</b>
            </div>
            <div x-data="{ open: false }" class="relative" x-on:click.outside="open = false">
                <button type="button" x-on:click="open = !open" :aria-expanded="open.toString()"
                        class="flex items-center gap-2 rounded-[10px] px-3 py-2 text-[12.5px] text-t75 transition-colors hover:bg-hover3"
                        style="border: 1px solid var(--border2)">
                    <i class="ph-duotone ph-calendar-blank" style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
                    {{ $year }}
                    <i class="ph-duotone ph-caret-down text-xs" aria-hidden="true"></i>
                </button>
                <div x-show="open" x-cloak x-transition
                     class="absolute right-0 top-[42px] z-30 max-h-[300px] w-[140px] overflow-y-auto rounded-xl bg-card p-2 shadow-2xl"
                     style="border: 1px solid var(--border2)">
                    @foreach ($years as $y)
                        <button type="button" wire:click="selectYear({{ $y }})" x-on:click="open = false"
                                class="mb-0.5 w-full rounded-lg py-2 text-center text-[13px] font-bold"
                                @style([
                                    'background: oklch(0.78 0.12 85); color: oklch(0.15 0.02 260)' => $y === $year,
                                    'color: var(--t65)' => $y !== $year,
                                ])>{{ $y }}</button>
                    @endforeach
                </div>
            </div>
        </div>
        <x-month-selector :months="$monthLabels" :active="$month" />
    </div>

    {{-- ══ 3 kad ringkasan ══ --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:gap-5">
        <div class="dbena-card p-5">
            <div class="text-[12.5px] text-t60">{{ __('service.total_sales') }}</div>
            <div class="mt-2 font-display text-2xl font-extrabold sm:text-[26px]">{{ $metrics->formatRm($actual) }}</div>
        </div>
        <div class="dbena-card p-5">
            <div class="text-[12.5px] text-t60">{{ __('service.target') }}</div>
            <div class="mt-2 font-display text-2xl font-extrabold sm:text-[26px]">{{ $metrics->formatRm($target) }}</div>
        </div>
        <div class="dbena-card flex items-center gap-4 p-5">
            <x-donut-chart :pct="$pct" :size="76" :hole="54" value-size="13px" />
            <div class="text-[12px] leading-snug text-t60">{{ __('service.target_achievement') }}</div>
        </div>
    </div>

    {{-- ══ Kad Actual vs Sasaran ══ --}}
    @if ($quotationCard || $siteVisitCard)
        <div class="grid grid-cols-1 gap-4 sm:gap-5 {{ $quotationCard && $siteVisitCard ? 'lg:grid-cols-2' : '' }}">
            @foreach ([['label' => __('service.quotation'), 'card' => $quotationCard], ['label' => $siteVisitLabel, 'card' => $siteVisitCard]] as $item)
                @continue(! $item['card'])
                <div class="dbena-card p-5">
                    <div class="mb-3 text-[12.5px] text-t60">{{ $item['label'] }}: {{ __('service.actual_vs_target') }}</div>
                    <div class="mb-2.5 flex flex-wrap items-baseline gap-3.5">
                        <span class="font-display text-[22px] font-extrabold">{{ $item['card']['actualLabel'] }}</span>
                        <span class="text-[13px] text-t65">/ {{ $item['card']['targetLabel'] }} {{ __('service.target_suffix') }}</span>
                    </div>
                    <x-progress-bar :pct="$item['card']['pct']" :color="$item['card']['barColor']" height="8px" :decimals="0" />
                </div>
            @endforeach
        </div>
    @endif

    {{-- ══ Carta Trend Bulanan ══ --}}
    <div class="dbena-card p-5 sm:p-6">
        <h2 class="mb-4 text-base font-bold">{{ __('service.monthly_trend') }}</h2>
        <x-trend-chart :chart="$serviceChart" height="300px" />
    </div>

    {{-- ══ Peta Perjalanan Sales ══
         Diletak SEBELUM carta trend dengan sengaja. Carta menunjukkan
         pergerakan setiap metrik; peta menunjukkan metrik mana yang
         penting minggu ini. Susunan itu memberitahu pemilik ke mana perlu
         melihat dahulu. --}}
    @if (! empty($journey['stages']))
        <x-sales-journey :journey="$journey" />
    @endif

    {{-- ══ Trend Mingguan — 3 mini carta ══ --}}
    @if (count($weeklyBars['quotation']) > 0 || count($weeklyBars['amount']) > 0)
        <div class="dbena-card p-5 sm:p-6">
            <h2 class="mb-0.5 text-base font-bold">{{ __('service.weekly_trend_title') }}</h2>
            <p class="padat-sorok mb-5 text-[12px] text-t55">
                {{ __('service.weekly_trend_caption', ['month' => $monthName, 'year' => $year]) }}
            </p>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                @if (count($weeklyBars['amount']))
                    <x-weekly-mini-chart :title="__('service.amount_quotation')"
                                         :target-label="$weeklyTargets['amount']"
                                         :bars="$weeklyBars['amount']" />
                @endif
                @if (count($weeklyBars['quotation']))
                    <x-weekly-mini-chart :title="__('service.quotation_count')"
                                         :target-label="$weeklyTargets['quotation']"
                                         :bars="$weeklyBars['quotation']" />
                @endif
                @if (count($weeklyBars['siteVisit']))
                    <x-weekly-mini-chart :title="__('service.site_visit_count', ['label' => $siteVisitLabel])"
                                         :target-label="$weeklyTargets['siteVisit']"
                                         :bars="$weeklyBars['siteVisit']" />
                @endif
            </div>

            <div class="mt-3.5 flex items-center gap-1.5 text-[11px] text-t55">
                <span class="w-3.5 border-t-2 border-dashed" style="border-color: oklch(0.78 0.12 85/0.8)"></span>
                {{ __('service.weekly_target_line') }}
            </div>
        </div>
    @endif

    {{-- ══ JADUAL DATA KRITIKAL MINGGUAN ══ --}}
    @include('livewire.dashboard._critical-table')

    {{-- ══ Prestasi Pemilik Data ══ --}}
    @if ($ownerPerformance->isNotEmpty())
        <div class="dbena-card p-5 sm:p-6">
            <h2 class="mb-1 text-base font-bold">{{ __('service.owner_performance') }}</h2>
            <p class="padat-sorok mb-5 text-[12px] text-t55">{{ __('service.owner_performance_hint') }}</p>

            <div class="grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr))">
                @foreach ($ownerPerformance as $op)
                    <div class="rounded-xl p-[18px] text-left transition-colors"
                         style="background: var(--hover-bg3);
                                border: 1px solid {{ $ownerFilter === $op['owner']->id ? $op['color'] : 'var(--border3)' }}">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <button type="button" wire:click="toggleOwnerFilter({{ $op['owner']->id }})">
                                <x-owner-chip :name="$op['name']" :color="$op['color']"
                                              :selected="$ownerFilter === $op['owner']->id" />
                            </button>
                            @if ($op['hasCritical'])
                                <span class="flex items-center gap-1.5 text-[11px] font-bold"
                                      style="color: oklch(0.6 0.2 25)">
                                    <i class="ph-duotone ph-warning-circle text-base" aria-hidden="true"></i>
                                    {{ __('service.needs_action') }}
                                </span>
                            @endif
                        </div>

                        <x-progress-bar :pct="$op['scorePct']" :color="$op['barColor']" height="8px" :decimals="0" label-width="40px" />

                        <div class="mb-3 mt-2.5 flex flex-wrap gap-3.5 text-[11.5px] text-t65">
                            <span><b style="color: oklch(0.55 0.15 145)">{{ $op['greenCount'] }}</b> {{ __('service.on_track') }}</span>
                            <span><b style="color: oklch(0.78 0.15 85)">{{ $op['yellowCount'] }}</b> {{ __('service.has_plan') }}</span>
                            <span><b style="color: oklch(0.6 0.2 25)">{{ $op['redCount'] }}</b> {{ __('service.critical') }}</span>
                            <span class="text-t55">{{ __('service.of_metrics', ['count' => $op['total']]) }}</span>
                        </div>

                        @if ($op['diagnoses']->isNotEmpty())
                            <div class="flex flex-col gap-2.5">
                                @foreach ($op['diagnoses']->take(3) as $d)
                                    @php
                                        $dColor = $d['severity'] === 'critical' ? 'oklch(0.6 0.2 25)' : 'oklch(0.78 0.15 85)';
                                    @endphp
                                    <div class="rounded-lg px-3 py-2.5 text-left"
                                         style="background: color-mix(in oklch, {{ $dColor }} 8%, transparent);
                                                border-left: 3px solid {{ $dColor }}">
                                        <div class="mb-1 flex flex-wrap items-baseline gap-x-2">
                                            <span class="text-[12px] font-bold" style="color: {{ $dColor }}">{{ $d['label'] }}</span>
                                            <span class="text-[11px] text-t60">
                                                {{ $d['actualLabel'] }} / {{ $d['targetLabel'] }}
                                            </span>
                                        </div>

                                        {{-- Poin ringkas. Naratif penuh masih ada
                                             dalam laporan PDF bagi yang mahukannya. --}}
                                        <ul class="flex flex-col gap-1">
                                            @foreach ($d['points'] as $pt)
                                                <li class="flex gap-1.5 text-[11.5px] leading-snug text-t75">
                                                    <span class="mt-[3px] h-1 w-1 shrink-0 rounded-full"
                                                          style="background: {{ $dColor }}"></span>
                                                    <span>{{ $pt['text'] }}</span>
                                                </li>
                                            @endforeach
                                        </ul>

                                        @if (! empty($d['actions']))
                                            <div class="mt-2 flex flex-col gap-1">
                                                @foreach ($d['actions'] as $act)
                                                    <div class="flex gap-1.5 text-[11.5px] leading-snug text-t70"
                                                         title="{{ $act['detail'] }}">
                                                        <i class="ph-duotone ph-arrow-elbow-down-right mt-px shrink-0"
                                                           style="color: {{ $dColor }}" aria-hidden="true"></i>
                                                        <span><b>{{ $act['label'] }}</b></span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach

                                @if ($op['diagnoses']->count() > 3)
                                    <a href="{{ route('laporan.owner', ['servis' => $service->key, 'bulan' => $month, 'tahun' => $year]) }}"
                                       wire:navigate
                                       class="text-[11.5px] font-semibold"
                                       style="color: oklch(0.78 0.12 85)">
                                        {{ __('funnel.see_all', ['count' => $op['diagnoses']->count()]) }}
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ══ Analisis Penting + Keutamaan ══ --}}
    <div class="flex flex-col items-start gap-5 xl:flex-row xl:gap-6">
        <div class="dbena-card w-full p-5 sm:p-6 xl:flex-[2]">
            <h2 class="mb-1 text-base font-bold">{{ __('service.key_analysis') }}</h2>
            <p class="padat-sorok mb-5 text-[12px] text-t55">{{ __('service.key_analysis_hint') }}</p>

            <div class="mb-4 rounded-xl px-4 py-4"
                 style="background: var(--hover-bg3); border: 1px solid var(--border3)">
                <div class="mb-2.5 text-[11.5px] text-t60">{{ __('service.actual_vs_target_comparison') }}</div>
                <div class="mb-2.5 flex flex-wrap items-baseline gap-x-6 gap-y-1">
                    <div>
                        <span class="font-display text-[22px] font-extrabold">{{ $metrics->formatRm($actual) }}</span>
                        <span class="ml-1.5 text-[11px] text-t55">Actual</span>
                    </div>
                    <div>
                        <span class="font-display text-base font-bold text-t65">{{ $metrics->formatRm($target) }}</span>
                        <span class="ml-1.5 text-[11px] text-t55">{{ __('service.target') }}</span>
                    </div>
                </div>
                <x-progress-bar :pct="min(100, $pct)" height="8px"
                                :color="$analysis['isGood'] ? 'oklch(0.72 0.15 145)' : 'oklch(0.6 0.22 350)'"
                                label-width="50px" />
            </div>

            {{-- 3 tile analisis --}}
            <div class="mb-5 grid grid-cols-1 gap-3.5 sm:grid-cols-3">
                @foreach ([
                    ['icon' => 'ph-target', 'label' => __('service.tile_achievement'), 'value' => number_format($pct, 1).'%', 'note' => __('service.gap_note', ['amount' => $analysis['gapLabel']])],
                    ['icon' => 'ph-chart-line-up', 'label' => __('service.tile_avg_quotation'), 'value' => $analysis['avgQuotation'], 'note' => __('service.avg_quotation_note')],
                    ['icon' => 'ph-gauge', 'label' => __('service.tile_run_rate'), 'value' => $analysis['runRate'].__('service.per_month'), 'note' => __('service.run_rate_note', ['months' => $analysis['monthsLeft']])],
                ] as $tile)
                    <div class="rounded-xl p-4" style="background: var(--hover-bg3); border: 1px solid var(--border3)">
                        <div class="mb-2.5 flex items-center gap-2">
                            <i class="ph-duotone {{ $tile['icon'] }} text-lg" style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
                            <span class="text-[11.5px] leading-tight text-t60">{{ $tile['label'] }}</span>
                        </div>
                        <div class="font-display text-[19px] font-extrabold">{{ $tile['value'] }}</div>
                        <div class="mt-1 text-[11px] text-t55">{{ $tile['note'] }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Kad nasihat mingguan --}}
            @if ($weeklyTargets['quotation'] || $weeklyTargets['siteVisit'])
                <div class="mb-5 flex flex-col gap-3.5 sm:flex-row">
                    @if ($weeklyTargets['quotation'])
                        <div class="flex flex-1 items-center gap-3 rounded-xl px-4 py-3.5"
                             style="background: var(--hover-bg3); border: 1px solid var(--border3)">
                            <i class="ph-duotone ph-file-text shrink-0 text-[22px]" style="color: oklch(0.6 0.22 350)" aria-hidden="true"></i>
                            <p class="text-[12.5px] leading-relaxed text-t70">
                                {{ __('service.advice_quotation', ['count' => $weeklyTargets['quotation']]) }}
                            </p>
                        </div>
                    @endif
                    @if ($weeklyTargets['siteVisit'])
                        <div class="flex flex-1 items-center gap-3 rounded-xl px-4 py-3.5"
                             style="background: var(--hover-bg3); border: 1px solid var(--border3)">
                            <i class="ph-duotone ph-map-pin shrink-0 text-[22px]" style="color: oklch(0.6 0.22 350)" aria-hidden="true"></i>
                            <p class="text-[12.5px] leading-relaxed text-t70">
                                {{ __('service.advice_site_visit', ['count' => $weeklyTargets['siteVisit']]) }}
                            </p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Tindakan disyorkan — teks berbeza ikut pencapaian --}}
            <h3 class="mb-2.5 text-[13px] font-bold">{{ __('service.recommended_actions') }}</h3>
            <div class="flex flex-col gap-2.5">
                @foreach (__($analysis['isGood'] ? 'service.actions_good' : 'service.actions_bad') as $bullet)
                    <div class="flex items-start gap-2.5">
                        <i class="ph-duotone ph-check-circle mt-px shrink-0 text-base"
                           style="color: oklch(0.72 0.15 145)" aria-hidden="true"></i>
                        <p class="text-[12.5px] leading-relaxed text-t75">{{ $bullet }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($priority)
            <div class="dbena-card w-full p-5 xl:flex-1">
                <h2 class="mb-3.5 text-[15px] font-bold">{{ __('service.priority') }}</h2>
                <p class="text-[13px] leading-relaxed text-t80">{{ $priority->description }}</p>
                <div class="mt-4 flex items-center gap-2.5 border-t pt-4" style="border-color: var(--border)">
                    <x-avatar :initials="$priority->ownerInitials()" :size="32" />
                    <span class="text-[12.5px]">{{ $priority->owner_name }}</span>
                </div>
            </div>
        @endif
    </div>

    {{-- ══ MODAL: Google Sheet (keputusan D2) ══ --}}
    <x-modal show="showSheet" :title="__('service.google_sheet')" icon="ph-google-logo">
        <div class="flex flex-col gap-4">
            <p class="text-[12px] leading-relaxed text-t55">{{ __('service.sheet_phase_note') }}</p>

            <div>
                <label for="sheet-url" class="mb-1.5 block text-[11.5px] text-t55">{{ __('service.sheet_url') }}</label>
                <input id="sheet-url" type="url" wire:model="sheetUrl" class="dbena-input"
                       placeholder="{{ __('service.sheet_url_placeholder') }}"
                       @cannot('manage-sheet-integration') disabled @endcannot>
            </div>

            <div class="flex items-center gap-2 text-[12px] text-t60">
                <span class="h-2 w-2 rounded-full"
                      style="background: {{ $sheet?->connected ? 'oklch(0.55 0.15 145)' : 'var(--t50)' }}"></span>
                {{ $sheet?->last_synced_at
                    ? __('service.sheet_last_synced').': '.$sheet->last_synced_at->translatedFormat('d M Y, H:i')
                    : __('service.sheet_never_synced') }}
            </div>
        </div>

        <x-slot:footer>
            @if ($sheetUrl)
                <a href="{{ $sheetUrl }}" target="_blank" rel="noopener noreferrer"
                   class="flex items-center gap-2 rounded-[9px] px-4 py-2.5 text-[12.5px] font-semibold text-t80"
                   style="border: 1px solid var(--border2)">
                    <i class="ph-duotone ph-arrow-square-out" aria-hidden="true"></i> {{ __('service.sheet_open') }}
                </a>
            @endif

            @can('manage-sheet-integration')
                @if ($sheet?->connected)
                    <button type="button" wire:click="syncSheet"
                            class="rounded-[9px] px-4 py-2.5 text-[12.5px] font-semibold text-t80"
                            style="border: 1px solid var(--border2)">{{ __('service.sheet_sync') }}</button>
                    <button type="button" wire:click="disconnectSheet"
                            class="rounded-[9px] px-4 py-2.5 text-[12.5px] font-semibold"
                            style="border: 1px solid oklch(0.6 0.2 25/0.4); color: oklch(0.7 0.15 25)">
                        {{ __('service.sheet_disconnect') }}
                    </button>
                @else
                    <button type="button" wire:click="connectSheet"
                            class="dbena-btn-gold px-4 py-2.5 text-[12.5px]">{{ __('service.sheet_connect') }}</button>
                @endif
            @endcan
        </x-slot:footer>
    </x-modal>

    {{-- ══ MODAL: Raw Data — Admin sahaja (BEBAS, betulkan isu #19) ══ --}}
    @can('access-admin-panel')
    <x-modal show="showRaw" :title="__('service.raw_data')" icon="ph-code">
        <p class="mb-3 text-[11.5px] text-t55">{{ __('service.raw_data_hint') }}</p>
        <pre class="overflow-auto rounded-[10px] p-4 text-[11.5px] leading-relaxed text-t80"
             style="background: var(--hover-bg3); border: 1px solid var(--border3); font-family: ui-monospace, SFMono-Regular, Consolas, monospace"
        >{{ $rawDataJson }}</pre>
    </x-modal>

    {{-- ══ MODAL: Tambah PIC — Admin sahaja ══ --}}
    <x-modal show="showAddOwner" :title="__('service.add_owner_title')" icon="ph-user-plus" max-width="440px">
        <div class="flex flex-col gap-4">
            <div>
                <label for="new-owner" class="mb-1.5 block text-[11.5px] text-t55">{{ __('service.owner_name') }}</label>
                <input id="new-owner" type="text" wire:model="newOwnerName" wire:keydown.enter="addOwner"
                       class="dbena-input uppercase" placeholder="{{ __('service.owner_name_placeholder') }}">
            </div>
        </div>
        <x-slot:footer>
            <button type="button" x-on:click="showAddOwner = false"
                    class="rounded-[9px] px-4 py-2.5 text-[12.5px] font-semibold text-t70"
                    style="border: 1px solid var(--border2)">{{ __('app.cancel') }}</button>
            <button type="button" wire:click="addOwner"
                    class="dbena-btn-gold px-4 py-2.5 text-[12.5px]">{{ __('app.add') }}</button>
        </x-slot:footer>
    </x-modal>
    @endcan
</div>
