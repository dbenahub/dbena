@php
    $summary = $report['summary'];
    $owners = $report['owners'];
@endphp

<div class="flex flex-col gap-5 sm:gap-6">

    {{-- ══ Bar kawalan ══ --}}
    <div class="dbena-card flex flex-wrap items-end gap-3 px-4 py-4 sm:px-5">

        {{-- Tempoh --}}
        <div>
            <label class="mb-1.5 block text-[11.5px] text-t55">{{ __('owner_report.filter_period') }}</label>
            <div class="flex gap-0.5 rounded-[9px] p-[3px]" style="background: var(--hover-bg3)">
                @foreach (\App\Enums\ReportPeriod::cases() as $option)
                    @php $isActive = $periodEnum === $option; @endphp
                    <button type="button" wire:click="$set('period', '{{ $option->value }}')"
                            aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                            class="rounded-[7px] px-3.5 py-2 text-[12.5px] font-semibold transition-colors"
                            @style([
                                'background: oklch(0.78 0.12 85); color: oklch(0.15 0.02 260)' => $isActive,
                                'color: var(--t65)' => ! $isActive,
                            ])>{{ $option->label() }}</button>
                @endforeach
            </div>
        </div>

        {{-- Tahun --}}
        <div>
            <label for="or-year" class="mb-1.5 block text-[11.5px] text-t55">{{ __('dashboard.year') }}</label>
            <select id="or-year" wire:model.live="year" class="dbena-input w-28 py-2.5">
                @foreach ($years as $y)<option value="{{ $y }}">{{ $y }}</option>@endforeach
            </select>
        </div>

        {{-- Bulan (tersembunyi dalam mod tahunan) --}}
        @unless ($periodEnum->isYearly())
            <div>
                <label for="or-month" class="mb-1.5 block text-[11.5px] text-t55">{{ __('sheets.month') }}</label>
                <select id="or-month" wire:model.live="month" class="dbena-input w-36 py-2.5">
                    @foreach ($months as $i => $label)<option value="{{ $i + 1 }}">{{ $label }}</option>@endforeach
                </select>
            </div>
        @endunless

        {{-- Minggu (mod mingguan sahaja) --}}
        @if ($periodEnum->isWeekly())
            <div>
                <label for="or-week" class="mb-1.5 block text-[11.5px] text-t55">{{ __('owner_report.filter_week') }}</label>
                <select id="or-week" wire:model.live="week" class="dbena-input w-28 py-2.5">
                    @foreach (range(1, 4) as $w)<option value="{{ $w }}">{{ __('service.week_n', ['n' => $w]) }}</option>@endforeach
                </select>
            </div>
        @endif

        {{-- Servis --}}
        <div>
            <label for="or-service" class="mb-1.5 block text-[11.5px] text-t55">{{ __('owner_report.filter_service') }}</label>
            <select id="or-service" wire:model.live="serviceKey" class="dbena-input w-44 py-2.5">
                <option value="">{{ __('owner_report.all_services') }}</option>
                @foreach ($services as $service)
                    <option value="{{ $service->key }}">{{ $service->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Pemilik --}}
        <div>
            <label for="or-owner" class="mb-1.5 block text-[11.5px] text-t55">{{ __('owner_report.filter_owner') }}</label>
            <select id="or-owner" wire:model.live="ownerId" class="dbena-input w-44 py-2.5">
                <option value="">{{ __('owner_report.all_owners') }}</option>
                @foreach ($ownerOptions as $o)
                    <option value="{{ $o->id }}">{{ $o->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Eksport. Butang mengikut penapis pemilik yang aktif supaya tiada
             dua kawalan berasingan yang boleh bercanggah — apa yang dilihat
             di skrin adalah apa yang keluar dalam PDF. --}}
        <div class="ml-auto flex flex-col items-end gap-1">
            <a href="{{ route('laporan.owner.pdf', [
                    'tempoh' => $period, 'tahun' => $year, 'bulan' => $month,
                    'minggu' => $week, 'servis' => $serviceKey, 'pemilik' => $ownerId,
               ]) }}"
               target="_blank"
               class="dbena-btn-gold flex items-center gap-2 px-4 py-2.5 text-[13px]">
                <i class="ph-duotone ph-file-pdf" aria-hidden="true"></i>
                @php $terpilih = $ownerId ? $ownerOptions->firstWhere('id', (int) $ownerId) : null; @endphp
                {{ $terpilih
                    ? __('owner_report.export_pdf_owner', ['owner' => $terpilih->name])
                    : __('owner_report.export_pdf') }}
            </a>
            @if ($terpilih)
                <span class="text-[10.5px] text-t50">{{ __('owner_report.export_hint_owner') }}</span>
            @endif
        </div>
    </div>

    @if ($owners->isEmpty())
        <div class="dbena-card px-5 py-12 text-center">
            <i class="ph-duotone ph-users-three text-4xl text-t40" aria-hidden="true"></i>
            <p class="mt-3 text-[14px] font-semibold text-t70">{{ __('owner_report.no_data') }}</p>
            <p class="mt-1.5 text-[12.5px] text-t55">{{ __('owner_report.no_data_hint') }}</p>
        </div>
    @else

    {{-- ══ Ringkasan pasukan ══ --}}
    <div class="dbena-card p-5 sm:p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base font-bold">{{ __('owner_report.summary.title') }}</h2>
            <span class="text-[12px] text-t55">{{ $report['periodLabel'] }}</span>
        </div>

        <p class="mb-5 text-[13px] leading-relaxed text-t75">{{ $summary['headline'] }}</p>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-6">
            @foreach ([
                ['label' => __('owner_report.summary.team_score'), 'value' => $summary['teamScore'].'%', 'color' => $summary['teamScoreColor']],
                ['label' => __('owner_report.summary.owners'), 'value' => $summary['ownerCount'], 'color' => null],
                ['label' => __('owner_report.summary.metrics_tracked'), 'value' => $summary['totalMetrics'], 'color' => null],
                ['label' => __('owner_report.summary.on_track'), 'value' => $summary['totalGreen'], 'color' => 'oklch(0.55 0.15 145)'],
                ['label' => __('owner_report.summary.critical'), 'value' => $summary['totalRed'], 'color' => 'oklch(0.6 0.2 25)'],
                ['label' => __('owner_report.summary.pending'), 'value' => $summary['totalPending'], 'color' => 'var(--t60)'],
            ] as $stat)
                <div class="rounded-xl p-4" style="background: var(--hover-bg3); border: 1px solid var(--border3)">
                    <div class="text-[11.5px] leading-tight text-t60">{{ $stat['label'] }}</div>
                    <div class="mt-1.5 font-display text-[22px] font-extrabold"
                         @if ($stat['color']) style="color: {{ $stat['color'] }}" @endif>
                        {{ $stat['value'] }}
                    </div>
                </div>
            @endforeach
        </div>

        @if ($summary['needsAttention']->isNotEmpty())
            <div class="mt-5 rounded-xl px-4 py-3.5"
                 style="background: oklch(0.6 0.2 25/0.1); border: 1px solid oklch(0.6 0.2 25/0.3)">
                <div class="mb-2 flex items-center gap-2 text-[12.5px] font-bold" style="color: oklch(0.65 0.2 25)">
                    <i class="ph-duotone ph-warning-circle text-base" aria-hidden="true"></i>
                    {{ __('owner_report.summary.needs_attention') }}
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($summary['needsAttention'] as $item)
                        <x-owner-chip :name="$item['name'].' · '.$item['scorePct'].'%'" :color="$item['color']" />
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- ══ Kad setiap PIC ══ --}}
    @foreach ($owners as $block)
        @php $isOpen = $expandedOwner === $block['owner']->id; @endphp

        <div wire:key="owner-{{ $block['owner']->id }}" class="dbena-card overflow-hidden">

            {{-- Header kad --}}
            <button type="button" wire:click="toggleOwner({{ $block['owner']->id }})"
                    :aria-expanded="'{{ $isOpen ? 'true' : 'false' }}'"
                    class="flex w-full flex-wrap items-center gap-4 p-5 text-left transition-colors hover:bg-hover3 sm:p-6">

                {{-- Gred --}}
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl font-display text-2xl font-extrabold"
                     style="background: color-mix(in oklch, {{ $block['scoreColor'] }} 18%, transparent);
                            color: {{ $block['scoreColor'] }};
                            border: 1px solid color-mix(in oklch, {{ $block['scoreColor'] }} 40%, transparent)">
                    {{ $block['grade'] }}
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="font-display text-[17px] font-bold" style="color: {{ $block['color'] }}">
                            {{ $block['name'] }}
                        </span>

                        @if ($block['trend']['available'])
                            @php
                                $dir = $block['trend']['direction'];
                                $trendColor = $dir === 'up' ? 'oklch(0.72 0.15 145)' : ($dir === 'down' ? 'oklch(0.65 0.2 25)' : 'var(--t55)');
                                $trendIcon = $dir === 'up' ? 'ph-trend-up' : ($dir === 'down' ? 'ph-trend-down' : 'ph-minus');
                            @endphp
                            <span class="flex items-center gap-1 text-[11.5px] font-semibold" style="color: {{ $trendColor }}">
                                <i class="ph-duotone {{ $trendIcon }}" aria-hidden="true"></i>
                                {{ $block['trend']['delta'] > 0 ? '+' : '' }}{{ $block['trend']['delta'] }}
                                <span class="font-normal text-t50">{{ __('owner_report.owner.vs_previous') }}</span>
                            </span>
                        @endif
                    </div>

                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[12px]">
                        <span><b style="color: oklch(0.55 0.15 145)">{{ $block['green'] }}</b> <span class="text-t60">{{ __('owner_report.owner.on_track') }}</span></span>
                        <span><b style="color: oklch(0.78 0.15 85)">{{ $block['yellow'] }}</b> <span class="text-t60">{{ __('owner_report.owner.has_plan') }}</span></span>
                        <span><b style="color: oklch(0.6 0.2 25)">{{ $block['red'] }}</b> <span class="text-t60">{{ __('owner_report.owner.no_plan') }}</span></span>
                        @if ($block['pending'] > 0)
                            <span><b class="text-t60">{{ $block['pending'] }}</b> <span class="text-t60">{{ __('owner_report.owner.pending') }}</span></span>
                        @endif
                        <span class="text-t50">/ {{ $block['total'] }} {{ __('owner_report.owner.metrics') }}</span>
                    </div>
                </div>

                <div class="w-full shrink-0 sm:w-52">
                    <x-progress-bar :pct="$block['scorePct']" :color="$block['scoreColor']" height="8px" :decimals="0" label-width="42px" />
                </div>

                <i class="ph-duotone ph-caret-down shrink-0 text-t55 transition-transform {{ $isOpen ? 'rotate-180' : '' }}"
                   aria-hidden="true"></i>
            </button>

            @if ($isOpen)
                <div class="border-t px-5 pb-6 pt-5 sm:px-6" style="border-color: var(--border3)">

                    {{-- Ulasan --}}
                    <h3 class="mb-3 flex items-center gap-2 text-[13.5px] font-bold">
                        <i class="ph-duotone ph-chat-text" style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
                        {{ __('owner_report.commentary_title') }}
                    </h3>
                    <div class="mb-6 flex flex-col gap-2.5">
                        @foreach ($block['commentary'] as $line)
                            <p class="rounded-lg px-3.5 py-2.5 text-[12.5px] leading-relaxed text-t75"
                               style="background: var(--hover-bg3)">{{ $line }}</p>
                        @endforeach
                    </div>

                    {{-- ══ ANALISIS PUNCA ══ --}}
                    @if ($block['diagnoses']->isNotEmpty())
                        <h3 class="mb-1 flex items-center gap-2 text-[13.5px] font-bold">
                            <i class="ph-duotone ph-tree-structure" style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
                            {{ __('funnel.title') }}
                        </h3>
                        <p class="mb-3 text-[12px] text-t55">{{ __('funnel.subtitle') }}</p>

                        <div class="mb-6 flex flex-col gap-3">
                            @foreach ($block['diagnoses'] as $d)
                                @php
                                    $dColor = $d['severity'] === 'critical' ? 'oklch(0.6 0.2 25)' : 'oklch(0.78 0.15 85)';
                                @endphp

                                <div class="rounded-xl p-4"
                                     style="background: var(--hover-bg3);
                                            border: 1px solid var(--border3);
                                            border-left: 3px solid {{ $dColor }}">

                                    {{-- Metrik & jurang --}}
                                    <div class="mb-2.5 flex flex-wrap items-baseline justify-between gap-2">
                                        <span class="text-[13px] font-bold" style="color: {{ $dColor }}">{{ $d['label'] }}</span>
                                        <span class="text-[11.5px] text-t60">
                                            {{ $d['actualLabel'] }} / {{ $d['targetLabel'] }}
                                            @if ($d['gapLabel'] !== '—')
                                                · {{ __('service.gap_note', ['amount' => $d['gapLabel']]) }}
                                            @endif
                                        </span>
                                    </div>

                                    {{-- Poin ringkas. Bahagian berlabel di bawah
                                         sudah memberi butiran; perenggan penuh di
                                         sini hanya mengulanginya dalam bentuk yang
                                         lebih sukar diimbas. --}}
                                    <ul class="mb-3 flex flex-col gap-1">
                                        @foreach ($d['points'] as $pt)
                                            <li class="flex gap-2 text-[12.5px] leading-snug text-t80">
                                                <span class="mt-[6px] h-1 w-1 shrink-0 rounded-full"
                                                      style="background: {{ $dColor }}"></span>
                                                <span>{{ $pt['text'] }}</span>
                                            </li>
                                        @endforeach
                                    </ul>

                                    {{-- Punca berlabel --}}
                                    @if (! empty($d['causes']))
                                        <div class="mb-3">
                                            <div class="mb-1.5 text-[11px] font-bold uppercase tracking-wide text-t55">
                                                {{ __('funnel.root_cause') }}
                                            </div>
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach ($d['causes'] as $cause)
                                                    <span class="rounded-md px-2 py-1 text-[11px]"
                                                          style="background: color-mix(in oklch, {{ $dColor }} 12%, transparent); color: {{ $dColor }}">
                                                        {{ __('funnel.cause.'.$cause['type']) }}
                                                        @if ($cause['pct'] !== null && in_array($cause['type'], ['driver_failed', 'conversion'], true))
                                                            · {{ $cause['label'] }} {{ number_format((float) $cause['pct'], 0) }}%
                                                        @elseif (in_array($cause['type'], ['driver_zero', 'driver_no_data'], true))
                                                            · {{ $cause['label'] }}
                                                        @endif
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Kesan hilir ke atas syarikat --}}
                                    @if (! empty($d['impacts']))
                                        <div class="mb-3 rounded-lg px-3 py-2.5"
                                             style="background: oklch(0.6 0.2 25/0.08); border: 1px solid oklch(0.6 0.2 25/0.25)">
                                            <div class="mb-1.5 flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wide"
                                                 style="color: oklch(0.65 0.2 25)">
                                                <i class="ph-duotone ph-arrow-bend-right-down" aria-hidden="true"></i>
                                                {{ __('funnel.downstream_impact') }}
                                            </div>
                                            @foreach ($d['impacts'] as $imp)
                                                <div class="text-[12px] leading-relaxed text-t75">
                                                    • {{ $imp['label'] }} —
                                                    <span class="text-t60">{{ $imp['actualLabel'] }} / {{ $imp['targetLabel'] }}</span>
                                                    @if ($imp['pct'] !== null)
                                                        <b>({{ number_format((float) $imp['pct'], 1) }}%)</b>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- Tindakan berkuantiti --}}
                                    @if (! empty($d['actions']))
                                        <div class="mb-1.5 text-[11px] font-bold uppercase tracking-wide text-t55">
                                            {{ __('funnel.required_action') }}
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            @foreach ($d['actions'] as $act)
                                                @php
                                                    $aColor = $act['priority'] === 'high' ? 'oklch(0.6 0.2 25)'
                                                        : ($act['priority'] === 'medium' ? 'oklch(0.78 0.15 85)' : 'oklch(0.55 0.15 145)');
                                                @endphp
                                                <div class="rounded-lg px-3 py-2.5" style="background: var(--card-bg)">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase"
                                                              style="background: color-mix(in oklch, {{ $aColor }} 16%, transparent); color: {{ $aColor }}">
                                                            {{ __('owner_report.priority.'.$act['priority']) }}
                                                        </span>
                                                        <span class="text-[12.5px] font-semibold text-t90">{{ $act['label'] }}</span>
                                                    </div>
                                                    <p class="mt-1.5 text-[12px] leading-relaxed text-t65">{{ $act['detail'] }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Tindakan umum --}}
                    <h3 class="mb-3 flex items-center gap-2 text-[13.5px] font-bold">
                        <i class="ph-duotone ph-list-checks" style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
                        {{ __('owner_report.actions_title') }}
                    </h3>
                    <div class="mb-6 flex flex-col gap-2.5">
                        @foreach ($block['actions'] as $action)
                            @php
                                $pColor = match ($action['priority']) {
                                    'high' => 'oklch(0.6 0.2 25)',
                                    'medium' => 'oklch(0.78 0.15 85)',
                                    default => 'oklch(0.55 0.15 145)',
                                };
                            @endphp
                            <div class="flex gap-3 rounded-lg px-3.5 py-3"
                                 style="background: var(--hover-bg3); border-left: 3px solid {{ $pColor }}">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded px-1.5 py-0.5 text-[10.5px] font-bold uppercase"
                                              style="background: color-mix(in oklch, {{ $pColor }} 18%, transparent); color: {{ $pColor }}">
                                            {{ __('owner_report.priority.'.$action['priority']) }}
                                        </span>
                                        <span class="text-[12.5px] font-semibold text-t90">{{ $action['label'] }}</span>
                                    </div>
                                    <p class="mt-1.5 text-[12px] leading-relaxed text-t65">{{ $action['detail'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Perincian metrik --}}
                    <h3 class="mb-3 flex items-center gap-2 text-[13.5px] font-bold">
                        <i class="ph-duotone ph-table" style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
                        {{ __('owner_report.metrics_title') }}
                    </h3>

                    {{-- Desktop --}}
                    <div class="hidden overflow-x-auto md:block">
                        <div class="min-w-[720px]">
                            <div class="grid gap-3 border-b pb-2.5 text-[11px] font-bold uppercase text-t60"
                                 style="grid-template-columns: 2fr 1.2fr 1fr 1fr 1.3fr 1.2fr; border-color: var(--border2)">
                                <div>{{ __('owner_report.col.metric') }}</div>
                                <div>{{ __('owner_report.col.service') }}</div>
                                <div>{{ __('owner_report.col.actual') }}</div>
                                <div>{{ __('owner_report.col.target') }}</div>
                                <div>{{ __('owner_report.col.achievement') }}</div>
                                <div>{{ __('owner_report.col.status') }}</div>
                            </div>

                            @foreach ($block['metrics'] as $row)
                                <div class="grid items-center gap-3 border-b py-2.5 text-[12.5px]"
                                     style="grid-template-columns: 2fr 1.2fr 1fr 1fr 1.3fr 1.2fr; border-color: var(--border3)">
                                    <div class="font-semibold">{{ $row['label'] }}</div>
                                    <div class="text-t65">{{ $row['service'] }}</div>
                                    <div>{{ $row['actualLabel'] }}</div>
                                    <div class="text-t70">{{ $row['targetLabel'] }}</div>
                                    <div>
                                        @if ($row['pct'] !== null)
                                            <x-progress-bar :pct="$row['pct']" :color="$row['statusColor']" height="6px" label-width="42px" />
                                        @else
                                            <span class="text-t40">—</span>
                                        @endif
                                    </div>
                                    <x-status-dot :color="$row['statusColor']" :label="$row['statusLabel']" size="7px" />
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Mobile --}}
                    <div class="flex flex-col gap-2.5 md:hidden">
                        @foreach ($block['metrics'] as $row)
                            <div class="rounded-lg p-3" style="background: var(--hover-bg3); border: 1px solid var(--border3)">
                                <div class="mb-1.5 flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="text-[12.5px] font-semibold">{{ $row['label'] }}</div>
                                        <div class="mt-0.5 text-[11px] text-t55">{{ $row['service'] }}</div>
                                    </div>
                                    <x-status-dot :color="$row['statusColor']" size="8px" />
                                </div>
                                <div class="mb-2 flex flex-wrap gap-x-3 text-[11.5px] text-t65">
                                    <span>{{ __('owner_report.col.actual') }}: <b class="text-t85">{{ $row['actualLabel'] }}</b></span>
                                    <span>{{ __('owner_report.col.target') }}: <b class="text-t85">{{ $row['targetLabel'] }}</b></span>
                                </div>
                                @if ($row['pct'] !== null)
                                    <x-progress-bar :pct="$row['pct']" :color="$row['statusColor']" height="6px" />
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endforeach
    @endif
</div>
