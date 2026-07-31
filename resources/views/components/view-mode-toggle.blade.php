@props(['mode' => 'monthly'])

<div class="flex shrink-0 gap-0.5 rounded-[9px] bg-hover3 p-[3px]" role="group"
     aria-label="{{ __('dashboard.view_mode_label') }}">
    @foreach (['monthly', 'yearly'] as $option)
        @php $isActive = $mode === $option; @endphp
        <button type="button" wire:click="$set('viewMode', '{{ $option }}')"
                aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                class="rounded-[7px] px-3.5 py-[7px] text-[12.5px] font-semibold whitespace-nowrap transition-colors"
                @style([
                    'background: oklch(0.78 0.12 85); color: oklch(0.15 0.02 260)' => $isActive,
                    'background: transparent; color: var(--t65)' => ! $isActive,
                ])>
            {{ __('dashboard.view_mode.'.$option) }}
        </button>
    @endforeach
</div>
