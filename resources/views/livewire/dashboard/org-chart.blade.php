<div class="flex flex-col gap-5">
    <x-org-chart :nodes="$nodes" :links="$links">
        <x-slot:actions>
            {{-- Kedua-dua butang Admin sahaja. Pengguna membaca carta di
                 sini; menyunting dan mengeksport senarai kakitangan penuh
                 ialah kerja Panel Admin. --}}
            @can('manage-org-chart')
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.carta') }}" wire:navigate
                       class="flex items-center gap-2 rounded-[9px] px-3.5 py-2 text-[12px] font-semibold text-white/90 transition-colors hover:bg-white/10"
                       style="border: 1px solid oklch(0.60 0.12 340)">
                        <i class="ph-duotone ph-pencil-simple text-sm" aria-hidden="true"></i>
                        {{ __('org.edit') }}
                    </a>

                    <a href="{{ route('carta.pdf') }}"
                       class="flex items-center gap-2 rounded-[9px] px-3.5 py-2 text-[12px] font-semibold text-white/90 transition-colors hover:bg-white/10"
                       style="border: 1px solid oklch(0.60 0.12 340)">
                        <i class="ph-duotone ph-file-pdf text-sm" aria-hidden="true"></i>
                        {{ __('org.export') }}
                    </a>
                </div>
            @endcan
        </x-slot:actions>
    </x-org-chart>
</div>
