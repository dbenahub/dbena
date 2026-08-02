@php
    $marun = 'oklch(0.30 0.13 350)';
    $marunGelap = 'oklch(0.22 0.10 345)';
    $emas = 'oklch(0.82 0.14 85)';

    // Lebar lajur hari dikira sekali. Dengan 31 lajur, satu piksel
    // perbezaan antara kepala dan badan menyebabkan seluruh jadual
    // tersasar dan huruf berhenti sebaris dengan nombor harinya.
    $lebarHari = 26;
@endphp

<div class="flex flex-col gap-4">

    {{-- ══ Kepala ══ --}}
    <div class="dbena-card overflow-hidden">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-3 px-5 py-4"
             style="background: linear-gradient(115deg, {{ $marunGelap }}, {{ $marun }} 55%, oklch(0.20 0.08 345));
                    border-bottom: 2px solid {{ $emas }}">

            <div class="min-w-0 flex-1">
                <h1 class="text-[19px] font-extrabold leading-tight tracking-wide text-white sm:text-[26px]">
                    {{ __('task.title') }}
                </h1>
                <div class="mt-0.5 text-[11px] font-bold tracking-[0.22em] sm:text-[12.5px]"
                     style="color: {{ $emas }}">{{ __('task.tagline') }}</div>
            </div>

            <div class="flex flex-col gap-1.5 rounded-xl px-4 py-3"
                 style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.18)">
                @foreach ([
                    ['ph-calendar-blank', __('task.month'), $monthLabel],
                    ['ph-user', __('task.prepared_by'), $board?->prepared_by ?: '—'],
                    ['ph-calendar-check', __('task.date_prepared'), $board?->prepared_on?->translatedFormat('j F Y') ?: '—'],
                ] as [$ikon, $label, $nilai])
                    <div class="flex items-center gap-2.5 text-[11.5px] whitespace-nowrap">
                        <i class="ph-duotone {{ $ikon }} text-sm text-white/70" aria-hidden="true"></i>
                        <span class="w-[110px] font-semibold text-white/70">{{ $label }}</span>
                        <span class="font-extrabold text-white">: {{ $nilai }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ══ Petunjuk + kawalan bulan ══ --}}
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2.5 px-4 py-3"
             style="background: var(--hover-bg3); border-bottom: 1px solid var(--border3)">

            <span class="rounded-[20px] px-3.5 py-1.5 text-[11px] font-extrabold tracking-wide text-white"
                  style="background: {{ $marun }}">{{ __('task.legend') }}</span>

            @foreach ($marks as $m)
                <span class="flex items-center gap-1.5">
                    <span class="flex h-[19px] min-w-[19px] items-center justify-center rounded px-1 text-[10px] font-extrabold"
                          style="background: {{ $m->color() }}; color: {{ $m->textColor() }}">{{ $m->letter() }}</span>
                    <span class="text-[11.5px] text-t75">{{ $m->label() }}</span>
                </span>
            @endforeach

            <div class="ml-auto flex flex-wrap items-center gap-2">
                <button type="button" wire:click="shiftMonth(-1)"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-t75"
                        style="border: 1px solid var(--border2)" aria-label="{{ __('task.month') }} -1">
                    <i class="ph-duotone ph-caret-left text-sm" aria-hidden="true"></i>
                </button>
                <span class="min-w-[120px] text-center text-[12.5px] font-extrabold text-t94">{{ $monthLabel }}</span>
                <button type="button" wire:click="shiftMonth(1)"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-t75"
                        style="border: 1px solid var(--border2)" aria-label="{{ __('task.month') }} +1">
                    <i class="ph-duotone ph-caret-right text-sm" aria-hidden="true"></i>
                </button>

                <button type="button" wire:click="copyPreviousMonth"
                        class="flex items-center gap-1.5 rounded-[9px] px-3 py-2 text-[12px] font-semibold text-t80"
                        style="border: 1px solid var(--border2)">
                    <i class="ph-duotone ph-copy text-sm" aria-hidden="true"></i>
                    {{ __('task.copy_month') }}
                </button>

                <a href="{{ route('task-planning.pdf', ['tahun' => $year, 'bulan' => $month]) }}"
                   class="dbena-btn-gold flex items-center gap-1.5 px-3 py-2 text-[12px]">
                    <i class="ph-duotone ph-file-pdf text-sm" aria-hidden="true"></i>
                    {{ __('task.export') }}
                </a>
            </div>
        </div>
    </div>

    {{-- ══ Jadual ══ --}}
    <div class="dbena-card overflow-hidden">
        @if ($departments->isEmpty())
            <div class="px-6 py-10 text-center text-[12.5px] text-t65">{{ __('task.no_departments') }}</div>
        @else
            <div class="overflow-x-auto">
                <div style="min-width: {{ 640 + count($days) * $lebarHari }}px">

                    {{-- Kepala jadual --}}
                    <div class="flex text-[10.5px] font-extrabold uppercase tracking-wide text-white"
                         style="background: {{ $marunGelap }}">
                        <div class="shrink-0 px-2 py-2.5 text-center" style="width: 46px">{{ __('task.col.no') }}</div>
                        <div class="shrink-0 px-3 py-2.5" style="width: 260px">{{ __('task.col.task') }}</div>
                        <div class="shrink-0 px-2 py-2.5 text-center" style="width: 110px">{{ __('task.col.action_by') }}</div>
                        <div class="shrink-0 px-2 py-2.5 text-center" style="width: 110px">{{ __('task.col.monitor_by') }}</div>

                        <div class="shrink-0" style="width: {{ count($days) * $lebarHari }}px">
                            <div class="border-b py-1 text-center text-[11px]" style="border-color: rgba(255,255,255,0.2)">
                                {{ mb_strtoupper($monthLabel) }}
                            </div>
                            <div class="flex">
                                @foreach ($days as $d)
                                    {{-- Minggu semasa disorot. Dengan tiga puluh satu
                                         lajur sempit, mencari "minggu ini" bermakna
                                         mengira ke seberang meja mesyuarat, dan
                                         seseorang sentiasa tersalah lajur. --}}
                                    <div class="py-1 text-center text-[10px]"
                                         style="width: {{ $lebarHari }}px;
                                                {{ in_array($d, $weekDays, true) ? 'background: '.$emas.'; color: oklch(0.18 0.02 260)' : '' }}
                                                {{ $today === $d ? 'font-weight: 900; text-decoration: underline' : '' }}">{{ $d }}</div>
                                @endforeach
                            </div>
                        </div>

                        <div class="min-w-0 flex-1 px-3 py-2.5">{{ __('task.col.remark') }}</div>
                    </div>

                    {{-- Bahagian jabatan --}}
                    @foreach ($departments as $dept)
                        @php $senarai = $tasksByDepartment[$dept->id] ?? collect(); @endphp

                        <div class="flex items-center gap-2 px-3 py-2"
                             style="background: {{ $marun }}; border-top: 1px solid var(--border3)">
                            <i class="ph-duotone {{ $dept->icon ?: 'ph-megaphone' }} text-base text-white/85" aria-hidden="true"></i>
                            <span class="text-[12px] font-extrabold tracking-wide text-white">{{ $dept->name }}</span>

                            <button type="button" wire:click="startAdd({{ $dept->id }})"
                                    class="ml-auto flex items-center gap-1.5 rounded-[7px] px-2.5 py-1 text-[11px] font-bold text-white"
                                    style="border: 1px solid rgba(255,255,255,0.35)">
                                <i class="ph-duotone ph-plus text-xs" aria-hidden="true"></i>
                                {{ __('task.add_task') }}
                            </button>
                        </div>

                        @forelse ($senarai as $task)
                            @include('livewire.dashboard._task-row', [
                                'task' => $task, 'bil' => $loop->iteration,
                            ])
                        @empty
                            <div class="px-4 py-3 text-[11.5px] text-t55"
                                 style="border-top: 1px solid var(--border3)">{{ __('task.no_tasks') }}</div>
                        @endforelse

                        {{-- Borang tambah kekal terbuka selepas setiap simpan:
                             menutupnya bermakna sepuluh klik tambahan untuk
                             menaip sepuluh tugasan, yang tepat apa yang
                             berlaku dalam mesyuarat. --}}
                        @if ($addingTo === $dept->id)
                            <div class="flex flex-wrap items-center gap-2 px-4 py-3"
                                 style="background: var(--hover-bg3); border-top: 1px solid var(--border3)">
                                <input type="text" wire:model="newTitle" wire:keydown.enter="addTask"
                                       class="dbena-input min-w-0 flex-1" style="min-width: 240px"
                                       placeholder="{{ __('task.task_placeholder') }}"
                                       aria-label="{{ __('task.col.task') }}">

                                <input type="text" wire:model="newActionBy" list="senarai-pic"
                                       class="dbena-input w-[130px]" placeholder="{{ __('task.col.action_by') }}"
                                       aria-label="{{ __('task.col.action_by') }}">

                                <input type="text" wire:model="newMonitorBy" list="senarai-pic"
                                       class="dbena-input w-[130px]" placeholder="{{ __('task.col.monitor_by') }}"
                                       aria-label="{{ __('task.col.monitor_by') }}">

                                <button type="button" wire:click="addTask"
                                        class="dbena-btn-gold px-3 py-2 text-[12px]">{{ __('task.save') }}</button>
                                <button type="button" wire:click="cancelAdd"
                                        class="rounded-[9px] px-3 py-2 text-[12px] font-semibold text-t75"
                                        style="border: 1px solid var(--border2)">{{ __('task.cancel') }}</button>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Senarai PIC dikongsi oleh setiap medan. Menaip semula nama yang
         sama dua puluh kali menjemput salah eja, dan "Zikri" serta "ZIKRI"
         menjadi dua orang berbeza dalam mana-mana laporan kemudian. --}}
    <datalist id="senarai-pic">
        @foreach ($owners as $nama)
            <option value="{{ $nama }}"></option>
        @endforeach
    </datalist>

    {{-- ══ Panel bawah ══ --}}
    <div class="grid gap-4 lg:grid-cols-3">

        <div class="dbena-card overflow-hidden">
            <div class="flex items-center gap-2 px-4 py-2.5" style="background: {{ $marun }}">
                <i class="ph-duotone ph-target text-base text-white/85" aria-hidden="true"></i>
                <span class="text-[11.5px] font-extrabold tracking-wide text-white">{{ __('task.priority') }}</span>
            </div>
            <div class="p-4">
                <textarea wire:model="priorities" rows="5" class="dbena-input text-[12px]"
                          style="resize: vertical"
                          placeholder="{{ __('task.priority_placeholder') }}"
                          aria-label="{{ __('task.priority') }}"></textarea>
            </div>
        </div>

        <div class="dbena-card overflow-hidden">
            <div class="flex items-center gap-2 px-4 py-2.5" style="background: {{ $marun }}">
                <i class="ph-duotone ph-chart-bar text-base text-white/85" aria-hidden="true"></i>
                <span class="text-[11.5px] font-extrabold tracking-wide text-white">{{ __('task.summary') }}</span>
            </div>

            {{-- Dikira daripada tanda, bukan disimpan. Kaunter yang
                 disimpan menyimpang sebaik satu petak diubah tanpa melalui
                 laluan yang mengemas kininya, dan nombor yang bercanggah
                 dengan jadual di atasnya lebih teruk daripada tiada
                 nombor. --}}
            <div class="grid grid-cols-2 gap-3 p-4">
                @foreach ([
                    ['total', $summary['total'], null],
                    ['in_progress', $summary['inProgress'], \App\Enums\TaskMark::Planning],
                    ['cancelled', $summary['cancelled'], \App\Enums\TaskMark::Cancel],
                    ['completed', $summary['completed'], \App\Enums\TaskMark::Complete],
                    ['pending', $summary['pending'], \App\Enums\TaskMark::Kiv],
                ] as [$kunci, $nilai, $tanda])
                    <div class="flex items-baseline gap-2">
                        <span class="text-[22px] font-extrabold leading-none text-t94">{{ $nilai }}</span>
                        <span class="flex items-center gap-1 text-[10.5px] font-bold text-t65">
                            {{ __('task.stat.'.$kunci) }}
                            @if ($tanda)
                                <span class="rounded px-1 text-[9px] font-extrabold"
                                      style="background: {{ $tanda->color() }}; color: {{ $tanda->textColor() }}">{{ $tanda->letter() }}</span>
                            @endif
                        </span>
                    </div>
                @endforeach

                <div class="flex items-baseline gap-2">
                    <span class="text-[22px] font-extrabold leading-none" style="color: {{ $emas }}">{{ $summary['focus'] }}%</span>
                    <span class="text-[10.5px] font-bold text-t65">{{ __('task.stat.focus') }}</span>
                </div>
            </div>
        </div>

        <div class="dbena-card overflow-hidden">
            <div class="flex items-center gap-2 px-4 py-2.5" style="background: {{ $marun }}">
                <i class="ph-duotone ph-note text-base text-white/85" aria-hidden="true"></i>
                <span class="text-[11.5px] font-extrabold tracking-wide text-white">{{ __('task.notes') }}</span>
            </div>
            <div class="flex flex-col gap-2.5 p-4">
                <textarea wire:model="notes" rows="4" class="dbena-input text-[12px]"
                          style="resize: vertical"
                          placeholder="{{ __('task.notes_placeholder') }}"
                          aria-label="{{ __('task.notes') }}"></textarea>

                <input type="text" wire:model="preparedBy" list="senarai-pic" class="dbena-input text-[12px]"
                       placeholder="{{ __('task.prepared_by') }}" aria-label="{{ __('task.prepared_by') }}">

                <button type="button" wire:click="saveBoard"
                        class="dbena-btn-gold flex items-center justify-center gap-1.5 px-3 py-2 text-[12px]">
                    <i class="ph-duotone ph-floppy-disk text-sm" aria-hidden="true"></i>
                    {{ __('task.save_board') }}
                </button>
            </div>
        </div>
    </div>

    {{-- ══ Jalur kaki ══ --}}
    <div class="flex flex-wrap items-center justify-center gap-x-10 gap-y-2 rounded-xl px-5 py-3"
         style="background: linear-gradient(115deg, {{ $marunGelap }}, {{ $marun }})">
        @foreach ([
            ['ph-clipboard-text', __('task.footer.plan')],
            ['ph-users-three', __('task.footer.work')],
            ['ph-target', __('task.footer.achieve')],
        ] as [$ikon, $teks])
            <span class="flex items-center gap-2 text-[12px] font-extrabold tracking-wide text-white">
                <i class="ph-duotone {{ $ikon }} text-base" style="color: {{ $emas }}" aria-hidden="true"></i>
                {{ $teks }}
            </span>
        @endforeach
    </div>
</div>
