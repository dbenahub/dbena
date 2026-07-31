@props(['months' => [], 'active' => 1, 'wireMethod' => 'selectMonth', 'maxWidth' => null])

<div class="snap-row -mx-1 flex gap-1.5 overflow-x-auto px-1 pb-1"
     @if ($maxWidth) style="max-width: {{ $maxWidth }}" @endif
     role="group" aria-label="{{ __('dashboard.select_month') }}">
    @foreach ($months as $index => $label)
        @php $isActive = ($index + 1) === (int) $active; @endphp
        <button type="button"
                wire:click="{{ $wireMethod }}({{ $index + 1 }})"
                aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                class="touch-target shrink-0 rounded-lg px-3.5 py-[7px] text-[12.5px] font-semibold transition-colors"
                @style([
                    'background: oklch(0.78 0.12 85); color: oklch(0.15 0.02 260)' => $isActive,
                    'background: var(--hover-bg3); color: var(--t65)' => ! $isActive,
                ])>
            {{ $label }}
        </button>
    @endforeach
</div>
