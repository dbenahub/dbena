@php
    $senarai = collect($cal['events'])
        ->filter(fn (array $e) => $e['date']->isSameDay($focusDate))
        ->values();
@endphp

<div class="p-4 sm:p-5">
    <div class="mb-3 flex items-baseline gap-2.5">
        <span class="text-[22px] font-extrabold text-t94">{{ $focusDate->day }}</span>
        <span class="text-[13px] font-semibold text-t70">{{ $focusDate->translatedFormat('l, j F Y') }}</span>
        @if ($focusDate->isToday())
            <span class="rounded px-2 py-0.5 text-[10px] font-extrabold"
                  style="background: oklch(0.30 0.13 350); color: #fff">{{ __('calendar_task.today') }}</span>
        @endif
    </div>

    <div class="flex flex-col gap-2">
        @forelse ($senarai as $acara)
            <div class="flex items-start gap-3 rounded-xl p-3"
                 style="background: var(--hover-bg3); border-left: 4px solid {{ $acara['mark']->color() }}">
                <span class="w-[62px] shrink-0 text-[12px] font-extrabold text-t85">
                    {{ $acara['time'] ?? __('calendar_task.all_day') }}
                </span>

                <div class="min-w-0 flex-1">
                    <div class="text-[13px] font-semibold text-t90">{{ $acara['title'] }}</div>
                    <div class="mt-0.5 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-[11px] text-t60">
                        <span class="rounded px-1.5 py-0.5 text-[9.5px] font-extrabold"
                              style="background: {{ $acara['mark']->color() }}; color: {{ $acara['mark']->textColor() }}">
                            {{ $acara['mark']->label() }}
                        </span>
                        @if ($acara['department'])
                            <span>{{ $acara['department'] }}</span>
                        @endif
                        @if ($acara['remark'])
                            <span class="text-t50">· {{ $acara['remark'] }}</span>
                        @endif
                    </div>
                </div>

                @if ($acara['pic'])
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[10px] font-extrabold text-white"
                          style="background: {{ $acara['picColor'] }}" title="{{ $acara['pic'] }}">
                        {{ mb_strtoupper(mb_substr($acara['pic'], 0, 2)) }}
                    </span>
                @endif
            </div>
        @empty
            <div class="flex flex-col items-center gap-2 py-10 text-center">
                <i class="ph-duotone ph-calendar-blank text-[28px] text-t50" aria-hidden="true"></i>
                <span class="text-[12.5px] text-t60">{{ __('calendar_task.no_events') }}</span>
                <button type="button" wire:click="openAdd({{ $focusDate->day }})"
                        class="dbena-btn-gold mt-1 px-3.5 py-2 text-[12px]">{{ __('calendar_task.add_task') }}</button>
            </div>
        @endforelse
    </div>
</div>
