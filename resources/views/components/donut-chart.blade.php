@props([
    'pct' => 0,
    'size' => 210,
    'hole' => 158,
    'label' => null,
    'sublabel' => null,
    'valueSize' => '30px',
])

@php
    $clamped = min(100, max(0, (float) $pct));
    $gradient = "conic-gradient(oklch(0.6 0.22 350) 0% {$clamped}%, var(--track-bg) {$clamped}% 100%)";
@endphp

<div class="relative flex shrink-0 items-center justify-center rounded-full"
     role="img"
     aria-label="{{ $label }} {{ number_format($clamped, 1) }}%"
     style="width: {{ $size }}px; height: {{ $size }}px; background: {{ $gradient }}">
    <div class="flex flex-col items-center justify-center rounded-full bg-card"
         style="width: {{ $hole }}px; height: {{ $hole }}px">
        <div class="font-display font-extrabold" style="font-size: {{ $valueSize }}; color: oklch(0.78 0.12 85)">
            {{ number_format($clamped, 1) }}%
        </div>
        @if ($label)
            <div class="mt-1 px-2 text-center text-[10.5px] leading-tight text-t60">
                {{ $label }}
                @if ($sublabel)<br><span class="italic">{{ $sublabel }}</span>@endif
            </div>
        @endif
    </div>
</div>
