@php
    $pct = fn (int $n) => $totalProjects > 0 ? round($n / $totalProjects * 100, 1) : 0;
@endphp

<div class="flex flex-col gap-5 sm:gap-6">

    {{-- ══ Bar carian & tindakan ══ --}}
    <div class="dbena-card flex flex-wrap items-center gap-3 px-4 py-4 sm:px-5">
        <div class="relative min-w-0 flex-1" style="min-width: 220px">
            <i class="ph-duotone ph-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[16px] text-t50"
               aria-hidden="true"></i>
            <input type="search" wire:model.live.debounce.300ms="search"
                   placeholder="{{ __('project.search') }}"
                   aria-label="{{ __('project.search') }}"
                   class="dbena-input pl-9">
        </div>

        {{-- Senarai dibina daripada status yang benar-benar wujud dalam
             data. Menyenaraikan setiap kes enum memberi pengguna pilihan
             yang sentiasa memulangkan senarai kosong, dan senarai kosong
             kelihatan seperti penapis yang rosak. --}}
        <select wire:model.live="status" class="dbena-input w-48" aria-label="{{ __('project.col.status') }}">
            <option value="">{{ __('project.all_status', ['count' => number_format($totalProjects)]) }}</option>
            @foreach ($statuses as $s)
                <option value="{{ $s['status']->value }}">
                    {{ $s['status']->label() }} ({{ number_format($s['count']) }})
                </option>
            @endforeach
        </select>

        {{-- Eksport: Admin sahaja. Ia mengeluarkan senarai pelanggan penuh
             dengan telefon dan emel daripada sistem. --}}
        @can('export-projects')
            <a href="{{ route('projek.eksport', [
                    'servis' => $serviceKey, 'cari' => $search, 'status' => $status,
               ]) }}"
               class="dbena-btn-gold flex items-center gap-2 px-4 py-2.5 text-[13px]">
                <i class="ph-duotone ph-download-simple text-base" aria-hidden="true"></i>
                {{ __('project.export') }}
            </a>
        @endcan

        {{-- Lihat Google Sheet: pengguna DAN admin. Data diisi di sana,
             jadi pautan itu ialah cara pengguna menyemak sumbernya. --}}
        @if ($sheet?->viewUrl())
            <a href="{{ $sheet->viewUrl() }}" target="_blank" rel="noopener noreferrer"
               class="flex items-center gap-2 rounded-[9px] px-4 py-2.5 text-[13px] font-semibold text-t80"
               style="border: 1px solid var(--border2)">
                <i class="ph-duotone ph-google-logo text-base" aria-hidden="true"></i>
                {{ __('project.view_sheet') }}
            </a>
        @endif
    </div>

    {{-- ══ Sepanduk penapis aktif ══ --}}
    {{-- Petak di bawah menunjukkan kiraan YANG DITAPIS. Perubahan itu
         mesti dinyatakan, kalau tidak "36" kelihatan seperti jumlah
         sebenar Renovation dan bukan bilangan Quotation di dalamnya. --}}
    @if ($isFiltered)
        <div class="dbena-card flex flex-wrap items-center gap-3 px-4 py-3"
             style="border-color: oklch(0.78 0.12 85/0.45)">
            <i class="ph-duotone ph-funnel text-[17px]" style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>

            <span class="text-[12.5px] font-semibold text-t80">
                @if ($activeStatus)
                    {{ __('project.filtered.status', ['status' => $activeStatus->label()]) }}
                @endif
                @if ($search !== '')
                    {{ __('project.filtered.search', ['term' => $search]) }}
                @endif
            </span>

            <span class="text-[12px] text-t55">
                {{ __('project.filtered.count', [
                    'shown' => number_format($totalProjects),
                    'total' => number_format($grandTotal),
                ]) }}
            </span>

            <button type="button" wire:click="clearFilters"
                    class="ml-auto inline-flex items-center gap-1.5 rounded-[20px] px-3 py-1.5 text-[11.5px] font-semibold"
                    style="background: oklch(0.78 0.12 85/0.15); border: 1px solid oklch(0.78 0.12 85/0.5); color: oklch(0.78 0.12 85)">
                <i class="ph-duotone ph-x text-xs" aria-hidden="true"></i>
                {{ __('project.filtered.clear') }}
            </button>
        </div>
    @endif

    {{-- ══ Petak kiraan ══ --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3 xl:grid-cols-6">
        <div class="dbena-card px-4 py-4">
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                      style="background: oklch(0.78 0.12 85/0.15)">
                    <i class="ph-duotone ph-clipboard-text text-[19px]"
                       style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
                </span>
                <span class="truncate text-[11.5px] font-semibold text-t60">{{ __('project.tile.total') }}</span>
            </div>
            <div class="mt-2 text-[26px] font-extrabold leading-none text-t94">{{ number_format($totalProjects) }}</div>
            {{-- Jumlah keseluruhan kekal kelihatan semasa ditapis. Tanpa
                 penyebut, nombor yang mengecil kelihatan seperti data yang
                 hilang, bukan seperti penapis yang sedang berjalan. --}}
            <div class="mt-1 text-[11px] text-t50">
                @if ($isFiltered)
                    {{ __('project.tile.of_all', ['total' => number_format($grandTotal)]) }}
                @else
                    {{ __('project.tile.total_note') }}
                @endif
            </div>
        </div>

        @foreach ($services as $service)
            @php $n = (int) ($countByService[$service->id] ?? 0); @endphp
            <button type="button" wire:click="selectService('{{ $service->key }}')"
                    class="dbena-card px-4 py-4 text-left transition-colors hover:bg-hover"
                    @style([
                        'border: 1px solid '.$service->chart_color => $serviceKey === $service->key,
                    ])>
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                          style="background: color-mix(in oklch, {{ $service->chart_color }} 16%, transparent)">
                        <i class="ph-duotone {{ $service->icon_class }} text-[19px]"
                           style="color: {{ $service->chart_color }}" aria-hidden="true"></i>
                    </span>
                    <span class="truncate text-[11.5px] font-semibold text-t60">{{ $service->name }}</span>
                </div>
                <div class="mt-2 text-[26px] font-extrabold leading-none text-t94">{{ number_format($n) }}</div>
                <div class="mt-1 text-[11px] text-t50">
                    @if ($isFiltered)
                        {{ __('project.tile.of_service', [
                            'total' => number_format((int) ($grandByService[$service->id] ?? 0)),
                        ]) }}
                    @else
                        {{ __('project.tile.of_total', ['pct' => $pct($n)]) }}
                    @endif
                </div>
            </button>
        @endforeach

        <div class="dbena-card px-4 py-4">
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                      style="background: oklch(0.62 0.16 150/0.15)">
                    <i class="ph-duotone ph-check-circle text-[19px]"
                       style="color: oklch(0.62 0.16 150)" aria-hidden="true"></i>
                </span>
                <span class="truncate text-[11.5px] font-semibold text-t60">{{ __('project.tile.closed') }}</span>
            </div>
            <div class="mt-2 text-[26px] font-extrabold leading-none text-t94">{{ number_format($closedProjects) }}</div>
            <div class="mt-1 text-[11px] text-t50">{{ __('project.tile.of_total', ['pct' => $pct($closedProjects)]) }}</div>
        </div>
    </div>

    {{-- ══ Tab kategori ══ --}}
    <div class="dbena-card overflow-hidden">
        <div class="snap-row flex gap-1 overflow-x-auto border-b px-2 pt-2" style="border-color: var(--border2)">
            <button type="button" wire:click="selectService(null)"
                    class="flex shrink-0 items-center gap-2 rounded-t-[10px] px-4 py-3 text-[12.5px] font-bold transition-colors"
                    @style([
                        'background: var(--hover-bg2); color: var(--t94); border-bottom: 2px solid oklch(0.78 0.12 85)' => $serviceKey === null,
                        'color: var(--t60)' => $serviceKey !== null,
                    ])>
                <i class="ph-duotone ph-list-dashes text-base" aria-hidden="true"></i>
                {{ __('project.all') }}
            </button>

            @foreach ($services as $service)
                @php $aktif = $serviceKey === $service->key; @endphp
                <button type="button" wire:click="selectService('{{ $service->key }}')"
                        class="flex shrink-0 items-center gap-2 rounded-t-[10px] px-4 py-3 text-[12.5px] font-bold transition-colors"
                        @style([
                            'background: var(--hover-bg2); color: var(--t94); border-bottom: 2px solid '.$service->chart_color => $aktif,
                            'color: var(--t60)' => ! $aktif,
                        ])>
                    <i class="ph-duotone {{ $service->icon_class }} text-base"
                       style="color: {{ $aktif ? $service->chart_color : 'var(--t50)' }}" aria-hidden="true"></i>
                    {{ $service->name }}
                </button>
            @endforeach
        </div>

        {{-- ══ Jadual — desktop ══ --}}
        <div class="hidden overflow-x-auto lg:block">
            <table class="w-full min-w-[1240px] border-collapse">
                <thead>
                    <tr style="background: var(--hover-bg3)">
                        @foreach ([
                            'code' => 'project.col.code',
                            'project_date' => 'project.col.date',
                            'client_name' => 'project.col.client',
                            'pic_sales' => 'project.col.pic',
                        ] as $field => $key)
                            <th class="whitespace-nowrap px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-t60">
                                <button type="button" wire:click="sortBy('{{ $field }}')"
                                        class="flex items-center gap-1 hover:text-t90">
                                    {{ __($key) }}
                                    @if ($sortField === $field)
                                        <i class="ph-duotone ph-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-[11px]"
                                           style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
                                    @endif
                                </button>
                            </th>
                        @endforeach
                        <th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-t60">{{ __('project.col.service') }}</th>
                        <th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-t60">{{ __('project.col.phone') }}</th>
                        <th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-t60">{{ __('project.col.address') }}</th>
                        <th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-t60">{{ __('project.col.email') }}</th>
                        <th class="px-3 py-3 text-right text-[11px] font-bold uppercase tracking-wide text-t60">{{ __('project.col.contract') }}</th>
                        <th class="px-3 py-3 text-right text-[11px] font-bold uppercase tracking-wide text-t60">{{ __('project.col.vo') }}</th>
                        <th class="px-3 py-3 text-center text-[11px] font-bold uppercase tracking-wide text-t60">{{ __('project.col.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($projects as $project)
                        <tr class="border-t" style="border-color: var(--border3)" wire:key="prj-{{ $project->id }}">
                            <td class="whitespace-nowrap px-3 py-3 text-[12px] font-bold text-t90">{{ $project->code }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-[12px] text-t70">
                                {{ $project->project_date?->translatedFormat('d M Y') ?? '—' }}
                            </td>
                            <td class="px-3 py-3 text-[12px] text-t85">{{ $project->client_name }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-[12px] text-t70">{{ $project->pic_sales ?? '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-[12px]">
                                <span class="rounded-md px-2 py-1 text-[11px] font-semibold"
                                      style="background: color-mix(in oklch, {{ $project->service->chart_color }} 14%, transparent);
                                             color: {{ $project->service->chart_color }}">
                                    {{ $project->service->name }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 text-[12px] text-t70">{{ $project->phone ?? '—' }}</td>
                            <td class="max-w-[220px] px-3 py-3 text-[11.5px] leading-snug text-t65">{{ $project->address ?? '—' }}</td>
                            <td class="px-3 py-3 text-[11.5px] text-t65">{{ $project->email ?? '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-right text-[12px] font-semibold text-t90">
                                RM{{ number_format((float) $project->contract_amount) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 text-right text-[12px] text-t75">
                                RM{{ number_format((float) $project->variation_order) }}
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="inline-block rounded-full px-2.5 py-1 text-[10.5px] font-bold"
                                      style="background: color-mix(in oklch, {{ $project->status->color() }} 18%, transparent);
                                             color: {{ $project->status->color() }}">
                                    {{ $project->status->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-14 text-center">
                                <i class="ph-duotone ph-folder-open text-4xl text-t40" aria-hidden="true"></i>
                                <p class="mt-3 text-[14px] font-semibold text-t70">{{ __('project.empty') }}</p>
                                <p class="mt-1.5 text-[12.5px] text-t55">
                                    {{ $sheet ? __('project.empty_hint') : __('project.no_sheet') }}
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ══ Kad — telefon & tablet ══ --}}
        <div class="flex flex-col gap-2.5 p-3 lg:hidden">
            @forelse ($projects as $project)
                <div class="rounded-xl p-3.5" wire:key="prjm-{{ $project->id }}"
                     style="background: var(--hover-bg3); border: 1px solid var(--border3)">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[12.5px] font-extrabold text-t90">{{ $project->code }}</span>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                              style="background: color-mix(in oklch, {{ $project->status->color() }} 18%, transparent);
                                     color: {{ $project->status->color() }}">
                            {{ $project->status->label() }}
                        </span>
                        <span class="ml-auto text-[11px] text-t55">
                            {{ $project->project_date?->translatedFormat('d M Y') ?? '—' }}
                        </span>
                    </div>

                    <div class="mt-2 text-[13px] font-semibold text-t90">{{ $project->client_name }}</div>
                    <div class="mt-0.5 text-[11.5px] text-t60">{{ $project->address ?? '—' }}</div>

                    <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11.5px] text-t65">
                        <span class="rounded-md px-2 py-0.5 text-[11px] font-semibold"
                              style="background: color-mix(in oklch, {{ $project->service->chart_color }} 14%, transparent);
                                     color: {{ $project->service->chart_color }}">
                            {{ $project->service->name }}
                        </span>
                        @if ($project->pic_sales)
                            <span>{{ __('project.col.pic') }}: <b class="text-t80">{{ $project->pic_sales }}</b></span>
                        @endif
                        @if ($project->phone)
                            <span>{{ $project->phone }}</span>
                        @endif
                    </div>

                    <div class="mt-2 flex flex-wrap items-baseline gap-x-4 text-[11.5px] text-t60">
                        <span>{{ __('project.col.contract') }}:
                            <b class="text-t90">RM{{ number_format((float) $project->contract_amount) }}</b></span>
                        <span>{{ __('project.col.vo') }}:
                            <b class="text-t80">RM{{ number_format((float) $project->variation_order) }}</b></span>
                    </div>
                </div>
            @empty
                <div class="px-4 py-12 text-center">
                    <i class="ph-duotone ph-folder-open text-4xl text-t40" aria-hidden="true"></i>
                    <p class="mt-3 text-[14px] font-semibold text-t70">{{ __('project.empty') }}</p>
                    <p class="mt-1.5 text-[12.5px] text-t55">
                        {{ $sheet ? __('project.empty_hint') : __('project.no_sheet') }}
                    </p>
                </div>
            @endforelse
        </div>

        {{-- ══ Penomboran ══ --}}
        @if ($projects->total() > 0)
            <div class="flex flex-wrap items-center gap-3 border-t px-4 py-3.5" style="border-color: var(--border2)">
                <span class="text-[11.5px] text-t55">
                    {{ __('project.showing', [
                        'from' => $projects->firstItem(),
                        'to' => $projects->lastItem(),
                        'total' => number_format($projects->total()),
                    ]) }}
                </span>

                <div class="ml-auto flex items-center gap-3">
                    <label class="flex items-center gap-2 text-[11.5px] text-t55">
                        {{ __('project.per_page') }}
                        <select wire:model.live="perPage"
                                class="rounded-lg px-2 py-1.5 text-[12px] text-t90 focus:outline-none"
                                style="background: var(--input-bg); border: 1px solid var(--border2)">
                            @foreach ([10, 25, 50, 100] as $n)
                                <option value="{{ $n }}">{{ $n }}</option>
                            @endforeach
                        </select>
                    </label>

                    <div>{{ $projects->onEachSide(1)->links() }}</div>
                </div>
            </div>
        @endif
    </div>

    {{-- ══ Nota sumber ══ --}}
    <p class="padat-sorok text-[11.5px] leading-relaxed text-t50">
        <i class="ph-duotone ph-info" aria-hidden="true"></i>
        {{ __('project.source_note') }}
    </p>
</div>
