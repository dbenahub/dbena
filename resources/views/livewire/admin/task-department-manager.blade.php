<div class="flex flex-col gap-5">

    <div class="dbena-card p-5">
        <h2 class="mb-1 text-base font-bold">{{ __('task.admin.add') }}</h2>
        <p class="mb-3 text-[12px] text-t60">{{ __('task.admin.note') }}</p>

        <div class="flex flex-wrap items-end gap-2.5">
            <div class="min-w-0 flex-1" style="min-width: 220px">
                <label for="dept-name" class="mb-1.5 block text-[11.5px] text-t60">{{ __('task.admin.name') }}</label>
                <input id="dept-name" type="text" wire:model="newName" wire:keydown.enter="add"
                       class="dbena-input" placeholder="SALES &amp; MARKETING">
            </div>

            <div>
                <label for="dept-icon" class="mb-1.5 block text-[11.5px] text-t60">{{ __('org.editor.field_icon') }}</label>
                <select id="dept-icon" wire:model="newIcon" class="dbena-input w-[190px]">
                    @foreach ([
                        'ph-megaphone' => 'Pemasaran', 'ph-gear' => 'Operasi',
                        'ph-briefcase' => 'Kontrak', 'ph-folder' => 'Projek',
                        'ph-users-three' => 'HR', 'ph-calculator' => 'Kewangan',
                        'ph-buildings' => 'Produksi', 'ph-wrench' => 'Teknikal',
                    ] as $kelas => $label)
                        <option value="{{ $kelas }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <button type="button" wire:click="add"
                    class="dbena-btn-gold flex items-center gap-2 px-4 py-2.5 text-[12.5px]">
                <i class="ph-duotone ph-plus-circle text-base" aria-hidden="true"></i>
                {{ __('task.admin.add') }}
            </button>
        </div>
    </div>

    <div class="dbena-card overflow-hidden">
        <div class="overflow-x-auto">
            <div class="min-w-[720px]">
                <div class="flex px-4 py-2.5 text-[11px] font-extrabold uppercase tracking-wide text-t60"
                     style="background: var(--hover-bg3)">
                    <div style="width: 70px">#</div>
                    <div class="flex-1">{{ __('task.admin.name') }}</div>
                    <div style="width: 180px">{{ __('org.editor.field_icon') }}</div>
                    <div style="width: 110px">{{ __('task.admin.active') }}</div>
                    <div style="width: 150px"></div>
                </div>

                @foreach ($rows as $i => $row)
                    <div class="flex items-center gap-2 px-4 py-2.5"
                         style="border-top: 1px solid var(--border3)" wire:key="dept-{{ $row['id'] }}">
                        <input type="number" min="0" wire:model="rows.{{ $i }}.sort_order"
                               class="dbena-input" style="width: 60px"
                               aria-label="#">

                        <input type="text" wire:model="rows.{{ $i }}.name" class="dbena-input min-w-0 flex-1"
                               aria-label="{{ __('task.admin.name') }}">

                        <select wire:model="rows.{{ $i }}.icon" class="dbena-input" style="width: 170px"
                                aria-label="{{ __('org.editor.field_icon') }}">
                            @foreach ([
                                'ph-megaphone' => 'Pemasaran', 'ph-gear' => 'Operasi',
                                'ph-briefcase' => 'Kontrak', 'ph-folder' => 'Projek',
                                'ph-users-three' => 'HR', 'ph-calculator' => 'Kewangan',
                                'ph-buildings' => 'Produksi', 'ph-wrench' => 'Teknikal',
                            ] as $kelas => $label)
                                <option value="{{ $kelas }}">{{ $label }}</option>
                            @endforeach
                        </select>

                        <label class="flex items-center gap-1.5 text-[11.5px] text-t75" style="width: 100px">
                            <input type="checkbox" wire:model="rows.{{ $i }}.active">
                            {{ __('task.admin.active') }}
                        </label>

                        <span class="text-[11px] text-t55" style="width: 90px">
                            {{ __('task.admin.has_tasks', ['count' => (int) ($counts[$row['id']] ?? 0)]) }}
                        </span>

                        <button type="button" wire:click="remove({{ $row['id'] }})"
                                class="shrink-0 text-t55 transition-colors hover:text-t90"
                                aria-label="{{ __('task.delete') }}">
                            <i class="ph-duotone ph-trash text-sm" aria-hidden="true"></i>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 px-4 py-3" style="border-top: 1px solid var(--border3)">
            <button type="button" wire:click="save"
                    class="dbena-btn-gold flex items-center gap-2 px-4 py-2.5 text-[12.5px]">
                <i class="ph-duotone ph-floppy-disk text-base" aria-hidden="true"></i>
                {{ __('task.save') }}
            </button>

            <span class="text-[11.5px] text-t55">{{ __('task.admin.pic_note') }}</span>
        </div>
    </div>
</div>
