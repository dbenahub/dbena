@props([
    'icon' => 'ph-wallet',
    'iconBg' => 'oklch(0.55 0.22 350/0.18)',
    'iconColor' => 'oklch(0.6 0.22 350)',
    'label' => '',
    'value' => '',
    'changeLabel' => null,
    'changeColor' => 'var(--t60)',
])

<div class="dbena-card flex flex-1 items-center gap-4 px-5 py-[18px]">
    <div class="flex h-[52px] w-[52px] shrink-0 items-center justify-center rounded-full"
         style="background: {{ $iconBg }}">
        <i class="ph-duotone {{ $icon }} text-[26px]" style="color: {{ $iconColor }}" aria-hidden="true"></i>
    </div>
    <div class="min-w-0 flex-1">
        <div class="text-[13px] text-t60">{{ $label }}</div>
        <div class="mt-0.5 truncate text-[22px] font-bold">{{ $value }}</div>
    </div>
    @if ($changeLabel !== null)
        <div class="shrink-0 text-right text-[12px] font-semibold" style="color: {{ $changeColor }}">
            <div class="text-[11px] font-normal text-t50">{{ __('dashboard.vs_target') }}</div>
            {{ $changeLabel }}
        </div>
    @endif
</div>
