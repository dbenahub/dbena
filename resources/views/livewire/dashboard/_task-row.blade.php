@php
    $sedangSunting = $editingId === $task->id;
    $tandaIkutHari = $task->marks->keyBy('day');
@endphp

<div class="flex items-stretch" style="border-top: 1px solid var(--border3)" wire:key="task-{{ $task->id }}">

    <div class="flex shrink-0 items-center justify-center text-[11.5px] font-bold text-t70"
         style="width: 46px">{{ $bil }}</div>

    @if ($sedangSunting)
        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2 px-3 py-2.5">
            <input type="text" wire:model="editTitle" wire:keydown.enter="saveTask"
                   class="dbena-input min-w-0 flex-1" style="min-width: 220px"
                   aria-label="{{ __('task.col.task') }}">
            <input type="text" wire:model="editActionBy" list="senarai-pic" class="dbena-input w-[120px]"
                   placeholder="{{ __('task.col.action_by') }}" aria-label="{{ __('task.col.action_by') }}">
            <input type="text" wire:model="editMonitorBy" list="senarai-pic" class="dbena-input w-[120px]"
                   placeholder="{{ __('task.col.monitor_by') }}" aria-label="{{ __('task.col.monitor_by') }}">
            <input type="text" wire:model="editRemark" class="dbena-input w-[180px]"
                   placeholder="{{ __('task.col.remark') }}" aria-label="{{ __('task.col.remark') }}">

            <button type="button" wire:click="saveTask"
                    class="dbena-btn-gold px-3 py-1.5 text-[11.5px]">{{ __('task.save') }}</button>
            <button type="button" wire:click="cancelEdit"
                    class="rounded-[9px] px-3 py-1.5 text-[11.5px] font-semibold text-t75"
                    style="border: 1px solid var(--border2)">{{ __('task.cancel') }}</button>

            @can('delete-monthly-task')
                <button type="button" wire:click="deleteTask({{ $task->id }})"
                        wire:confirm="{{ __('task.delete') }}?"
                        class="rounded-[9px] px-3 py-1.5 text-[11.5px] font-semibold"
                        style="border: 1px solid oklch(0.6 0.2 25/0.45); color: oklch(0.72 0.16 25)">
                    {{ __('task.delete') }}
                </button>
            @endcan
        </div>
    @else
        <button type="button" wire:click="startEdit({{ $task->id }})"
                class="shrink-0 px-3 py-2.5 text-left transition-colors hover:bg-hover"
                style="width: 260px" title="{{ __('task.edit') }}">
            <span class="block text-[11.5px] leading-snug text-t90">{{ $task->title }}</span>
        </button>

        <div class="flex shrink-0 items-center justify-center px-2 text-[11.5px] text-t80"
             style="width: 110px">{{ $task->action_by ?: '—' }}</div>
        <div class="flex shrink-0 items-center justify-center px-2 text-[11.5px] text-t80"
             style="width: 110px">{{ $task->monitor_by ?: '—' }}</div>

        {{-- ══ Petak hari ══
             Setiap petak ialah <select> tersembunyi di atas label berwarna.
             Sebuah menu tersuai akan kelihatan lebih kemas dan gagal pada
             telefon; <select> asli membuka pemilih asli peranti, yang
             pengguna sudah tahu guna. --}}
        <div class="flex shrink-0" style="width: {{ count($days) * 26 }}px">
            @foreach ($days as $d)
                @php
                    $tanda = $tandaIkutHari->get($d)?->mark;
                    $minggu = in_array($d, $weekDays, true);
                @endphp

                <div class="relative" style="width: 26px; border-left: 1px solid var(--border3)"
                     wire:key="cell-{{ $task->id }}-{{ $d }}">
                    <div class="flex h-full items-center justify-center py-1.5"
                         style="{{ $minggu && ! $tanda ? 'background: oklch(0.82 0.14 85/0.10)' : '' }}">
                        @if ($tanda)
                            <span class="flex h-[19px] min-w-[19px] items-center justify-center rounded px-0.5 text-[9.5px] font-extrabold"
                                  style="background: {{ $tanda->color() }}; color: {{ $tanda->textColor() }}">
                                {{ $tanda->letter() }}
                            </span>
                        @else
                            <span class="text-[10px] text-t45">·</span>
                        @endif
                    </div>

                    <select wire:change="setMark({{ $task->id }}, {{ $d }}, $event.target.value)"
                            class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                            aria-label="{{ $task->title }} — {{ $d }}">
                        <option value="" @selected(! $tanda)>{{ __('task.mark_clear') }}</option>
                        @foreach ($marks as $m)
                            <option value="{{ $m->value }}" @selected($tanda === $m)>
                                {{ $m->letter() }} — {{ $m->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endforeach
        </div>

        <button type="button" wire:click="startEdit({{ $task->id }})"
                class="min-w-0 flex-1 px-3 py-2.5 text-left text-[11px] leading-snug text-t70 transition-colors hover:bg-hover">
            {{ $task->remark ?: '—' }}
        </button>
    @endif
</div>
