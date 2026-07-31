@props(['on' => false, 'label' => null, 'sublabel' => null])

@php
    $bg = $on ? 'oklch(0.72 0.15 145)' : 'var(--switch-off)';
    $left = $on ? '18px' : '2px';
@endphp

<div class="flex items-center justify-between gap-4">
    @if ($label)
        <div>
            <div class="text-[13.5px] font-semibold">{{ $label }}</div>
            @if ($sublabel)<div class="text-[11.5px] italic text-t55">{{ $sublabel }}</div>@endif
        </div>
    @endif
    <button type="button" role="switch" aria-checked="{{ $on ? 'true' : 'false' }}"
            aria-label="{{ $label }}"
            {{ $attributes->merge(['class' => 'relative shrink-0 cursor-pointer rounded-xl transition-colors touch-target']) }}
            style="width: 38px; height: 22px; background: {{ $bg }}">
        <span class="absolute rounded-full bg-white transition-[left] duration-150"
              style="top: 2px; left: {{ $left }}; width: 18px; height: 18px"></span>
    </button>
</div>
