<div x-data="{ open: false }" class="relative" x-on:click.outside="open = false">
    <button type="button"
            x-on:click="open = !open; if (open) $wire.markRead()"
            :aria-expanded="open.toString()"
            aria-label="{{ __('dashboard.notifications') }}"
            class="relative flex h-[38px] w-[38px] items-center justify-center rounded-[10px] transition-colors hover:bg-hover3"
            style="border: 1px solid var(--border2)">
        <i class="ph-duotone ph-bell text-xl text-t85" aria-hidden="true"></i>
        @if (count($items) > 0 && ! $read)
            <span class="absolute right-[7px] top-[6px] h-2 w-2 rounded-full"
                  style="background: oklch(0.55 0.22 350)"></span>
        @endif
    </button>

    <div x-show="open" x-cloak x-transition
         class="absolute right-0 top-[46px] z-30 max-h-[70vh] w-[300px] overflow-y-auto rounded-xl bg-card shadow-2xl sm:w-[320px]"
         style="border: 1px solid var(--border2)">
        <div class="border-b px-4 py-3.5 text-[13px] font-bold" style="border-color: var(--border)">
            {{ __('dashboard.notifications') }}
        </div>

        @forelse ($items as $item)
            @if ($item['url'])
                <a href="{{ $item['url'] }}" wire:navigate
                   class="flex gap-3 border-b px-4 py-3 transition-colors hover:bg-hover"
                   style="border-color: var(--border3)">
                    <i class="ph-duotone {{ $item['icon'] }} mt-0.5 shrink-0 text-xl"
                       style="color: {{ $item['color'] }}" aria-hidden="true"></i>
                    <div>
                        <div class="text-[12.5px] leading-snug text-t92">{{ $item['text'] }}</div>
                        <div class="mt-0.5 text-[11px] text-t50">{{ $item['time'] }}</div>
                    </div>
                </a>
            @else
                <div class="flex gap-3 border-b px-4 py-3" style="border-color: var(--border3)">
                    <i class="ph-duotone {{ $item['icon'] }} mt-0.5 shrink-0 text-xl"
                       style="color: {{ $item['color'] }}" aria-hidden="true"></i>
                    <div>
                        <div class="text-[12.5px] leading-snug text-t92">{{ $item['text'] }}</div>
                        <div class="mt-0.5 text-[11px] text-t50">{{ $item['time'] }}</div>
                    </div>
                </div>
            @endif
        @empty
            <div class="px-4 py-6 text-center text-[12.5px] text-t55">
                {{ __('dashboard.all_clear') }}
            </div>
        @endforelse
    </div>
</div>
