{{-- Jadual Data Kritikal Mingguan — 11 lajur.
     Desktop: grid min-width 1360px dengan lajur pertama MELEKAT.
     Mobile : senarai kad boleh-kembang setiap metrik. --}}
@if ($rows->isNotEmpty())
<div class="dbena-card p-5 sm:p-6">

    {{-- Header + kawalan --}}
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <h2 class="text-base font-bold">{{ __('service.critical_data') }}</h2>
            <p class="mt-0.5 text-[12px] text-t55">{{ __('service.critical_data_hint') }}</p>

            {{-- Legend status --}}
            <div class="mt-2.5 flex flex-wrap gap-x-3.5 gap-y-1.5">
                @foreach ([
                    ['oklch(0.55 0.15 145)', __('service.legend_green')],
                    ['oklch(0.78 0.15 85)', __('service.legend_yellow')],
                    ['oklch(0.55 0.2 25)', __('service.legend_red')],
                ] as [$color, $text])
                    <span class="flex items-center gap-1.5 text-[11px] text-t65">
                        <span class="h-2 w-2 rounded-full" style="background: {{ $color }}"></span>{{ $text }}
                    </span>
                @endforeach
            </div>

            {{-- Chip penapis aktif --}}
            @if ($ownerFilter)
                @php $filtered = $owners->firstWhere('id', $ownerFilter); @endphp
                <button type="button" wire:click="clearOwnerFilter"
                        class="mt-2.5 inline-flex w-fit items-center gap-1.5 rounded-[20px] px-3 py-1.5 text-[11.5px] font-semibold"
                        style="background: oklch(0.78 0.12 85/0.15); border: 1px solid oklch(0.78 0.12 85/0.5); color: oklch(0.78 0.12 85)">
                    {{ __('service.filter_by') }}: {{ $filtered?->name }}
                    <i class="ph-duotone ph-x text-xs" aria-hidden="true"></i>
                </button>
            @endif
        </div>

        <div class="flex flex-wrap gap-2">
            {{-- Tambah PIC & Raw Data dialih keluar daripada Dashboard Pengguna.
                 PIC diuruskan di Admin Panel; Raw Data ialah paparan diagnostik. --}}
            @can('access-admin-panel')
                <button type="button" x-on:click="showAddOwner = true"
                        class="flex items-center gap-1.5 rounded-[9px] px-3.5 py-2.5 text-[12.5px] font-semibold text-t80"
                        style="border: 1px solid var(--border2)">
                    <i class="ph-duotone ph-user-plus text-base" aria-hidden="true"></i> {{ __('service.add_owner') }}
                </button>
                <button type="button" x-on:click="showRaw = true"
                        class="flex items-center gap-1.5 rounded-[9px] px-3.5 py-2.5 text-[12.5px] font-semibold text-t80"
                        style="border: 1px solid var(--border2)">
                    <i class="ph-duotone ph-code text-base" aria-hidden="true"></i> {{ __('service.view_raw_data') }}
                </button>
            @endcan
            <button type="button" x-on:click="showSheet = true"
                    class="dbena-btn-gold flex items-center gap-1.5 px-3.5 py-2.5 text-[12.5px]">
                <i class="ph-duotone ph-google-logo text-base" aria-hidden="true"></i>
                {{ $sheet?->connected ? __('service.google_sheet_connected') : __('service.google_sheet') }}
            </button>
        </div>
    </div>

    {{-- ══ DESKTOP: grid 11 lajur, skrol mendatar, lajur 1 melekat ══ --}}
    @php $grid = 'grid-template-columns: 250px 95px 95px 95px 95px 80px 115px 140px 90px 130px minmax(200px, 1fr);'; @endphp

    <div class="sticky-col-table hidden overflow-x-auto lg:block">
        <div class="min-w-[1385px]">
            <div class="grid gap-2.5 border-b-2 pb-3 text-[11px] font-bold uppercase tracking-wide text-t60"
                 style="{{ $grid }} border-color: var(--border2)">
                <div class="sticky-col">{{ __('service.col_metric') }}</div>
                @foreach ($weekHeaders as $header)
                    <div class="leading-tight whitespace-pre-line">{{ $header }}</div>
                @endforeach
                <div>{{ __('service.col_type') }}</div>
                <div>{{ __('service.col_actual') }}</div>
                <div>{{ __('service.col_target') }}</div>
                <div>{{ __('service.col_status') }}</div>
                <div>{{ __('service.col_owner') }}</div>
                <div>{{ __('service.col_action_plan') }}</div>
            </div>

            @foreach ($displayRows as $row)
                <div wire:key="row-{{ $row['id'] }}"
                     class="grid items-center gap-2.5 border-b py-3 text-[12.5px]"
                     style="{{ $grid }} border-color: var(--border3)">

                    <div class="sticky-col pr-2 font-semibold">{{ $row['label'] }}</div>

                    {{-- Minggu 1-4 — wire:model.blur elak round-trip setiap ketukan --}}
                    @for ($w = 1; $w <= 4; $w++)
                        <input type="text" inputmode="decimal" placeholder="—"
                               wire:model.blur="weekValues.{{ $row['id'] }}.{{ $w }}"
                               wire:change="saveWeekValue({{ $row['id'] }}, {{ $w }})"
                               aria-label="{{ $row['label'] }} — {{ __('service.week_n', ['n' => $w]) }}"
                               class="w-full rounded-[7px] bg-transparent px-2 py-1.5 text-center text-[12px] text-t85 focus:outline-none"
                               style="border: 1px solid var(--border2)">
                    @endfor

                    <div class="text-t65">{{ $row['type']->label() }}</div>
                    <div class="font-bold text-t90">{{ $row['actualLabel'] }}</div>

                    {{-- SASARAN — read-only untuk user, boleh-edit untuk admin --}}
                    <div>
                        @if ($canEditTarget && $row['targetIsNumeric'])
                            <input type="text" inputmode="decimal" value="{{ $row['target'] }}"
                                   wire:change="updateTarget({{ $row['id'] }}, $event.target.value)"
                                   aria-label="{{ $row['label'] }} — {{ __('service.col_target') }}"
                                   class="w-full rounded-[7px] bg-transparent px-2 py-1.5 text-[12px] font-semibold focus:outline-none"
                                   style="border: 1px solid var(--border2); color: oklch(0.72 0.15 145)">
                        @else
                            <span class="font-semibold" style="color: oklch(0.72 0.15 145)"
                                  title="{{ __('service.target_admin_only') }}">{{ $row['targetLabel'] }}</span>
                        @endif
                    </div>

                    <x-status-dot :color="$row['statusColor']" :label="$row['statusLabel']" size="7px" />

                    {{-- Pemilik/PIC — warna dari satu sumber (owners.color_token).
                         Paparan sahaja untuk pengguna: nama datang daripada lajur
                         DATA OWNER dalam Google Sheet. --}}
                    @can('assignOwner', App\Models\CriticalMetric::class)
                        <select wire:model="rowOwners.{{ $row['id'] }}"
                                wire:change="saveRowOwner({{ $row['id'] }})"
                                aria-label="{{ $row['label'] }} — {{ __('service.col_owner') }}"
                                class="w-full cursor-pointer rounded-md px-2 py-1.5 text-[11px] font-bold focus:outline-none"
                                style="background: color-mix(in oklch, {{ $row['ownerColor'] }} 18%, transparent);
                                       color: {{ $row['ownerColor'] }};
                                       border: 1px solid color-mix(in oklch, {{ $row['ownerColor'] }} 40%, transparent)">
                            <option value="">—</option>
                            @foreach ($owners as $owner)
                                <option value="{{ $owner->id }}">{{ $owner->name }}</option>
                            @endforeach
                        </select>
                    @else
                        <div class="w-full truncate rounded-md px-2 py-1.5 text-[11px] font-bold"
                             title="{{ $row['ownerName'] }}"
                             style="background: color-mix(in oklch, {{ $row['ownerColor'] }} 14%, transparent);
                                    color: {{ $row['ownerColor'] }}">
                            {{ $row['ownerName'] }}
                        </div>
                    @endcan

                    <input type="text" wire:model.blur="rowPlans.{{ $row['id'] }}"
                           wire:change="saveRowPlan({{ $row['id'] }})"
                           placeholder="{{ __('service.action_plan_placeholder') }}"
                           aria-label="{{ $row['label'] }} — {{ __('service.col_action_plan') }}"
                           class="w-full rounded-[7px] bg-transparent px-2.5 py-1.5 text-[12px] text-t85 focus:outline-none"
                           style="border: 1px solid var(--border2)">
                </div>
            @endforeach
        </div>
    </div>

    {{-- ══ MOBILE: kad boleh-kembang setiap metrik ══ --}}
    <div class="flex flex-col gap-3 lg:hidden">
        @foreach ($displayRows as $row)
            <div wire:key="mrow-{{ $row['id'] }}" x-data="{ expanded: false }"
                 class="rounded-xl p-3.5"
                 style="background: var(--hover-bg3); border: 1px solid var(--border3)">

                <button type="button" x-on:click="expanded = !expanded" :aria-expanded="expanded.toString()"
                        class="flex w-full items-start justify-between gap-3 text-left">
                    <div class="min-w-0 flex-1">
                        <div class="text-[13px] font-semibold">{{ $row['label'] }}</div>
                        <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[12px]">
                            <x-status-dot :color="$row['statusColor']" :label="$row['statusLabel']" size="7px" />
                            <span class="text-t60">{{ __('service.col_actual') }}: <b class="text-t90">{{ $row['actualLabel'] }}</b></span>
                            <span class="text-t60">{{ __('service.col_target') }}:
                                <b style="color: oklch(0.72 0.15 145)">{{ $row['targetLabel'] }}</b>
                            </span>
                        </div>
                    </div>
                    <i class="ph-duotone ph-caret-down shrink-0 text-t55 transition-transform"
                       :class="expanded && 'rotate-180'" aria-hidden="true"></i>
                </button>

                <div x-show="expanded" x-cloak x-collapse class="mt-3.5 border-t pt-3.5" style="border-color: var(--border3)">
                    <div class="mb-3 grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                        @for ($w = 1; $w <= 4; $w++)
                            <div>
                                <label class="mb-1 block text-[10.5px] leading-tight whitespace-pre-line text-t55">{{ $weekHeaders[$w - 1] }}</label>
                                <input type="text" inputmode="decimal" placeholder="—"
                                       wire:model.blur="weekValues.{{ $row['id'] }}.{{ $w }}"
                                       wire:change="saveWeekValue({{ $row['id'] }}, {{ $w }})"
                                       class="touch-target w-full rounded-lg bg-transparent px-2 py-2 text-center text-[12.5px] text-t85 focus:outline-none"
                                       style="border: 1px solid var(--border2)">
                            </div>
                        @endfor
                    </div>

                    <div class="mb-3">
                        <label class="mb-1 block text-[11px] text-t55">{{ __('service.col_owner') }}</label>
                        @can('assignOwner', App\Models\CriticalMetric::class)
                            <select wire:model="rowOwners.{{ $row['id'] }}" wire:change="saveRowOwner({{ $row['id'] }})"
                                    class="touch-target w-full rounded-lg px-2.5 py-2 text-[12.5px] font-bold focus:outline-none"
                                    style="background: color-mix(in oklch, {{ $row['ownerColor'] }} 18%, transparent);
                                           color: {{ $row['ownerColor'] }};
                                           border: 1px solid color-mix(in oklch, {{ $row['ownerColor'] }} 40%, transparent)">
                                <option value="">—</option>
                                @foreach ($owners as $owner)
                                    <option value="{{ $owner->id }}">{{ $owner->name }}</option>
                                @endforeach
                            </select>
                        @else
                            <div class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-[12.5px] font-bold"
                                 style="background: color-mix(in oklch, {{ $row['ownerColor'] }} 14%, transparent);
                                        color: {{ $row['ownerColor'] }}">
                                {{ $row['ownerName'] }}
                            </div>
                        @endcan
                    </div>

                    <div>
                        <label class="mb-1 block text-[11px] text-t55">{{ __('service.col_action_plan') }}</label>
                        <textarea wire:model.blur="rowPlans.{{ $row['id'] }}" wire:change="saveRowPlan({{ $row['id'] }})"
                                  rows="2" placeholder="{{ __('service.action_plan_placeholder') }}"
                                  class="w-full rounded-lg bg-transparent px-2.5 py-2 text-[12.5px] text-t85 focus:outline-none"
                                  style="border: 1px solid var(--border2)"></textarea>
                    </div>

                    @unless ($canEditTarget)
                        <p class="mt-2.5 text-[11px] italic text-t50">{{ __('service.target_readonly') }}</p>
                    @endunless
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif
