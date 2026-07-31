@props([
    'pct' => 0,
    'color' => 'oklch(0.72 0.15 145)',
    'height' => '7px',
    'showLabel' => true,
    'labelWidth' => '44px',
    'decimals' => 1,
])

@php $clamped = min(100, max(0, (float) $pct)); @endphp

<div class="flex items-center gap-2.5">
    <div class="flex-1 overflow-hidden rounded bg-track" style="height: {{ $height }}"
         role="progressbar" aria-valuenow="{{ round($clamped) }}" aria-valuemin="0" aria-valuemax="100">
        <div class="h-full rounded transition-[width] duration-300"
             style="background: {{ $color }}; width: {{ $clamped }}%"></div>
    </div>
    @if ($showLabel)
        <div class="shrink-0 text-right text-[12.5px] font-semibold" style="width: {{ $labelWidth }}">
            {{ number_format((float) $pct, $decimals) }}%
        </div>
    @endif
</div>
