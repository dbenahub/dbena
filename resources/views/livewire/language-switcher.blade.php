<div class="flex shrink-0 gap-0.5 rounded-[9px] p-[3px]" style="background: var(--hover-bg3)"
     role="group" aria-label="{{ __('app.language') }}">
    @foreach (['ms' => 'BM', 'en' => 'EN'] as $code => $label)
        @php $isActive = $locale === $code; @endphp
        <button type="button" wire:click="switchTo('{{ $code }}')"
                aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                class="rounded-[7px] px-2.5 py-1.5 text-[12px] font-bold transition-colors"
                @style([
                    'background: oklch(0.78 0.12 85); color: oklch(0.15 0.02 260)' => $isActive,
                    'color: var(--t60)' => ! $isActive,
                ])>
            {{ $label }}
        </button>
    @endforeach
</div>
