@props(['color' => 'var(--t60)', 'size' => '8px', 'label' => null, 'sublabel' => null])

<div class="flex items-center gap-[7px]">
    <div class="shrink-0 rounded-full" style="width: {{ $size }}; height: {{ $size }}; background: {{ $color }}"></div>
    @if ($label)
        <div class="text-[12px] leading-tight">
            <div>{{ $label }}</div>
            @if ($sublabel)<div class="italic text-t55">{{ $sublabel }}</div>@endif
        </div>
    @endif
</div>
