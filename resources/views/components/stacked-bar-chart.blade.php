@props(['bars' => [], 'legend' => []])

<div>
    <div class="mb-4 flex flex-wrap gap-3.5">
        @foreach ($legend as $item)
            <div class="flex items-center gap-1.5 text-[11.5px] text-t65">
                <div class="h-[9px] w-[9px] rounded-sm" style="background: {{ $item['color'] }}"></div>
                {{ $item['name'] }}
            </div>
        @endforeach
    </div>

    <div class="flex h-[200px] items-end gap-2 border-b border-l px-2 sm:gap-4"
         style="border-color: var(--border2)">
        @foreach ($bars as $bar)
            <div class="flex h-full flex-1 flex-col items-center justify-end gap-1.5">
                <div class="hidden text-[10.5px] text-t55 sm:block">{{ $bar['totalLabel'] }}</div>
                <div class="flex w-[60%] flex-col-reverse overflow-hidden rounded-t"
                     style="height: {{ $bar['totalPct'] }}"
                     title="{{ $bar['label'] }} — {{ $bar['totalLabel'] }}">
                    @foreach ($bar['segments'] as $segment)
                        <div style="flex: {{ $segment['flexVal'] }} 1 0%; background: {{ $segment['color'] }}"></div>
                    @endforeach
                </div>
                <div class="text-[11px] text-t60">{{ $bar['label'] }}</div>
            </div>
        @endforeach
    </div>
</div>
