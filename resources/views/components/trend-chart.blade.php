@props([
    'chart' => ['bars' => [], 'dots' => [], 'linePoints' => '', 'maxLabel' => '0'],
    'height' => '320px',
])

@php
    $bars = $chart['bars'] ?? [];
    $dots = $chart['dots'] ?? [];
    $linePoints = $chart['linePoints'] ?? '';
    $maxLabel = $chart['maxLabel'] ?? '0';
    $maxNum = (float) str_replace(',', '', (string) $maxLabel);
    $fmt = fn (float $n) => number_format(round($n));
@endphp

{{-- Port TrendChart.dc.html — bar jualan sebenar + garis sasaran SVG --}}
<div class="flex w-full flex-col font-sans text-t96" style="height: {{ $height }}">

    {{-- Legend --}}
    <div class="mb-3.5 flex flex-wrap items-center gap-x-[18px] gap-y-2 text-[12px] text-t60">
        <div class="flex items-center gap-1.5">
            <div class="h-2.5 w-2.5 rounded-[3px]" style="background: oklch(0.6 0.22 350)"></div>
            <span>{{ __('dashboard.chart.actual_sales') }}</span>
        </div>
        <div class="flex items-center gap-1.5">
            <div class="h-2.5 w-2.5 rounded-full" style="background: oklch(0.78 0.12 85)"></div>
            <span>{{ __('dashboard.chart.target') }}</span>
        </div>
    </div>

    <div class="flex min-h-0 flex-1 gap-2.5">
        {{-- Paksi-Y : max, 75%, 50%, 25%, 0 --}}
        <div class="hidden flex-col justify-between py-1.5 text-[11px] text-t50 sm:flex">
            <span>{{ $maxLabel }}</span>
            <span>{{ $fmt($maxNum * 0.75) }}</span>
            <span>{{ $fmt($maxNum * 0.5) }}</span>
            <span>{{ $fmt($maxNum * 0.25) }}</span>
            <span>0</span>
        </div>

        {{-- Kawasan plot --}}
        <div class="relative flex flex-1 items-end gap-1.5 border-b border-l px-1.5 pt-1.5 sm:gap-3.5 sm:px-2.5"
             style="border-color: var(--border2)">

            {{-- Garis + titik sasaran (viewBox 1180x380, sama seperti prototaip) --}}
            <svg viewBox="0 0 1180 380" preserveAspectRatio="none" aria-hidden="true"
                 class="pointer-events-none absolute inset-0 h-full w-full overflow-visible">
                @if ($linePoints)
                    <polyline points="{{ $linePoints }}" fill="none"
                              stroke="oklch(0.78 0.12 85)" stroke-width="2.5"></polyline>
                @endif
                @foreach ($dots as $dot)
                    <circle cx="{{ $dot['x'] }}" cy="{{ $dot['y'] }}" r="4.5"
                            fill="var(--input-bg)" stroke="oklch(0.78 0.12 85)" stroke-width="2"></circle>
                @endforeach
            </svg>

            {{-- Bar jualan sebenar --}}
            @foreach ($bars as $bar)
                <div class="relative z-[1] flex h-full flex-1 items-end justify-center">
                    @if ($bar['hasValue'])
                        <div class="w-[60%] rounded-t"
                             title="{{ $bar['valueLabel'] }}"
                             style="background: oklch(0.6 0.22 350); height: {{ $bar['pctHeight'] }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Label paksi-X --}}
    <div class="flex gap-1.5 pt-2 pl-0 sm:gap-3.5 sm:pl-[34px]">
        @foreach ($bars as $bar)
            <div class="flex-1 text-center text-[10px] text-t55 sm:text-[11px]">{{ $bar['label'] }}</div>
        @endforeach
    </div>
</div>
