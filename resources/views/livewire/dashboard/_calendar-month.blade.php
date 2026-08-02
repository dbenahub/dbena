<div class="overflow-x-auto">
    <div class="min-w-[720px]">
        <div class="grid grid-cols-7" style="border-bottom: 1px solid var(--border3)">
            @foreach (__('calendar.days_full') as $h)
                <div class="px-2 py-2 text-center text-[10.5px] font-extrabold tracking-wide text-t60">{{ $h }}</div>
            @endforeach
        </div>

        @foreach ($cal['grid'] as $minggu)
            <div class="grid grid-cols-7" style="border-bottom: 1px solid var(--border3)">
                @foreach ($minggu as $sel)
                    {{-- Hari daripada bulan lain kekal dalam grid supaya minggu
                         utuh, tetapi dipudarkan: grid yang bermula pada 1
                         haribulan tanpa mengira hari apa ia jatuh bukan
                         kalendar, ia jadual. --}}
                    <button type="button"
                            wire:click="selectDay({{ $sel['date']->year }}, {{ $sel['date']->month }}, {{ $sel['day'] }})"
                            class="flex min-h-[92px] flex-col gap-1 p-1.5 text-left transition-colors hover:bg-hover"
                            style="border-right: 1px solid var(--border3);
                                   {{ $sel['isWeekend'] ? 'background: var(--hover-bg3)' : '' }}">
                        <span class="flex items-center gap-1">
                            <span class="flex h-[19px] min-w-[19px] items-center justify-center rounded-full px-1 text-[10.5px] font-bold"
                                  style="{{ $sel['isToday']
                                        ? 'background: oklch(0.30 0.13 350); color: #fff'
                                        : 'color: '.($sel['inMonth'] ? 'var(--t80)' : 'var(--t45)') }}">
                                {{ $sel['day'] }}
                            </span>
                        </span>

                        @foreach ($sel['events']->take(3) as $acara)
                            <span class="block truncate rounded px-1.5 py-1 text-[9.5px] font-semibold leading-tight"
                                  style="background: {{ $acara['mark']->color() }};
                                         color: {{ $acara['mark']->textColor() }}"
                                  title="{{ $acara['title'] }}{{ $acara['pic'] ? ' — '.$acara['pic'] : '' }}">
                                @if ($acara['time'])
                                    <span class="font-extrabold">{{ $acara['time'] }}</span>
                                @endif
                                {{ $acara['title'] }}
                            </span>
                        @endforeach

                        @if ($sel['events']->count() > 3)
                            <span class="text-[9.5px] font-bold text-t55">+{{ $sel['events']->count() - 3 }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
