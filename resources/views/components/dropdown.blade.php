@props(['align' => 'right', 'width' => '220px'])

<div x-data="{ open: false }" class="relative" x-on:click.outside="open = false">
    <div x-on:click="open = !open" :aria-expanded="open.toString()" aria-haspopup="true">
        {{ $trigger }}
    </div>
    <div x-show="open" x-cloak x-transition
         class="absolute top-[46px] z-30 overflow-hidden rounded-xl bg-card shadow-2xl {{ $align === 'left' ? 'left-0' : 'right-0' }}"
         style="border: 1px solid var(--border2); width: {{ $width }}">
        {{ $slot }}
    </div>
</div>
