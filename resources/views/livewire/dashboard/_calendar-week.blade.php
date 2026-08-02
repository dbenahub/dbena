@php
    $ikutTarikh = collect($cal['events'])->groupBy(fn (array $e) => $e['date']->toDateString());
@endphp

<div class="overflow-x-auto">
    <div class="grid min-w-[720px] grid-cols-7">
        @foreach ($weekDays as $hari)
            @php
                $senarai = $ikutTarikh->get($hari->toDateString(), collect());
                $iniHariIni = $hari->isToday();
            @endphp

            <div style="border-right: 1px solid var(--border3)">
                <div class="px-2 py-2 text-center"
                     style="border-bottom: 1px solid var(--border3);
                            {{ $iniHariIni ? 'background: oklch(0.30 0.13 350)' : '' }}">
                    <div class="text-[10px] font-extrabold tracking-wide"
                         style="color: {{ $iniHariIni ? '#fff' : 'var(--t60)' }}">
                        {{ mb_strtoupper($hari->translatedFormat('D')) }}
                    </div>
                    <div class="text-[15px] font-extrabold"
                         style="color: {{ $iniHariIni ? '#fff' : 'var(--t90)' }}">{{ $hari->day }}</div>
                </div>

                <div class="flex min-h-[280px] flex-col gap-1.5 p-2">
                    @forelse ($senarai as $acara)
                        <div class="rounded-lg px-2 py-1.5"
                             style="background: {{ $acara['mark']->color() }}; color: {{ $acara['mark']->textColor() }}">
                            <div class="text-[9.5px] font-extrabold">
                                {{ $acara['time'] ?? __('calendar_task.all_day') }}
                            </div>
                            <div class="text-[10.5px] font-semibold leading-snug">{{ $acara['title'] }}</div>
                            @if ($acara['pic'])
                                <div class="mt-0.5 text-[9px] opacity-80">{{ $acara['pic'] }}</div>
                            @endif
                        </div>
                    @empty
                        <span class="text-[10px] text-t45">—</span>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
