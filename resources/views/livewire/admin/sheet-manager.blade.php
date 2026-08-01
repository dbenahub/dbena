<div class="flex flex-col gap-5 sm:gap-6 xl:max-w-6xl">

    {{-- ══ Pemilih servis ══ --}}
    <div class="dbena-card p-5 sm:p-6">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-bold">{{ __('sheets.select_service') }}</h2>
                <p class="mt-0.5 text-[12px] text-t55">{{ __('sheets.select_service_hint') }}</p>
            </div>
            <span class="rounded-md px-2.5 py-1.5 text-[11.5px] font-semibold"
                  style="background: var(--hover-bg3); color: var(--t65)">
                {{ __('sheets.driver_label') }}: <b class="text-t85">{{ $driverLabel }}</b>
            </span>
        </div>

        <div class="flex flex-wrap gap-2">
            @php $isGlobal = $selectedServiceId === null; @endphp
            <button type="button" wire:click="$set('selectedServiceId', null)"
                    aria-pressed="{{ $isGlobal ? 'true' : 'false' }}"
                    class="flex items-center gap-2 rounded-[10px] px-3.5 py-2.5 text-[12.5px] font-semibold transition-colors"
                    @style([
                        'background: oklch(0.78 0.12 85); color: oklch(0.15 0.02 260)' => $isGlobal,
                        'border: 1px solid var(--border2); color: var(--t70)' => ! $isGlobal,
                    ])>
                <i class="ph-duotone ph-stack" aria-hidden="true"></i>
                {{ __('sheets.global_sheet') }}
            </button>

            @foreach ($services as $service)
                @php $isActive = $selectedServiceId === $service->id; @endphp
                <button type="button" wire:click="$set('selectedServiceId', {{ $service->id }})"
                        aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                        class="flex items-center gap-2 rounded-[10px] px-3.5 py-2.5 text-[12.5px] font-semibold transition-colors"
                        @style([
                            'background: oklch(0.78 0.12 85); color: oklch(0.15 0.02 260)' => $isActive,
                            'border: 1px solid var(--border2); color: var(--t70)' => ! $isActive,
                        ])>
                    <i class="ph-duotone {{ $service->icon_class }}" aria-hidden="true"></i>
                    {{ $service->name }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- ══ Sambungan ══ --}}
    <div class="dbena-card p-5 sm:p-6">
        <h2 class="mb-1 text-base font-bold">{{ __('sheets.connection') }}</h2>
        <p class="mb-5 text-[12px] leading-relaxed text-t55">{{ __('sheets.connection_hint') }}</p>

        <div class="flex flex-col gap-4">
            <div>
                <label for="sheet-url" class="mb-1.5 block text-[11.5px] text-t55">{{ __('sheets.url') }}</label>
                <input id="sheet-url" type="url" wire:model="url" class="dbena-input"
                       placeholder="https://docs.google.com/spreadsheets/d/1AbC.../edit#gid=0">
                @if ($integration->spreadsheet_id)
                    <p class="mt-1.5 text-[11.5px]" style="color: oklch(0.55 0.15 145)">
                        <i class="ph-duotone ph-check-circle" aria-hidden="true"></i>
                        ID: <code>{{ Str::limit($integration->spreadsheet_id, 32) }}</code>
                        @if ($integration->gid) · gid: <code>{{ $integration->gid }}</code> @endif
                    </p>
                @endif
            </div>

            {{-- Susun atur sheet --}}
            <div>
                <span class="mb-2 block text-[11.5px] text-t55">{{ __('sheets.layout') }}</span>
                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                    @foreach (['multi', 'single'] as $mode)
                        @php $isMode = $layoutMode === $mode; @endphp
                        <button type="button" wire:click="$set('layoutMode', '{{ $mode }}')"
                                aria-pressed="{{ $isMode ? 'true' : 'false' }}"
                                class="rounded-xl p-3.5 text-left transition-colors"
                                style="border: 1px solid {{ $isMode ? 'oklch(0.78 0.12 85)' : 'var(--border2)' }};
                                       background: {{ $isMode ? 'oklch(0.78 0.12 85/0.08)' : 'transparent' }}">
                            <div class="flex items-center gap-2 text-[12.5px] font-semibold"
                                 style="color: {{ $isMode ? 'oklch(0.78 0.12 85)' : 'var(--t80)' }}">
                                <i class="ph-duotone {{ $mode === 'multi' ? 'ph-stack' : 'ph-file' }}" aria-hidden="true"></i>
                                {{ __('sheets.layout_'.$mode) }}
                            </div>
                            <p class="mt-1.5 text-[11.5px] leading-relaxed text-t60">
                                {{ __('sheets.layout_'.$mode.'_hint') }}
                            </p>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label for="tab-name" class="mb-1.5 block text-[11.5px] text-t55">{{ __('sheets.tab_name') }}</label>
                    <input id="tab-name" type="text" wire:model="tabName" class="dbena-input"
                           placeholder="{{ __('sheets.tab_placeholder') }}">
                </div>
                <div>
                    <label for="header-row" class="mb-1.5 block text-[11.5px] text-t55">
                        {{ __('sheets.header_row') }} <span class="text-t40">· {{ __('sheets.header_auto') }} = 0</span>
                    </label>
                    <input id="header-row" type="number" min="0" wire:model="headerRow" class="dbena-input">
                    <p class="mt-1 text-[11px] text-t50">{{ __('sheets.header_auto_hint') }}</p>
                </div>
                <div>
                    <label for="match-mode" class="mb-1.5 block text-[11.5px] text-t55">{{ __('sheets.match_mode') }}</label>
                    <select id="match-mode" wire:model="matchMode" class="dbena-input">
                        <option value="label">{{ __('sheets.match_label') }}</option>
                        <option value="key">{{ __('sheets.match_key') }}</option>
                    </select>
                </div>
            </div>

            <x-toggle-switch :on="$importTargets" wire:click="$toggle('importTargets')"
                             :label="__('sheets.import_targets')" :sublabel="__('sheets.import_targets_hint')" />

            <x-toggle-switch :on="$syncEnabled" wire:click="$toggle('syncEnabled')"
                             :label="__('sheets.enable_sync')" :sublabel="__('sheets.enable_sync_hint')" />

            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="loadPreview"
                        class="dbena-btn-gold flex items-center gap-2 px-4 py-2.5 text-[13px]"
                        wire:loading.attr="disabled" wire:target="loadPreview">
                    <i class="ph-duotone ph-eye" aria-hidden="true"></i>
                    <span wire:loading.remove wire:target="loadPreview">{{ __('sheets.load_preview') }}</span>
                    <span wire:loading wire:target="loadPreview">{{ __('sheets.reading') }}</span>
                </button>
                <button type="button" wire:click="save"
                        class="rounded-[9px] px-4 py-2.5 text-[13px] font-semibold text-t80"
                        style="border: 1px solid var(--border2)">{{ __('app.save') }}</button>
            </div>

            @if ($previewError)
                <div role="alert" class="flex items-start gap-2.5 rounded-[10px] px-3.5 py-3 text-[12.5px] leading-relaxed"
                     style="background: oklch(0.6 0.2 25/0.12); border: 1px solid oklch(0.6 0.2 25/0.35); color: oklch(0.7 0.18 25)">
                    <i class="ph-duotone ph-warning-circle mt-px shrink-0 text-base" aria-hidden="true"></i>
                    <div>
                        {{ $previewError }}
                        <p class="mt-2 text-t60">{{ __('sheets.error.share_tip') }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ══ Pemetaan lajur ══ --}}
    @if ($preview)
        <div class="dbena-card p-5 sm:p-6">
            <h2 class="mb-1 text-base font-bold">{{ __('sheets.mapping') }}</h2>
            <p class="mb-5 text-[12px] text-t55">
                {{ __('sheets.mapping_hint', ['rows' => $preview['totalRows']]) }}
            </p>

            {{-- ══ Baris mentah - apa yang Google sebenarnya pulangkan ══ --}}
            <div class="mb-5 rounded-xl p-4" style="background: var(--hover-bg3); border: 1px solid var(--border3)">
                <div class="mb-2.5 flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-[12.5px] font-bold">{{ __('sheets.raw_rows') }}</h3>
                    <span class="text-[11.5px] text-t55">
                        {{ __('sheets.widest_row', ['n' => $preview['widestRow'] ?? 0]) }}
                    </span>
                </div>
                <p class="mb-3 text-[11.5px] leading-relaxed text-t55">{{ __('sheets.raw_rows_hint') }}</p>

                <div class="overflow-x-auto">
                    <table class="w-full text-[11px]" style="min-width: 700px">
                        <thead>
                            <tr class="text-t50">
                                <th class="pb-1.5 pr-2 text-left font-bold">#</th>
                                @foreach (array_slice($preview['columnLetters'], 0, 12) as $letter)
                                    <th class="pb-1.5 pr-2 text-left font-bold">{{ $letter }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($preview['rawRows'] ?? [] as $raw)
                                <tr class="border-t" style="border-color: var(--border3)">
                                    <td class="py-1.5 pr-2 font-bold"
                                        style="color: {{ $raw['isHeader'] ? 'oklch(0.78 0.12 85)' : 'var(--t50)' }}">
                                        {{ $raw['number'] }}{{ $raw['isHeader'] ? ' ←' : '' }}
                                    </td>
                                    @for ($i = 0; $i < 12; $i++)
                                        @php $cell = $raw['cells'][$i] ?? ''; @endphp
                                        <td class="max-w-[110px] truncate py-1.5 pr-2"
                                            title="{{ $cell }}"
                                            style="color: {{ $cell === '' ? 'var(--t40)' : 'var(--t85)' }}">
                                            {{ $cell === '' ? '·' : \Illuminate\Support\Str::limit(str_replace("
", ' ⏎ ', $cell), 18) }}
                                        </td>
                                    @endfor
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Header dikesan --}}
            <div class="mb-5 overflow-x-auto">
                <div class="flex gap-1.5 pb-1">
                    @foreach ($preview['headers'] as $i => $header)
                        <div class="min-w-[110px] shrink-0 rounded-lg px-2.5 py-2 text-center"
                             style="background: var(--hover-bg3); border: 1px solid var(--border3)">
                            <div class="font-display text-[11px] font-bold" style="color: oklch(0.78 0.12 85)">
                                {{ $preview['columnLetters'][$i] ?? '?' }}
                            </div>
                            <div class="mt-0.5 truncate text-[11px] text-t70" title="{{ $header }}">
                                {{ $header !== '' ? $header : '—' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Pemilih pemetaan --}}
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ([
                    'metric' => ['label' => __('sheets.field.metric'), 'required' => true],
                    'week1' => ['label' => __('sheets.field.week1'), 'required' => true],
                    'week2' => ['label' => __('sheets.field.week2'), 'required' => true],
                    'week3' => ['label' => __('sheets.field.week3'), 'required' => true],
                    'week4' => ['label' => __('sheets.field.week4'), 'required' => true],
                    'target' => ['label' => __('sheets.field.target'), 'required' => false],
                    'owner' => ['label' => __('sheets.field.owner'), 'required' => false],
                    'action_plan' => ['label' => __('sheets.field.action_plan'), 'required' => false],
                ] as $field => $meta)
                    <div>
                        <label for="map-{{ $field }}" class="mb-1.5 block text-[11px] text-t55">
                            {{ $meta['label'] }}
                            @if ($meta['required'])
                                <span style="color: oklch(0.65 0.2 25)">*</span>
                            @endif
                        </label>
                        <select id="map-{{ $field }}" wire:model.live="columnMap.{{ $field }}"
                                class="w-full rounded-lg px-2 py-2 text-[12.5px] focus:outline-none"
                                style="background: var(--input-bg);
                                       border: 1px solid {{ $meta['required'] && ! $columnMap[$field] ? 'oklch(0.6 0.2 25/0.5)' : 'var(--border2)' }};
                                       color: var(--t90)">
                            <option value="">—</option>
                            @foreach ($preview['columnLetters'] as $i => $letter)
                                <option value="{{ $letter }}">
                                    {{ $letter }}{{ ($preview['headers'][$i] ?? '') !== '' ? ' · '.Str::limit($preview['headers'][$i], 14) : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>

            {{-- Pratonton padanan baris --}}
            {{-- Servis dikesan --}}
            @if ($layoutMode === 'multi')
                <div class="mt-5">
                    <span class="mb-2 block text-[11.5px] text-t55">{{ __('sheets.detected_services') }}</span>
                    @if (! empty($preview['detectedServices']))
                        <div class="flex flex-wrap gap-2">
                            @foreach ($preview['detectedServices'] as $name)
                                <span class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[12px] font-semibold"
                                      style="background: oklch(0.55 0.15 145/0.14); color: oklch(0.6 0.15 145)">
                                    <i class="ph-duotone ph-check-circle" aria-hidden="true"></i>{{ $name }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="rounded-lg px-3.5 py-2.5 text-[12px] leading-relaxed"
                           style="background: oklch(0.78 0.15 85/0.1); border: 1px solid oklch(0.78 0.15 85/0.3); color: oklch(0.8 0.14 85)">
                            {{ __('sheets.no_services_detected') }}
                        </p>
                    @endif

                    @if (! empty($preview['skippedSections']))
                        <div class="mt-3">
                            <span class="mb-2 block text-[11.5px] text-t55">{{ __('sheets.skipped_sections') }}</span>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($preview['skippedSections'] as $name)
                                    <span class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[12px]"
                                          style="background: var(--hover-bg3); color: var(--t60)">
                                        <i class="ph-duotone ph-eye-slash" aria-hidden="true"></i>{{ $name }}
                                    </span>
                                @endforeach
                            </div>
                            <p class="mt-1.5 text-[11.5px] leading-relaxed text-t50">
                                {{ __('sheets.skipped_sections_hint') }}
                            </p>
                        </div>
                    @endif
                </div>
            @endif

            <h3 class="mb-2.5 mt-6 text-[13px] font-bold">
                {{ __('sheets.preview_title') }}
                <span class="ml-1 font-normal text-t50">· {{ __('sheets.header_detected', ['row' => $preview['headerRow']]) }}</span>
            </h3>
            @php $grid = 'grid-template-columns: 28px 1.9fr 1.5fr 0.9fr 0.9fr 0.9fr 0.9fr 1.1fr 1fr;'; @endphp

            <div class="overflow-x-auto">
                <div class="min-w-[860px]">
                    <div class="grid gap-3 border-b pb-2.5 text-[11px] font-bold uppercase text-t60"
                         style="{{ $grid }} border-color: var(--border2)">
                        <div></div>
                        <div>{{ __('sheets.preview_sheet_value') }}</div>
                        <div>{{ __('sheets.preview_matched_to') }}</div>
                        <div>M1</div><div>M2</div><div>M3</div><div>M4</div>
                        <div>{{ __('sheets.col_target') }}</div>
                        <div>{{ __('sheets.col_owner') }}</div>
                    </div>

                    @foreach ($preview['rows'] as $row)
                        @if ($row['type'] === 'skipped')
                            {{-- Bahagian ringkasan (cth. COMPANY PERFORMANCE) - sengaja dilangkau --}}
                            <div class="flex items-center gap-2 border-b px-1 py-2.5 text-[12.5px]"
                                 style="border-color: var(--border3); background: var(--hover-bg3); color: var(--t55)">
                                <i class="ph-duotone ph-eye-slash" aria-hidden="true"></i>
                                <span class="font-semibold">{{ $row['label'] }}</span>
                                <span>· {{ __('sheets.section_skipped') }}</span>
                            </div>
                        @elseif ($row['type'] === 'ignored')
                            <div class="grid items-center gap-3 border-b py-2 text-[12px] opacity-40"
                                 style="{{ $grid }} border-color: var(--border3)">
                                <div><i class="ph-duotone ph-minus text-t50" aria-hidden="true"></i></div>
                                <div class="truncate text-t50">{{ $row['label'] }}</div>
                                <div class="text-t50">{{ __('sheets.row_ignored') }}</div>
                                <div></div><div></div><div></div><div></div><div></div><div></div>
                            </div>
                        @elseif ($row['type'] === 'band')
                            {{-- Baris jalur servis --}}
                            <div class="flex items-center gap-2 border-b px-1 py-2.5 text-[12.5px] font-bold"
                                 style="border-color: var(--border3); background: var(--hover-bg2); color: oklch(0.78 0.12 85)">
                                <i class="ph-duotone ph-folder-open" aria-hidden="true"></i>
                                {{ $row['label'] }}
                                <span class="font-normal text-t50">· {{ __('sheets.band_row') }}</span>
                            </div>
                        @else
                            <div class="grid items-center gap-3 border-b py-2.5 text-[12.5px]"
                                 style="{{ $grid }} border-color: var(--border3)">
                                <div>
                                    @if ($row['matched'])
                                        <i class="ph-duotone ph-check-circle text-base" style="color: oklch(0.55 0.15 145)"
                                           title="{{ __('sheets.matched') }}" aria-hidden="true"></i>
                                    @else
                                        <i class="ph-duotone ph-x-circle text-base" style="color: oklch(0.6 0.2 25)"
                                           title="{{ __('sheets.unmatched') }}" aria-hidden="true"></i>
                                    @endif
                                </div>
                                <div class="truncate {{ $row['matched'] ? '' : 'text-t50' }}">{{ $row['label'] ?: '—' }}</div>
                                <div class="truncate text-t65">{{ $row['matchedTo'] ?? '—' }}</div>
                                @foreach ($row['weeks'] as $value)
                                    <div class="{{ $value === null ? 'text-t40' : 'font-semibold' }}">
                                        {{ $value === null ? '—' : rtrim(rtrim(number_format($value, 2), '0'), '.') }}
                                    </div>
                                @endforeach
                                <div class="truncate text-t65">{{ $row['target'] ?: '—' }}</div>
                                <div class="truncate text-t65">{{ $row['owner'] ?: '—' }}</div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            @php $t = $preview['totals'] ?? ['metrics' => 0, 'matched' => 0, 'unmatched' => 0, 'ignored' => 0]; @endphp

            {{-- Ringkasan meliputi SELURUH sheet, bukan hanya tetingkap paparan --}}
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ([
                    ['label' => __('sheets.total_metric_rows'), 'value' => $t['metrics'], 'color' => null],
                    ['label' => __('sheets.total_matched'), 'value' => $t['matched'], 'color' => 'oklch(0.55 0.15 145)'],
                    ['label' => __('sheets.total_unmatched'), 'value' => $t['unmatched'], 'color' => $t['unmatched'] > 0 ? 'oklch(0.6 0.2 25)' : null],
                    ['label' => __('sheets.total_ignored'), 'value' => $t['ignored'], 'color' => null],
                ] as $stat)
                    <div class="rounded-xl p-3.5" style="background: var(--hover-bg3); border: 1px solid var(--border3)">
                        <div class="text-[11px] leading-tight text-t60">{{ $stat['label'] }}</div>
                        <div class="mt-1 font-display text-[20px] font-extrabold"
                             @if ($stat['color']) style="color: {{ $stat['color'] }}" @endif>{{ $stat['value'] }}</div>
                    </div>
                @endforeach
            </div>

            @if ($t['matched'] > 0)
                <p class="mt-3 rounded-lg px-3.5 py-2.5 text-[12px] leading-relaxed"
                   style="background: oklch(0.55 0.15 145/0.1); border: 1px solid oklch(0.55 0.15 145/0.3); color: oklch(0.6 0.15 145)">
                    <i class="ph-duotone ph-check-circle" aria-hidden="true"></i>
                    {{ __('sheets.matched_hint', ['count' => $t['matched']]) }}
                </p>
            @endif

            @if ($t['unmatched'] > 0)
                <div class="mt-3 rounded-lg px-3.5 py-2.5 text-[12px] leading-relaxed"
                     style="background: oklch(0.6 0.2 25/0.1); border: 1px solid oklch(0.6 0.2 25/0.3); color: oklch(0.68 0.19 25)">
                    <i class="ph-duotone ph-warning-circle" aria-hidden="true"></i>
                    {{ __('sheets.unmatched_hint', ['count' => $t['unmatched']]) }}

                    @if (! empty($preview['unmatchedLabels']))
                        <div class="mt-2 flex flex-col gap-1 text-t70">
                            @foreach (array_slice($preview['unmatchedLabels'], 0, 10) as $label)
                                <div>• {{ $label }}</div>
                            @endforeach
                            @if (count($preview['unmatchedLabels']) > 10)
                                <div class="text-t50">… {{ count($preview['unmatchedLabels']) - 10 }} lagi</div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            @if (($preview['displayed'] ?? 0) < ($t['metrics'] + $t['ignored'] + ($t['bands'] ?? 0)))
                <p class="mt-3 text-[11.5px] italic text-t50">
                    {{ __('sheets.preview_truncated', ['shown' => $preview['displayed']]) }}
                </p>
            @endif
        </div>
    @endif

    {{-- ══ Jalankan sync ══ --}}
    <div class="dbena-card p-5 sm:p-6">
        <h2 class="mb-1 text-base font-bold">{{ __('sheets.run_sync') }}</h2>
        <p class="mb-5 text-[12px] text-t55">
            {{ __('sheets.run_sync_hint', ['minutes' => config('dbena.sheets.sync_interval_minutes')]) }}
        </p>

        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label for="sync-year" class="mb-1.5 block text-[11.5px] text-t55">{{ __('dashboard.year') }}</label>
                <select id="sync-year" wire:model="year" class="dbena-input w-28">
                    @foreach ($years as $y)<option value="{{ $y }}">{{ $y }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="sync-month" class="mb-1.5 block text-[11.5px] text-t55">{{ __('sheets.month') }}</label>
                <select id="sync-month" wire:model="month" class="dbena-input w-40">
                    @foreach ($months as $i => $label)<option value="{{ $i + 1 }}">{{ $label }}</option>@endforeach
                </select>
            </div>

            <button type="button" wire:click="syncNow"
                    class="dbena-btn-gold flex items-center gap-2 px-4 py-3 text-[13px]"
                    wire:loading.attr="disabled" wire:target="syncNow">
                <i class="ph-duotone ph-arrows-clockwise" aria-hidden="true"></i>
                <span wire:loading.remove wire:target="syncNow">{{ __('sheets.sync_now') }}</span>
                <span wire:loading wire:target="syncNow">{{ __('sheets.syncing') }}</span>
            </button>

            <button type="button" wire:click="syncAll"
                    class="rounded-[9px] px-4 py-3 text-[13px] font-semibold text-t80"
                    style="border: 1px solid var(--border2)">{{ __('sheets.sync_all') }}</button>
        </div>

        @if ($integration->last_synced_at)
            <div class="mt-4 flex flex-wrap items-center gap-2 text-[12px]">
                <span class="h-2 w-2 rounded-full" style="background: {{ $integration->statusColor() }}"></span>
                <span class="text-t70">{{ $integration->last_sync_message }}</span>
                <span class="text-t50">· {{ $integration->last_synced_at->diffForHumans() }}</span>
            </div>
        @endif
    </div>

    {{-- ══ Kemas kini masa-nyata melalui Apps Script ══ --}}
    <div class="dbena-card p-5 sm:p-6">
        <div class="mb-1 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base font-bold">{{ __('sheets.realtime') }}</h2>
            <button type="button" wire:click="$toggle('showAppsScript')"
                    class="rounded-[9px] px-3.5 py-2 text-[12.5px] font-semibold text-t80"
                    style="border: 1px solid var(--border2)">
                {{ $showAppsScript ? __('app.close') : __('sheets.show_script') }}
            </button>
        </div>
        <p class="text-[12px] leading-relaxed text-t55">{{ __('sheets.realtime_hint') }}</p>

        @if ($showAppsScript)
            @if (! $integration->service_id)
                <p class="mt-4 text-[12.5px] text-t60">{{ __('sheets.error.no_service') }}</p>
            @else
                <ol class="mt-4 flex list-inside list-decimal flex-col gap-1.5 text-[12.5px] leading-relaxed text-t70">
                    <li>{{ __('sheets.script_step1') }}</li>
                    <li>{{ __('sheets.script_step2') }}</li>
                    <li>{{ __('sheets.script_step3') }}</li>
                </ol>

                <div x-data="{ copied: false }" class="relative mt-3">
                    <button type="button"
                            x-on:click="navigator.clipboard.writeText($refs.script.textContent.trim()); copied = true; setTimeout(() => copied = false, 2000)"
                            class="absolute right-2 top-2 rounded-md px-2.5 py-1.5 text-[11.5px] font-semibold"
                            style="background: var(--hover-bg2); color: var(--t80)">
                        <span x-show="!copied">{{ __('sheets.copy') }}</span>
                        <span x-show="copied" x-cloak style="color: oklch(0.72 0.15 145)">{{ __('sheets.copied') }}</span>
                    </button>
                    <pre x-ref="script" class="overflow-x-auto rounded-[10px] p-4 pr-24 text-[11.5px] leading-relaxed text-t80"
                         style="background: var(--hover-bg3); border: 1px solid var(--border3); font-family: ui-monospace, SFMono-Regular, Consolas, monospace"
                    >{{ $this->appsScript() }}</pre>
                </div>

                <button type="button" wire:click="regenerateSecret"
                        class="mt-3 text-[12px] font-semibold" style="color: oklch(0.7 0.15 25)">
                    {{ __('sheets.regenerate_secret') }}
                </button>
            @endif
        @endif
    </div>

    {{-- ══ Log sync ══ --}}
    <div class="dbena-card p-5 sm:p-6">
        <h2 class="mb-1 text-base font-bold">{{ __('sheets.logs') }}</h2>
        <p class="mb-4 text-[12px] text-t55">{{ __('sheets.logs_hint') }}</p>

        @forelse ($logs as $log)
            <div wire:key="log-{{ $log->id }}" class="border-b py-3" style="border-color: var(--border3)">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[12.5px]">
                    <span class="h-2 w-2 shrink-0 rounded-full" style="background: {{ $log->statusColor() }}"></span>
                    <span class="text-t65">{{ $log->created_at->translatedFormat('d M, H:i') }}</span>
                    <span class="rounded px-1.5 py-0.5 text-[11px]" style="background: var(--hover-bg3); color: var(--t60)">
                        {{ __('sheets.trigger.'.$log->trigger) }}
                    </span>
                    <span class="text-t80">{{ $log->message }}</span>
                    <span class="ml-auto text-[11px] text-t50">{{ $log->duration_ms }}ms</span>
                </div>

                @if ($log->unmatched_labels)
                    <div class="mt-1.5 text-[11.5px] text-t55">
                        {{ __('sheets.unmatched') }}: {{ implode(' · ', array_slice($log->unmatched_labels, 0, 6)) }}
                        @if (count($log->unmatched_labels) > 6) … @endif
                    </div>
                @endif
            </div>
        @empty
            <p class="py-4 text-center text-[12.5px] text-t55">{{ __('sheets.no_logs') }}</p>
        @endforelse
    </div>
</div>
