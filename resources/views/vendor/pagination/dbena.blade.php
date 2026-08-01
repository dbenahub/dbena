{{--
    Penomboran bertema DBENA.

    Paparan lalai Laravel menggunakan kelas Tailwind yang ditetapkan untuk
    tema TERANG — bg-white, text-gray-500, border-gray-300. Pada dashboard
    gelap ini nombor halaman menjadi putih di atas putih: pautan itu ada,
    boleh diklik, dan langsung tidak kelihatan.

    Semua warna di sini datang daripada token tema, jadi ia betul dalam
    kedua-dua mod terang dan gelap tanpa peraturan berasingan.

    Pautan ialah <a href> DENGAN wire:click.prevent. Livewire memintas
    klik itu; di luar Livewire, href berfungsi seperti biasa. Paparan ini
    ditetapkan sebagai lalai global, jadi ia mesti betul untuk senarai
    bernombor yang ditambah kemudian — termasuk yang bukan Livewire.
--}}

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('pagination.nav') }}"
         class="flex items-center gap-1">

        {{-- Sebelumnya --}}
        @if ($paginator->onFirstPage())
            <span aria-disabled="true"
                  class="flex h-8 w-8 items-center justify-center rounded-lg text-[12px] opacity-40"
                  style="border: 1px solid var(--border3); color: var(--t50)">
                <i class="ph-duotone ph-caret-left" aria-hidden="true"></i>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}"
               wire:click.prevent="previousPage" wire:loading.attr="disabled"
               rel="prev" aria-label="{{ __('pagination.previous') }}"
               class="flex h-8 w-8 items-center justify-center rounded-lg text-[12px] transition-colors hover:bg-hover"
               style="border: 1px solid var(--border2); color: var(--t75)">
                <i class="ph-duotone ph-caret-left" aria-hidden="true"></i>
            </a>
        @endif

        {{-- Nombor halaman --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span aria-disabled="true"
                      class="flex h-8 w-8 items-center justify-center text-[12px]"
                      style="color: var(--t50)">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page"
                              class="flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-[12px] font-bold"
                              style="background: oklch(0.78 0.12 85); color: oklch(0.15 0.02 260)">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}" wire:click.prevent="gotoPage({{ $page }})"
                           aria-label="{{ __('pagination.go_to', ['page' => $page]) }}"
                           class="flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-[12px] font-semibold transition-colors hover:bg-hover"
                           style="border: 1px solid var(--border2); color: var(--t75)">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Seterusnya --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}"
               wire:click.prevent="nextPage" wire:loading.attr="disabled"
               rel="next" aria-label="{{ __('pagination.next') }}"
               class="flex h-8 w-8 items-center justify-center rounded-lg text-[12px] transition-colors hover:bg-hover"
               style="border: 1px solid var(--border2); color: var(--t75)">
                <i class="ph-duotone ph-caret-right" aria-hidden="true"></i>
            </a>
        @else
            <span aria-disabled="true"
                  class="flex h-8 w-8 items-center justify-center rounded-lg text-[12px] opacity-40"
                  style="border: 1px solid var(--border3); color: var(--t50)">
                <i class="ph-duotone ph-caret-right" aria-hidden="true"></i>
            </span>
        @endif
    </nav>
@endif
