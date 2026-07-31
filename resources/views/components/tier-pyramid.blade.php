@props(['tiers' => []])

{{-- Tersusun Sustainability → Critical, lebar 100% − i×16% --}}
<div class="mb-6 flex flex-col items-center gap-0.5">
    @foreach ($tiers as $tier)
        <div class="relative flex w-full items-center justify-center">
            <div class="flex items-center justify-center gap-2 rounded-md px-3 py-2.5 text-center text-[11px] font-bold text-white sm:px-4 sm:py-3 sm:text-[13px]"
                 style="width: {{ $tier['widthPct'] }}; background: {{ $tier['color'] }}">
                <span class="truncate">{{ $tier['name'] }}</span>
            </div>
            @if ($tier['isCurrent'])
                <div class="absolute right-1 flex items-center gap-1.5 rounded-md bg-card px-1.5 py-0.5 text-[10px] font-bold whitespace-nowrap sm:right-2 sm:px-2 sm:py-[3px] sm:text-[11.5px]"
                     style="color: {{ $tier['color'] }}; border: 1px solid {{ $tier['color'] }}">
                    <i class="ph-duotone ph-map-pin text-[14px]" aria-hidden="true"></i>
                    <span class="hidden sm:inline">{{ __('dashboard.you_are_here') }}</span>
                </div>
            @endif
        </div>
    @endforeach
</div>
