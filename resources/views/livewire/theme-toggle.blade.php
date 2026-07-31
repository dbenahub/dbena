@php $isDark = $theme === 'dark'; @endphp

<div class="flex items-center justify-between">
    <div class="flex items-center gap-2.5">
        <i class="ph-duotone {{ $isDark ? 'ph-moon' : 'ph-sun' }} text-xl"
           style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
        <span class="text-[13px] text-t68">{{ $isDark ? __('app.dark_mode') : __('app.light_mode') }}</span>
    </div>
    <button type="button" wire:click="toggle" role="switch"
            aria-checked="{{ $isDark ? 'true' : 'false' }}"
            aria-label="{{ __('app.dark_mode') }}"
            class="relative shrink-0 cursor-pointer rounded-xl transition-colors"
            style="width: 38px; height: 22px; background: {{ $isDark ? 'oklch(0.72 0.15 145)' : 'var(--switch-off)' }}">
        <span class="absolute rounded-full bg-white transition-[left] duration-150"
              style="top: 2px; left: {{ $isDark ? '18px' : '2px' }}; width: 18px; height: 18px"></span>
    </button>
</div>
