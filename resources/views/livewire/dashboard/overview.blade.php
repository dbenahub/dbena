@php
    $monthName = $monthsFull[$month - 1];
    $caption = $mode->isYearly()
        ? __('dashboard.yearly_caption', ['month' => $monthName, 'year' => $year])
        : __('dashboard.monthly_caption', ['month' => $monthName, 'year' => $year]);
@endphp

<div class="flex flex-col gap-5 sm:gap-6">

    {{-- ══ BAR KAWALAN: tahun · bulan · period ══
         Diletak dalam kandungan halaman (bukan topbar layout) supaya ia boleh
         menyusun semula secara bebas pada mobile. --}}
    <div class="dbena-card flex flex-wrap items-center gap-3 px-4 py-3.5 sm:px-5">

        {{-- Tahun --}}
        <div x-data="{ open: false }" class="relative" x-on:click.outside="open = false">
            <button type="button" x-on:click="open = !open" :aria-expanded="open.toString()"
                    class="flex items-center gap-2 rounded-[10px] px-3.5 py-2.5 text-[13px] text-t75 transition-colors hover:bg-hover3"
                    style="border: 1px solid var(--border2)">
                <i class="ph-duotone ph-calendar-blank text-[17px]" style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
                {{ __('dashboard.year') }} {{ $year }}
                <i class="ph-duotone ph-caret-down text-sm" aria-hidden="true"></i>
            </button>
            <div x-show="open" x-cloak x-transition
                 class="absolute left-0 top-[46px] z-30 max-h-[320px] w-[160px] overflow-y-auto rounded-xl bg-card p-2 shadow-2xl"
                 style="border: 1px solid var(--border2)">
                @foreach ($years as $y)
                    <button type="button" wire:click="selectYear({{ $y }})" x-on:click="open = false"
                            class="mb-0.5 w-full rounded-lg py-2.5 text-center text-[13px] font-bold transition-colors"
                            @style([
                                'background: oklch(0.78 0.12 85); color: oklch(0.15 0.02 260)' => $y === $year,
                                'color: var(--t65)' => $y !== $year,
                            ])>{{ $y }}</button>
                @endforeach
            </div>
        </div>

        {{-- Period — AKTIF (keputusan D3): benar-benar menukar unit pengiraan --}}
        <div x-data="{ open: false }" class="relative" x-on:click.outside="open = false">
            <button type="button" x-on:click="open = !open" :aria-expanded="open.toString()"
                    class="flex items-center gap-2 rounded-[10px] px-3.5 py-2.5 text-[13px] text-t90 transition-colors hover:bg-hover3"
                    style="border: 1px solid var(--border2)">
                <i class="ph-duotone ph-clock-countdown text-[17px]" style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
                {{ $periodMode->label() }}
                <i class="ph-duotone ph-caret-down text-sm" aria-hidden="true"></i>
            </button>
            <div x-show="open" x-cloak x-transition
                 class="absolute left-0 top-[46px] z-30 w-[220px] overflow-hidden rounded-xl bg-card shadow-2xl"
                 style="border: 1px solid var(--border2)">
                @foreach (\App\Enums\PeriodMode::cases() as $option)
                    <button type="button" wire:click="selectPeriod('{{ $option->value }}')" x-on:click="open = false"
                            class="w-full px-4 py-3 text-left text-[13px] text-t90 transition-colors hover:bg-hover2"
                            @style(['background: var(--hover-bg3)' => $option === $periodMode])>
                        {{ $option->label() }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="ml-auto text-[11.5px] text-t50">{{ $periodMode->previousLabel() }}</div>
    </div>

    {{-- ══ BLOK 1: Prestasi Keseluruhan + 3 kad ringkasan ══ --}}
    <div class="flex flex-col items-stretch gap-5 xl:flex-row xl:gap-6">

        {{-- Kad prestasi keseluruhan --}}
        <div class="flex flex-1 flex-col rounded-2xl p-5 sm:p-[26px] xl:flex-[2]"
             style="background: var(--card-bg); border: 1px solid oklch(0.78 0.12 85/0.3)">

            <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h2 class="text-base font-bold">{{ __('dashboard.overall_performance') }}</h2>
                        <span class="flex items-center gap-1.5 rounded-[20px] py-[3px] pl-2 pr-2.5"
                              style="background: color-mix(in oklch, {{ $currentTier->color_token }} 18%, transparent);
                                     border: 1px solid color-mix(in oklch, {{ $currentTier->color_token }} 40%, transparent)">
                            <span class="h-[7px] w-[7px] rounded-full" style="background: {{ $currentTier->color_token }}"></span>
                            <span class="text-[11.5px] font-bold" style="color: {{ $currentTier->color_token }}">
                                {{ __('dashboard.index_badge') }}: {{ $currentTier->name }}
                            </span>
                        </span>
                    </div>
                    <p class="mt-0.5 text-[11.5px] text-t55">{{ $caption }}</p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <x-view-mode-toggle :mode="$viewMode" />
                </div>
            </div>

            <x-month-selector :months="$monthLabels" :active="$month" max-width="100%" />

            <div class="mt-5 flex flex-1 flex-col items-center gap-6 sm:flex-row sm:gap-8">
                <div class="w-full flex-1">
                    <div class="text-[13px] text-t60">
                        {{ $mode->isYearly() ? __('dashboard.cumulative_sales') : __('dashboard.monthly_sales') }}
                        <span class="text-t50">· {{ $periodMode->label() }}</span>
                    </div>
                    <div class="my-1.5 mb-[18px] font-display text-[28px] font-extrabold sm:text-[38px]">
                        {{ $metrics->formatRm($displayActual) }}
                    </div>

                    <div class="text-[13px] text-t60">
                        {{ $mode->isYearly() ? __('dashboard.cumulative_target') : __('dashboard.monthly_target') }}
                    </div>
                    <div class="my-1 mb-4 text-xl font-bold">{{ $metrics->formatRm($displayTarget) }}</div>

                    <div class="mb-1.5 text-[12px] text-t55">{{ __('dashboard.change_vs_target') }}</div>
                    <div class="flex items-center gap-1.5 text-[15px] font-bold"
                         style="color: {{ $metrics->changeColor($changeVsTarget) }}">
                        <i class="ph-duotone {{ $changeVsTarget >= 0 ? 'ph-trend-up' : 'ph-trend-down' }}" aria-hidden="true"></i>
                        {{ ($changeVsTarget >= 0 ? '+' : '').number_format($changeVsTarget, 1) }}%
                        <span class="text-[12px] font-normal italic text-t55">{{ __('dashboard.vs_current_target') }}</span>
                    </div>
                </div>

                <div class="shrink-0">
                    <div class="hidden sm:block">
                        <x-donut-chart :pct="$overallPct" :size="210" :hole="158"
                                       :label="__('dashboard.target_achievement')" />
                    </div>
                    <div class="sm:hidden">
                        <x-donut-chart :pct="$overallPct" :size="150" :hole="112" value-size="22px"
                                       :label="__('dashboard.target_achievement')" />
                    </div>
                </div>
            </div>
        </div>

        {{-- 3 kad ringkasan --}}
        <div class="flex flex-1 flex-col gap-4">
            <x-stat-card icon="ph-wallet" :label="__('dashboard.collection')"
                         :value="$summary['collection']['value']"
                         :change-label="$summary['collection']['changeLabel']"
                         :change-color="$summary['collection']['changeColor']" />

            <x-stat-card icon="ph-receipt" icon-bg="oklch(0.78 0.12 85/0.16)" icon-color="oklch(0.78 0.12 85)"
                         :label="__('dashboard.quotation')"
                         :value="$summary['quotation']['value']"
                         :change-label="$summary['quotation']['changeLabel']"
                         :change-color="$summary['quotation']['changeColor']" />

            <x-stat-card icon="ph-user-plus" :label="__('dashboard.new_leads')"
                         :value="$summary['leads']['value']"
                         :change-label="$summary['leads']['changeLabel']"
                         :change-color="$summary['leads']['changeColor']" />
        </div>
    </div>

    {{-- ══ BLOK 2: Prestasi Mengikut Servis ══ --}}
    <div class="dbena-card p-5 sm:p-6">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-bold">{{ __('dashboard.performance_by_service') }}</h2>
                <p class="mt-0.5 text-[11.5px] text-t55">
                    {{ $mode->isYearly()
                        ? __('dashboard.service_view_caption_yearly', ['month' => $monthName, 'year' => $year])
                        : __('dashboard.service_view_caption_monthly', ['month' => $monthName, 'year' => $year]) }}
                </p>
            </div>
            <x-view-mode-toggle :mode="$viewMode" />
        </div>

        {{-- Desktop: grid 6 lajur --}}
        <div class="hidden md:block">
            <div class="grid gap-4 border-b pb-3 text-[11.5px] text-t55"
                 style="grid-template-columns: 2fr 1.2fr 1.2fr 2fr 1.6fr 1.2fr; border-color: var(--border)">
                <div>{{ __('dashboard.col_service') }}</div>
                <div>{{ __('dashboard.col_sales') }}</div>
                <div>{{ __('dashboard.col_target') }}</div>
                <div>{{ __('dashboard.col_achievement') }}</div>
                <div>{{ __('dashboard.col_status') }}</div>
                <div>{{ __('dashboard.col_action') }}</div>
            </div>

            @foreach ($serviceRows as $row)
                <div class="grid items-center gap-4 border-b py-4"
                     style="grid-template-columns: 2fr 1.2fr 1.2fr 2fr 1.6fr 1.2fr; border-color: var(--border3)">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-[9px]"
                             style="background: oklch(0.55 0.22 350/0.16)">
                            <i class="ph-duotone {{ $row['icon'] }} text-lg" style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
                        </div>
                        <span class="text-[13.5px] font-semibold">{{ $row['name'] }}</span>
                    </div>
                    <div class="text-[13.5px] font-semibold">{{ $row['salesLabel'] }}</div>
                    <div class="text-[13.5px] text-t70">{{ $row['targetLabel'] }}</div>
                    <div><x-progress-bar :pct="$row['pct']" :color="$row['barColor']" /></div>
                    <div><x-status-dot :color="$row['statusColor']" :label="$row['statusLabel']" /></div>
                    <a href="{{ route('service.detail', $row['key']) }}" wire:navigate
                       class="flex items-center justify-center gap-1.5 rounded-[9px] px-2.5 py-2 text-[12px] font-semibold transition-colors"
                       style="border: 1px solid oklch(0.78 0.12 85/0.5); color: oklch(0.78 0.12 85)">
                        {{ __('dashboard.view_detail') }} <i class="ph-duotone ph-caret-right" aria-hidden="true"></i>
                    </a>
                </div>
            @endforeach
        </div>

        {{-- Mobile: senarai kad --}}
        <div class="flex flex-col gap-3 md:hidden">
            @foreach ($serviceRows as $row)
                <a href="{{ route('service.detail', $row['key']) }}" wire:navigate
                   class="block rounded-xl p-4 transition-colors active:bg-hover"
                   style="background: var(--hover-bg3); border: 1px solid var(--border3)">
                    <div class="mb-3 flex items-center gap-2.5">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[9px]"
                             style="background: oklch(0.55 0.22 350/0.16)">
                            <i class="ph-duotone {{ $row['icon'] }} text-lg" style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-semibold">{{ $row['name'] }}</div>
                            <div class="mt-0.5"><x-status-dot :color="$row['statusColor']" :label="$row['statusLabel']" /></div>
                        </div>
                        <i class="ph-duotone ph-caret-right text-t55" aria-hidden="true"></i>
                    </div>

                    <div class="mb-2.5 grid grid-cols-2 gap-2 text-[12.5px]">
                        <div>
                            <div class="text-t55">{{ __('dashboard.col_sales') }}</div>
                            <div class="mt-0.5 font-semibold">{{ $row['salesLabel'] }}</div>
                        </div>
                        <div>
                            <div class="text-t55">{{ __('dashboard.col_target') }}</div>
                            <div class="mt-0.5 text-t70">{{ $row['targetLabel'] }}</div>
                        </div>
                    </div>

                    <x-progress-bar :pct="$row['pct']" :color="$row['barColor']" />
                </a>
            @endforeach
        </div>
    </div>

    {{-- ══ BLOK 3: Index Sasaran Jualan ══ --}}
    <div class="dbena-card p-5 sm:p-6">
        <div class="mb-1 flex flex-wrap items-center justify-between gap-2.5">
            <h2 class="text-base font-bold">{{ __('dashboard.sales_target_index') }}</h2>
            <span class="flex items-center gap-1.5 rounded-[20px] px-3 py-1.5"
                  style="background: color-mix(in oklch, {{ $currentTier->color_token }} 18%, transparent)">
                <span class="h-[7px] w-[7px] rounded-full" style="background: {{ $currentTier->color_token }}"></span>
                <span class="text-[12px] font-bold" style="color: {{ $currentTier->color_token }}">
                    {{ __('dashboard.index_status') }}: {{ $currentTier->name }}
                    ({{ $mode->isYearly() ? __('dashboard.timeframe_yearly') : __('dashboard.timeframe_monthly') }})
                </span>
            </span>
        </div>

        <p class="mb-5 text-[12px] text-t55">
            {{ __('dashboard.index_desc', [
                'timeframe' => $mode->isYearly() ? __('dashboard.timeframe_yearly') : __('dashboard.timeframe_monthly'),
                'profit' => $metrics->formatRm($estProfit),
                'margin' => (int) (config('dbena.profit_margin') * 100),
            ]) }}
        </p>

        <x-tier-pyramid :tiers="$tiersView" />

        {{-- Jadual 7 lajur: skrol mendatar dengan lajur pertama melekat --}}
        <div class="sticky-col-table overflow-x-auto">
            <div class="min-w-[760px]">
                <div class="grid gap-2.5 border-b pb-2.5 text-[11px] font-bold uppercase text-t60"
                     style="grid-template-columns: 1.4fr 1fr 1fr 1fr 1fr 1fr 1fr; border-color: var(--border2)">
                    <div class="sticky-col">{{ __('dashboard.col_tier') }}</div>
                    <div>{{ __('dashboard.col_monthly_revenue') }}</div>
                    <div>{{ __('dashboard.col_monthly_profit') }}</div>
                    <div>{{ __('dashboard.col_quarterly_revenue') }}</div>
                    <div>{{ __('dashboard.col_quarterly_profit') }}</div>
                    <div>{{ __('dashboard.col_yearly_revenue') }}</div>
                    <div>{{ __('dashboard.col_yearly_profit') }}</div>
                </div>

                @foreach ($tiersView as $tier)
                    <div class="grid items-center gap-2.5 border-b py-3 text-[12.5px]"
                         style="grid-template-columns: 1.4fr 1fr 1fr 1fr 1fr 1fr 1fr;
                                border-color: var(--border3); background: {{ $tier['rowBg'] }}">
                        <div class="sticky-col flex items-center gap-2 font-bold"
                             style="background: {{ $tier['rowBg'] === 'transparent' ? 'var(--card-bg)' : $tier['rowBg'] }}">
                            <span class="h-[9px] w-[9px] shrink-0 rounded-sm" style="background: {{ $tier['color'] }}"></span>
                            {{ $tier['name'] }}
                            @if ($tier['isCurrent'])
                                <i class="ph-duotone ph-check-circle text-[15px]" style="color: {{ $tier['color'] }}" aria-hidden="true"></i>
                            @endif
                        </div>
                        <div>{{ $tier['monthlyRevenue'] }}</div>
                        <div>{{ $tier['monthlyProfit'] }}</div>
                        <div>{{ $tier['quarterlyRevenue'] }}</div>
                        <div>{{ $tier['quarterlyProfit'] }}</div>
                        <div>{{ $tier['yearlyRevenue'] }}</div>
                        <div>{{ $tier['yearlyProfit'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ══ BLOK 4: Trend + Keutamaan ══ --}}
    <div class="flex flex-col items-start gap-5 xl:flex-row xl:gap-6">
        <div class="dbena-card w-full p-5 sm:p-6 xl:flex-[2]">
            <h2 class="mb-0.5 text-base font-bold">{{ __('dashboard.sales_target_trend') }}</h2>
            <p class="mb-4 text-[12px] text-t55">{{ __('dashboard.monthly_year', ['year' => $year]) }}</p>

            <x-trend-chart :chart="$dashboardChart" height="320px" />

            <div class="my-6 mb-1 flex items-center gap-2.5">
                <span class="whitespace-nowrap text-[13px] font-bold text-t80">{{ __('dashboard.by_service') }}</span>
                <span class="h-px flex-1" style="background: var(--border)"></span>
            </div>

            <div class="mt-4">
                <x-stacked-bar-chart :bars="$stackBars" :legend="$stackLegend" />
            </div>
        </div>

        <div class="dbena-card flex w-full flex-col p-5 sm:p-6 xl:flex-1">
            <h2 class="mb-4 text-base font-bold">{{ __('dashboard.weekly_priorities') }}</h2>

            <div class="flex flex-1 flex-col gap-3.5">
                @forelse ($priorities as $priority)
                    <a href="{{ $priority->service ? route('service.detail', $priority->service->key) : '#' }}" wire:navigate
                       class="flex gap-3 rounded-[10px] p-2.5 transition-colors hover:bg-hover">
                        <div class="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-[9px]"
                             style="background: oklch(0.55 0.22 350/0.16)">
                            <i class="ph-duotone {{ $priority->icon_class ?? 'ph-target' }} text-[19px]"
                               style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[13px] font-semibold">{{ $priority->title }}</div>
                            <p class="mt-0.5 text-[12px] leading-snug text-t65">{{ $priority->description }}</p>
                        </div>
                        <x-avatar :initials="$priority->ownerInitials()" :size="30" />
                    </a>
                @empty
                    <p class="py-6 text-center text-[12.5px] text-t55">{{ __('app.no_data') }}</p>
                @endforelse
            </div>

            <a href="{{ route('laporan') }}" wire:navigate
               class="mt-3.5 flex items-center gap-1.5 border-t pt-3.5 text-[13px] font-semibold"
               style="border-color: var(--border); color: oklch(0.78 0.12 85)">
                {{ __('dashboard.view_all_actions') }} <i class="ph-duotone ph-caret-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>

    {{-- ══ BLOK 5: Trend Jualan Tahunan ══ --}}
    <div class="dbena-card p-5 sm:p-6">
        <h2 class="mb-0.5 text-base font-bold">{{ __('dashboard.annual_sales_trend') }}</h2>
        <p class="mb-4 text-[12px] text-t55">{{ __('dashboard.annual_projection', ['range' => '2023-2032']) }}</p>
        <x-trend-chart :chart="$yearlyChart" height="320px" />
    </div>
</div>
