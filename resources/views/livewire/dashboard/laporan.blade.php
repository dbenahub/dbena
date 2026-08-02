@php $monthName = $monthsFull[$month - 1]; @endphp

<div class="flex flex-col gap-5 sm:gap-6">

    {{-- ══ Bar kawalan ══ --}}
    <div class="dbena-card flex flex-wrap items-center gap-3 px-4 py-3.5 sm:px-5">

        {{-- Tarikh --}}
        <div x-data="{ open: false }" class="relative" x-on:click.outside="open = false">
            <button type="button" x-on:click="open = !open" :aria-expanded="open.toString()"
                    class="flex items-center gap-2 rounded-[10px] px-3.5 py-2.5 text-[13px] transition-colors hover:bg-hover3"
                    style="border: 1px solid var(--border2)">
                <i class="ph-duotone ph-calendar-blank" style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
                {{ $monthName }} {{ $year }}
                <i class="ph-duotone ph-caret-down text-[13px]" aria-hidden="true"></i>
            </button>
            <div x-show="open" x-cloak x-transition
                 class="absolute left-0 top-[46px] z-30 max-h-[320px] w-[200px] overflow-y-auto rounded-xl bg-card p-2 shadow-2xl"
                 style="border: 1px solid var(--border2)">
                <div class="mb-2 flex flex-wrap gap-1 border-b pb-2" style="border-color: var(--border3)">
                    @foreach ($years as $y)
                        <button type="button" wire:click="selectYear({{ $y }})"
                                class="rounded px-2 py-1 text-[11.5px] font-bold"
                                @style([
                                    'background: oklch(0.78 0.12 85); color: oklch(0.15 0.02 260)' => $y === $year,
                                    'color: var(--t60)' => $y !== $year,
                                ])>{{ $y }}</button>
                    @endforeach
                </div>
                @foreach ($monthsFull as $i => $label)
                    <button type="button" wire:click="selectMonth({{ $i + 1 }})" x-on:click="open = false"
                            class="w-full rounded-lg px-3 py-2.5 text-left text-[13px] transition-colors hover:bg-hover2"
                            @style(['background: var(--hover-bg3); font-weight: 700' => ($i + 1) === $month])>
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Penapis servis --}}
        <div x-data="{ open: false }" class="relative" x-on:click.outside="open = false">
            <button type="button" x-on:click="open = !open" :aria-expanded="open.toString()"
                    class="flex items-center gap-2 rounded-[10px] px-3.5 py-2.5 text-[13px] transition-colors hover:bg-hover3"
                    style="border: 1px solid var(--border2)">
                <i class="ph-duotone ph-funnel" style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
                {{ $selected?->name ?? __('laporan.all_services') }}
                <i class="ph-duotone ph-caret-down text-[13px]" aria-hidden="true"></i>
            </button>
            <div x-show="open" x-cloak x-transition
                 class="absolute left-0 top-[46px] z-30 w-[220px] overflow-hidden rounded-xl bg-card shadow-2xl"
                 style="border: 1px solid var(--border2)">
                <button type="button" wire:click="selectService(null)" x-on:click="open = false"
                        class="w-full px-4 py-2.5 text-left text-[13px] text-t90 transition-colors hover:bg-hover2"
                        @style(['background: var(--hover-bg3)' => ! $selected])>
                    {{ __('laporan.all_services') }}
                </button>
                @foreach ($allServices as $service)
                    <button type="button" wire:click="selectService('{{ $service->key }}')" x-on:click="open = false"
                            class="w-full px-4 py-2.5 text-left text-[13px] text-t90 transition-colors hover:bg-hover2"
                            @style(['background: var(--hover-bg3)' => $selected?->id === $service->id])>
                        {{ $service->name }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Pemilih tempoh. Mingguan, bulanan dan tahunan menjawab soalan
             yang berbeza: mingguan untuk mesyuarat, bulanan untuk prestasi,
             tahunan untuk arah. Satu tempoh tetap memaksa pembaca mengira
             sendiri dua yang lain. --}}
        <div class="flex items-center gap-1 rounded-[10px] p-1" style="background: var(--hover-bg2)">
            @foreach (\App\Enums\ReportPeriod::cases() as $p)
                <button type="button" wire:click="selectPeriod('{{ $p->value }}')"
                        class="rounded-[7px] px-3 py-1.5 text-[12px] font-bold transition-colors"
                        style="{{ $period === $p->value
                            ? 'background: oklch(0.30 0.13 350); color: #fff'
                            : 'color: var(--t65)' }}">
                    {{ $p->label() }}
                </button>
            @endforeach
        </div>

        @if ($period === 'weekly')
            <select wire:model.live="week" class="dbena-input w-[110px] text-[12.5px]"
                    aria-label="{{ __('report.week_label') }}">
                @foreach (range(1, 4) as $w)
                    <option value="{{ $w }}">{{ __('report.week_label') }} {{ $w }}</option>
                @endforeach
            </select>
        @endif

        <div class="ml-auto flex flex-wrap items-center gap-2">
            {{-- PDF ialah butang utama. CSV kekal kerana data mentah masih
                 diperlukan untuk kerja lanjutan, tetapi ia bukan lagi
                 satu-satunya pilihan — dan ia bukan laporan. --}}
            <a href="{{ route('laporan.pdf', [
                    'tempoh' => $period, 'tahun' => $year, 'bulan' => $month,
                    'minggu' => $week, 'servis' => $serviceKey,
               ]) }}"
               class="dbena-btn-gold flex items-center gap-2 px-4 py-2.5 text-[13px]">
                <i class="ph-duotone ph-file-pdf" aria-hidden="true"></i> {{ __('report.export_pdf') }}
            </a>

            <a href="{{ route('laporan.export', ['tahun' => $year, 'bulan' => $month, 'servis' => $serviceKey]) }}"
               class="flex items-center gap-2 rounded-[10px] px-3.5 py-2.5 text-[12.5px] font-semibold text-t80"
               style="border: 1px solid var(--border2)">
                <i class="ph-duotone ph-file-csv" aria-hidden="true"></i> {{ __('report.export_csv') }}
            </a>
        </div>
    </div>

    {{-- ══ 4 kad KPI ══ --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 xl:grid-cols-4">
        @foreach ([
            ['label' => __('laporan.total_revenue'), 'value' => $totalRevenueLabel, 'hint' => null],
            ['label' => __('laporan.total_quotations'), 'value' => $totalQuotationLabel, 'hint' => null],
            ['label' => __('laporan.conversion_rate'), 'value' => $conversionRateLabel, 'hint' => __('laporan.conversion_hint')],
            ['label' => __('laporan.avg_quotation_value'), 'value' => $avgQuotationLabel, 'hint' => __('laporan.avg_quotation_hint')],
        ] as $kpi)
            <div class="dbena-card p-5">
                <div class="text-[12.5px] text-t60">{{ $kpi['label'] }}</div>
                <div class="mt-2 font-display text-[22px] font-extrabold">{{ $kpi['value'] }}</div>
                @if ($kpi['hint'])
                    <div class="mt-1.5 text-[11px] italic text-t50">{{ $kpi['hint'] }}</div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- ══ Carta Trend Keseluruhan ══ --}}
    <div class="dbena-card p-5 sm:p-6">
        <h2 class="mb-4 text-base font-bold">{{ __('laporan.overall_trend') }}</h2>
        <x-trend-chart :chart="$reportChart" height="320px" />
    </div>

    {{-- ══ Pecahan Mengikut Servis ══ --}}
    <div class="dbena-card p-5 sm:p-6">
        <h2 class="mb-5 text-base font-bold">{{ __('laporan.breakdown_by_service') }}</h2>

        {{-- Desktop --}}
        <div class="hidden md:block">
            <div class="grid gap-4 border-b pb-3 text-[11.5px] text-t55"
                 style="grid-template-columns: 2fr 1.3fr 1.3fr 1.8fr 1.4fr; border-color: var(--border)">
                <div>{{ __('laporan.col_service') }}</div>
                <div>{{ __('laporan.col_sales') }}</div>
                <div>{{ __('laporan.col_target') }}</div>
                <div>{{ __('laporan.col_achievement') }}</div>
                <div>{{ __('laporan.col_status') }}</div>
            </div>

            @foreach ($rows as $row)
                <div class="grid items-center gap-4 border-b py-3.5 text-[13px]"
                     style="grid-template-columns: 2fr 1.3fr 1.3fr 1.8fr 1.4fr; border-color: var(--border3)">
                    <div class="flex items-center gap-2.5 font-semibold">
                        <i class="ph-duotone {{ $row['icon'] }} text-[17px]" style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
                        {{ $row['name'] }}
                    </div>
                    <div class="font-semibold">{{ $row['salesLabel'] }}</div>
                    <div class="text-t70">{{ $row['targetLabel'] }}</div>
                    <div><x-progress-bar :pct="$row['pct']" :color="$row['barColor']" label-width="46px" /></div>
                    <x-status-dot :color="$row['statusColor']" :label="$row['statusLabel']" size="7px" />
                </div>
            @endforeach
        </div>

        {{-- Mobile --}}
        <div class="flex flex-col gap-3 md:hidden">
            @foreach ($rows as $row)
                <div class="rounded-xl p-4" style="background: var(--hover-bg3); border: 1px solid var(--border3)">
                    <div class="mb-3 flex items-center gap-2.5">
                        <i class="ph-duotone {{ $row['icon'] }} text-lg" style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
                        <span class="flex-1 text-sm font-semibold">{{ $row['name'] }}</span>
                        <x-status-dot :color="$row['statusColor']" size="8px" />
                    </div>
                    <div class="mb-2.5 grid grid-cols-2 gap-2 text-[12px]">
                        <div>
                            <div class="text-t55">{{ __('laporan.col_sales') }}</div>
                            <div class="mt-0.5 font-semibold">{{ $row['salesLabel'] }}</div>
                        </div>
                        <div>
                            <div class="text-t55">{{ __('laporan.col_target') }}</div>
                            <div class="mt-0.5 text-t70">{{ $row['targetLabel'] }}</div>
                        </div>
                    </div>
                    <x-progress-bar :pct="$row['pct']" :color="$row['barColor']" />
                </div>
            @endforeach
        </div>
    </div>
</div>
