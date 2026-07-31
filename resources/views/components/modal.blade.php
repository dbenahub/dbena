@props(['show' => 'false', 'title' => '', 'icon' => null, 'maxWidth' => '640px'])

{{-- Full-screen pada mobile, kad berpusat pada desktop --}}
<div x-show="{{ $show }}"
     x-cloak
     x-transition.opacity
     class="fixed inset-0 z-[110] flex items-end justify-center bg-black/55 sm:items-center sm:p-4"
     x-on:keydown.escape.window="{{ $show }} = false"
     role="dialog"
     aria-modal="true"
     aria-label="{{ $title }}">
    <div x-on:click.outside="{{ $show }} = false"
         class="flex max-h-[90vh] w-full flex-col rounded-t-2xl bg-card p-5 shadow-2xl sm:max-h-[80vh] sm:rounded-2xl sm:p-6"
         style="border: 1px solid var(--border2); max-width: {{ $maxWidth }}">
        <div class="mb-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-2.5">
                @if ($icon)
                    <i class="ph-duotone {{ $icon }} text-xl" style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
                @endif
                <h2 class="text-[15.5px] font-bold">{{ $title }}</h2>
            </div>
            <button type="button" x-on:click="{{ $show }} = false"
                    class="touch-target -mr-2 flex items-center justify-center text-t55 hover:text-t90"
                    aria-label="{{ __('app.close') }}">
                <i class="ph-duotone ph-x text-xl" aria-hidden="true"></i>
            </button>
        </div>
        <div class="min-h-0 flex-1 overflow-y-auto">{{ $slot }}</div>
        @isset($footer)
            <div class="mt-4 flex flex-wrap justify-end gap-2 border-t pt-4" style="border-color: var(--border)">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
