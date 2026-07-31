@props(['title' => '', 'subtitle' => null, 'targetLabel' => null, 'bars' => []])

<div>
    <div class="mb-2.5 flex flex-wrap items-center justify-between gap-2">
        <div class="text-[12.5px] font-semibold text-t80">
            {{ $title }}
            @if ($subtitle)<span class="font-normal italic text-t55"> {{ $subtitle }}</span>@endif
        </div>
        @if ($targetLabel !== null)
            <div class="text-[11px] text-t55">
                {{ __('service.target_short') }}: <b class="text-t80">{{ $targetLabel }}</b>/{{ __('service.per_week') }}
            </div>
        @endif
    </div>

    <div class="relative flex h-[120px] items-end gap-3 border-b border-l px-2 pt-2"
         style="border-color: var(--border2)">
        {{-- Garis sasaran mingguan --}}
        <div class="absolute left-0 right-0 top-2 border-t-2 border-dashed"
             style="border-color: oklch(0.78 0.12 85/0.6)" aria-hidden="true"></div>

        @foreach ($bars as $bar)
            <div class="flex h-full flex-1 flex-col items-center justify-end gap-1.5">
                <div class="whitespace-nowrap text-[10.5px] font-bold text-t80">{{ $bar['actualLabel'] }}</div>
                <div class="w-[56%] rounded-t" title="{{ $bar['actualLabel'] }}"
                     style="background: {{ $bar['barColor'] }}; height: {{ $bar['pctHeight'] }}; min-height: 2px"></div>
                <div class="text-[10.5px] text-t55">{{ $bar['label'] }}</div>
            </div>
        @endforeach
    </div>
</div>
