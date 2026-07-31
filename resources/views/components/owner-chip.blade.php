@props(['name' => '', 'color' => 'var(--t60)', 'selected' => false, 'removable' => false])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 rounded-[7px] px-3 py-1.5 text-[13px] font-bold']) }}
      style="background: color-mix(in oklch, {{ $color }} 18%, transparent); color: {{ $color }};
             border: 1px solid color-mix(in oklch, {{ $color }} {{ $selected ? 60 : 40 }}%, transparent)">
    {{ $name }}
    @if ($selected)<i class="ph-duotone ph-check text-[14px]" aria-hidden="true"></i>@endif
    {{ $slot }}
</span>
