@php
    $marun = 'oklch(0.30 0.13 350)';
    $marunGelap = 'oklch(0.22 0.10 345)';
    $emas = 'oklch(0.82 0.14 85)';
    $stats = $cal['stats'];
    $hariMinggu = __('calendar.days_short');
@endphp

<div class="flex flex-col gap-4">

    {{-- ══ Kepala ══ --}}
    <div class="dbena-card flex flex-wrap items-center gap-x-4 gap-y-3 px-5 py-4">
        <div class="min-w-0 flex-1">
            <h1 class="text-[20px] font-extrabold leading-tight tracking-wide sm:text-[26px]">
                <span class="text-t94">TASK</span>
                <span style="color: {{ $emas }}">CALENDAR</span>
            </h1>
            <p class="mt-0.5 text-[12px] text-t60">{{ __('calendar_task.subtitle') }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="flex items-center gap-1.5 rounded-[10px] px-2 py-1.5" style="border: 1px solid var(--border2)">
                <i class="ph-duotone ph-calendar-blank text-sm text-t55" aria-hidden="true"></i>
                <select wire:change="goToMonth($event.target.value.split('-')[0], $event.target.value.split('-')[1])"
                        class="bg-transparent text-[12.5px] font-semibold text-t90 focus:outline-none"
                        aria-label="{{ __('task.month') }}">
                    @foreach (range(-6, 6) as $offset)
                        @php $t = \Illuminate\Support\Carbon::create($cal['year'], $cal['month'], 1)->addMonths($offset); @endphp
                        <option value="{{ $t->year }}-{{ $t->month }}" @selected($offset === 0)>
                            {{ $t->translatedFormat('F Y') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <select wire:model.live="pic" class="dbena-input w-[150px] text-[12.5px]"
                    aria-label="{{ __('calendar_task.team_filter') }}">
                <option value="">{{ __('calendar_task.all_team') }}</option>
                @foreach ($cal['team'] as $ahli)
                    <option value="{{ $ahli['name'] }}">{{ $ahli['name'] }}</option>
                @endforeach
            </select>

            <button type="button" wire:click="openAdd"
                    class="dbena-btn-gold flex items-center gap-1.5 px-4 py-2.5 text-[12.5px]">
                <i class="ph-duotone ph-plus text-sm" aria-hidden="true"></i>
                {{ __('calendar_task.add_task') }}
            </button>
        </div>
    </div>

    {{-- ══ Petak statistik ══ --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
        @foreach ([
            ['total', $stats['total'], 'ph-clipboard-text', 'oklch(0.62 0.19 255)'],
            ['in_progress', $stats['inProgress'], 'ph-dots-three-circle', 'oklch(0.72 0.17 60)'],
            ['completed', $stats['completed'], 'ph-check-circle', 'oklch(0.62 0.16 150)'],
            ['pending', $stats['pending'], 'ph-hourglass', 'oklch(0.65 0.20 330)'],
            ['cancelled', $stats['cancelled'], 'ph-x-circle', 'oklch(0.63 0.22 25)'],
        ] as [$kunci, $nilai, $ikon, $warna])
            <div class="dbena-card flex items-center gap-3 px-4 py-3.5">
                <div class="min-w-0 flex-1">
                    <div class="truncate text-[10px] font-extrabold tracking-wide text-t60">
                        {{ __('calendar_task.stat.'.$kunci) }}
                    </div>
                    <div class="mt-1 text-[24px] font-extrabold leading-none text-t94">{{ $nilai }}</div>
                    <div class="mt-0.5 text-[10px] text-t50">{{ __('calendar_task.stat.'.$kunci.'_note') }}</div>
                </div>
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                      style="background: color-mix(in oklch, {{ $warna }} 18%, transparent)">
                    <i class="ph-duotone {{ $ikon }} text-[18px]" style="color: {{ $warna }}" aria-hidden="true"></i>
                </span>
            </div>
        @endforeach

        {{-- Kadar siap: cincin dilukis dengan conic-gradient, bukan SVG.
             Satu elemen, tiada skrip, dan ia menskala dengan saiz fon. --}}
        <div class="dbena-card flex items-center gap-3 px-4 py-3.5">
            <div class="min-w-0 flex-1">
                <div class="truncate text-[10px] font-extrabold tracking-wide text-t60">
                    {{ __('calendar_task.stat.rate') }}
                </div>
                <div class="mt-1 text-[24px] font-extrabold leading-none" style="color: {{ $emas }}">{{ $stats['rate'] }}%</div>
                <div class="mt-0.5 text-[10px] text-t50">{{ __('calendar_task.stat.rate_note') }}</div>
            </div>
            <span class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                  style="background: conic-gradient({{ $marun }} {{ $stats['rate'] * 3.6 }}deg, var(--hover-bg2) 0)">
                <span class="h-[26px] w-[26px] rounded-full" style="background: var(--card-bg)"></span>
            </span>
        </div>
    </div>

    <div class="flex flex-col gap-4 xl:flex-row">

        {{-- ══ Lajur kiri ══ --}}
        <div class="flex w-full shrink-0 flex-col gap-4 xl:w-[240px]">

            {{-- Mini kalendar --}}
            <div class="dbena-card p-3.5">
                <div class="mb-2 flex items-center justify-between">
                    <button type="button" wire:click="shiftMonth(-1)" class="text-t60" aria-label="-1">
                        <i class="ph-duotone ph-caret-left text-sm" aria-hidden="true"></i>
                    </button>
                    <span class="text-[12.5px] font-bold text-t90">{{ $cal['monthLabel'] }}</span>
                    <button type="button" wire:click="shiftMonth(1)" class="text-t60" aria-label="+1">
                        <i class="ph-duotone ph-caret-right text-sm" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="grid grid-cols-7 gap-0.5 text-center">
                    @foreach ($hariMinggu as $h)
                        <div class="py-1 text-[9.5px] font-bold text-t50">{{ $h }}</div>
                    @endforeach

                    @foreach ($miniGrid as $minggu)
                        @foreach ($minggu as $sel)
                            <button type="button"
                                    wire:click="selectDay({{ $sel['date']->year }}, {{ $sel['date']->month }}, {{ $sel['day'] }})"
                                    class="relative flex h-[26px] items-center justify-center rounded-full text-[10.5px] transition-colors hover:bg-hover"
                                    style="color: {{ $sel['inMonth'] ? 'var(--t85)' : 'var(--t45)' }};
                                           {{ $sel['isToday'] ? 'background: '.$marun.'; color: #fff; font-weight: 800' : '' }}">
                                {{ $sel['day'] }}
                                @if ($sel['inMonth'] && $sel['events']->isNotEmpty() && ! $sel['isToday'])
                                    <span class="absolute bottom-0.5 h-1 w-1 rounded-full" style="background: {{ $emas }}"></span>
                                @endif
                            </button>
                        @endforeach
                    @endforeach
                </div>
            </div>

            {{-- Penapis pasukan --}}
            <div class="dbena-card p-3.5">
                <div class="mb-2.5 text-[10.5px] font-extrabold tracking-wide text-t60">
                    {{ __('calendar_task.team_filter') }}
                </div>

                <div class="flex flex-col gap-1">
                    <button type="button" wire:click="filterPic('')"
                            class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-left transition-colors hover:bg-hover"
                            @style(['background: var(--hover-bg2)' => $pic === ''])>
                        <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background: var(--t55)"></span>
                        <span class="min-w-0 flex-1 truncate text-[11.5px] font-semibold text-t85">
                            {{ __('calendar_task.all_team') }}
                        </span>
                    </button>

                    @forelse ($cal['team'] as $ahli)
                        {{-- Klik pada PIC yang sudah dipilih mengosongkan
                             penapis. Tanpa itu, satu-satunya jalan kembali
                             ialah dropdown, dan orang menganggap penapis
                             tersekat. --}}
                        <button type="button" wire:click="filterPic('{{ $ahli['name'] }}')"
                                class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-left transition-colors hover:bg-hover"
                                @style(['background: var(--hover-bg2)' => $pic === $ahli['name']])>
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background: {{ $ahli['color'] }}"></span>
                            <span class="min-w-0 flex-1 truncate text-[11.5px] text-t80">
                                {{ $ahli['name'] }}
                                @if ($ahli['department'])
                                    <span class="text-t50">({{ $ahli['department'] }})</span>
                                @endif
                            </span>
                            <span class="shrink-0 text-[10px] text-t55">{{ $ahli['count'] }}</span>
                        </button>
                    @empty
                        <span class="px-2 py-1.5 text-[11.5px] text-t55">{{ __('calendar_task.others') }}</span>
                    @endforelse
                </div>

                {{-- Manual, bukan automatik pada setiap klik petak. Panggilan
                     Google mengambil satu hingga tiga saat; melakukannya pada
                     setiap tanda hari bermakna papan terasa rosak semasa
                     mesyuarat. --}}
                <button type="button" wire:click="pushToGoogle" wire:loading.attr="disabled"
                        class="mt-3 flex w-full items-center justify-center gap-1.5 rounded-[9px] px-3 py-2 text-[11.5px] font-semibold text-t85"
                        style="border: 1px solid var(--border2)">
                    <i class="ph-duotone ph-google-logo text-sm" aria-hidden="true"></i>
                    <span wire:loading.remove wire:target="pushToGoogle">{{ __('calendar_task.google.sync') }}</span>
                    <span wire:loading wire:target="pushToGoogle">{{ __('calendar_task.google.syncing') }}</span>
                </button>

                <a href="{{ route('task-calendar.pdf', ['tahun' => $cal['year'], 'bulan' => $cal['month'], 'pic' => $pic]) }}"
                   class="mt-3 flex items-center justify-center gap-1.5 rounded-[9px] px-3 py-2 text-[11.5px] font-semibold text-t80"
                   style="border: 1px solid var(--border2)">
                    <i class="ph-duotone ph-download-simple text-sm" aria-hidden="true"></i>
                    {{ __('calendar_task.export') }}
                </a>
            </div>
        </div>

        {{-- ══ Kalendar utama ══ --}}
        <div class="dbena-card min-w-0 flex-1 overflow-hidden">
            <div class="flex flex-wrap items-center gap-2 px-4 py-3" style="border-bottom: 1px solid var(--border3)">
                <div class="flex items-center gap-1 rounded-[10px] p-1" style="background: var(--hover-bg2)">
                    @foreach (['month', 'week', 'day'] as $mod)
                        <button type="button" wire:click="setView('{{ $mod }}')"
                                class="rounded-[7px] px-3 py-1.5 text-[11.5px] font-bold transition-colors"
                                style="{{ $view === $mod ? 'background: '.$marun.'; color: #fff' : 'color: var(--t65)' }}">
                            {{ __('calendar_task.view.'.$mod) }}
                        </button>
                    @endforeach
                </div>

                <div class="ml-auto flex items-center gap-1.5">
                    <button type="button" wire:click="today"
                            class="rounded-[9px] px-3 py-1.5 text-[11.5px] font-semibold text-t80"
                            style="border: 1px solid var(--border2)">{{ __('calendar_task.today') }}</button>
                    <button type="button" wire:click="shift(-1)"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-t75"
                            style="border: 1px solid var(--border2)" aria-label="-1">
                        <i class="ph-duotone ph-caret-left text-sm" aria-hidden="true"></i>
                    </button>
                    <button type="button" wire:click="shift(1)"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-t75"
                            style="border: 1px solid var(--border2)" aria-label="+1">
                        <i class="ph-duotone ph-caret-right text-sm" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            @include('livewire.dashboard._calendar-'.$view)
        </div>

        {{-- ══ Tugasan akan datang ══ --}}
        <div class="dbena-card w-full shrink-0 overflow-hidden xl:w-[250px]">
            <div class="px-4 py-3 text-[10.5px] font-extrabold tracking-wide text-t60"
                 style="border-bottom: 1px solid var(--border3)">
                {{ __('calendar_task.upcoming') }}
            </div>

            <div class="flex flex-col gap-3 p-4">
                @forelse ($cal['upcoming'] as $acara)
                    <div>
                        <div class="text-[10.5px] font-extrabold" style="color: {{ $emas }}">
                            {{ mb_strtoupper($acara['date']->translatedFormat('j F Y (D)')) }}
                        </div>
                        <div class="mt-1 flex gap-2">
                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full"
                                  style="background: {{ $acara['mark']->color() }}"></span>
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] font-bold text-t85">
                                    {{ $acara['time'] ?? __('calendar_task.all_day') }}
                                </div>
                                <div class="text-[11.5px] leading-snug text-t75">{{ $acara['title'] }}</div>
                            </div>
                            @if ($acara['pic'])
                                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[9px] font-extrabold text-white"
                                      style="background: {{ $acara['picColor'] }}"
                                      title="{{ $acara['pic'] }}">
                                    {{ mb_strtoupper(mb_substr($acara['pic'], 0, 2)) }}
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-[11.5px] text-t55">{{ __('calendar_task.no_upcoming') }}</p>
                @endforelse
            </div>

            <a href="{{ route('task-planning') }}" wire:navigate
               class="flex items-center gap-1.5 px-4 py-3 text-[12px] font-semibold"
               style="border-top: 1px solid var(--border3); color: {{ $emas }}">
                {{ __('calendar_task.view_all') }} <i class="ph-duotone ph-caret-right text-xs" aria-hidden="true"></i>
            </a>
        </div>
    </div>

    {{-- ══ Jalur kaki ══ --}}
    <div class="flex flex-wrap items-center justify-center gap-x-10 gap-y-2 rounded-xl px-5 py-3"
         style="background: linear-gradient(115deg, {{ $marunGelap }}, {{ $marun }})">
        @foreach ([
            ['ph-clipboard-text', __('calendar_task.footer.plan')],
            ['ph-users-three', __('calendar_task.footer.work')],
            ['ph-target', __('calendar_task.footer.achieve')],
        ] as [$ikon, $teks])
            <span class="flex items-center gap-2 text-[12px] font-extrabold tracking-wide text-white">
                <i class="ph-duotone {{ $ikon }} text-base" style="color: {{ $emas }}" aria-hidden="true"></i>
                {{ $teks }}
            </span>
        @endforeach
    </div>

    {{-- ══ Modal tambah tugasan ══ --}}
    @if ($showAdd)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background: oklch(0.10 0.02 260 / 0.72)"
             wire:click.self="closeAdd">
            <div class="dbena-card w-full max-w-[460px] overflow-hidden">
                <div class="flex items-center gap-2 px-5 py-3.5" style="background: {{ $marun }}">
                    <i class="ph-duotone ph-plus-circle text-base text-white/85" aria-hidden="true"></i>
                    <span class="text-[13px] font-extrabold text-white">{{ __('calendar_task.form.title') }}</span>
                    <button type="button" wire:click="closeAdd" class="ml-auto text-white/70"
                            aria-label="{{ __('calendar_task.form.cancel') }}">
                        <i class="ph-duotone ph-x text-sm" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="flex flex-col gap-3 p-5">
                    <div>
                        <label for="cal-title" class="mb-1.5 block text-[11.5px] text-t60">{{ __('calendar_task.form.task') }}</label>
                        <input id="cal-title" type="text" wire:model="newTitle" wire:keydown.enter="addTask"
                               class="dbena-input" placeholder="{{ __('calendar_task.form.task_placeholder') }}">
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label for="cal-dept" class="mb-1.5 block text-[11.5px] text-t60">{{ __('calendar_task.form.department') }}</label>
                            <select id="cal-dept" wire:model="newDepartment" class="dbena-input">
                                @foreach ($departments as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="cal-status" class="mb-1.5 block text-[11.5px] text-t60">{{ __('calendar_task.form.status') }}</label>
                            <select id="cal-status" wire:model="newMark" class="dbena-input">
                                @foreach ($marks as $m)
                                    <option value="{{ $m->value }}">{{ $m->letter() }} — {{ $m->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label for="cal-day" class="mb-1.5 block text-[11.5px] text-t60">{{ __('calendar_task.form.day') }}</label>
                            <input id="cal-day" type="number" min="1"
                                   max="{{ \Illuminate\Support\Carbon::create($cal['year'], $cal['month'], 1)->daysInMonth }}"
                                   wire:model="newDay" class="dbena-input">
                        </div>

                        <div>
                            <label for="cal-time" class="mb-1.5 block text-[11.5px] text-t60">{{ __('calendar_task.form.time') }}</label>
                            <input id="cal-time" type="time" wire:model="newTime" class="dbena-input">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label for="cal-action" class="mb-1.5 block text-[11.5px] text-t60">{{ __('calendar_task.form.action_by') }}</label>
                            <input id="cal-action" type="text" wire:model="newActionBy" list="senarai-pic" class="dbena-input">
                        </div>
                        <div>
                            <label for="cal-monitor" class="mb-1.5 block text-[11.5px] text-t60">{{ __('calendar_task.form.monitor_by') }}</label>
                            <input id="cal-monitor" type="text" wire:model="newMonitorBy" list="senarai-pic" class="dbena-input">
                        </div>
                    </div>

                    <div class="mt-1 flex justify-end gap-2">
                        <button type="button" wire:click="closeAdd"
                                class="rounded-[9px] px-4 py-2.5 text-[12.5px] font-semibold text-t75"
                                style="border: 1px solid var(--border2)">{{ __('calendar_task.form.cancel') }}</button>
                        <button type="button" wire:click="addTask"
                                class="dbena-btn-gold px-4 py-2.5 text-[12.5px]">{{ __('calendar_task.form.save') }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <datalist id="senarai-pic">
        @foreach ($owners as $nama)
            <option value="{{ $nama }}"></option>
        @endforeach
    </datalist>
</div>
