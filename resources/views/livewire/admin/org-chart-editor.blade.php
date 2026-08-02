<div class="flex flex-col gap-5 xl:flex-row xl:items-start">

    <div class="min-w-0 flex-1">
        <x-org-chart :nodes="$nodes" :links="$links" :editable="true"
                     :selected-id="$selectedId" :connect-from="$connectFrom">
            <x-slot:actions>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="addNode"
                            class="flex items-center gap-2 rounded-[9px] px-3.5 py-2 text-[12px] font-semibold text-white/90 transition-colors hover:bg-white/10"
                            style="border: 1px solid oklch(0.60 0.12 340)">
                        <i class="ph-duotone ph-plus-circle text-sm" aria-hidden="true"></i>
                        {{ __('org.editor.add') }}
                    </button>

                    <button type="button" wire:click="tidy"
                            class="flex items-center gap-2 rounded-[9px] px-3.5 py-2 text-[12px] font-semibold text-white/90 transition-colors hover:bg-white/10"
                            style="border: 1px solid oklch(0.60 0.12 340)">
                        <i class="ph-duotone ph-grid-nine text-sm" aria-hidden="true"></i>
                        {{ __('org.editor.tidy') }}
                    </button>

                    <a href="{{ route('carta') }}" wire:navigate
                       class="flex items-center gap-2 rounded-[9px] px-3.5 py-2 text-[12px] font-semibold text-white/90 transition-colors hover:bg-white/10"
                       style="border: 1px solid oklch(0.60 0.12 340)">
                        <i class="ph-duotone ph-check-circle text-sm" aria-hidden="true"></i>
                        {{ __('org.done') }}
                    </a>
                </div>
            </x-slot:actions>
        </x-org-chart>

        {{-- Jalur mod sambung. Mod yang tidak menyatakan dirinya ialah mod
             yang admin lupa mereka berada di dalamnya, dan klik seterusnya
             menghasilkan garisan yang tidak diminta. --}}
        @if ($connectFrom)
            <div class="dbena-card mt-3 flex flex-wrap items-center gap-3 px-4 py-3"
                 style="border-color: oklch(0.82 0.15 85/0.5); background: oklch(0.82 0.15 85/0.08)">
                <i class="ph-duotone ph-flow-arrow text-lg" style="color: oklch(0.85 0.14 85)" aria-hidden="true"></i>
                <span class="text-[12.5px] font-semibold text-t85">
                    {{ __('org.editor.connect_from', [
                        'name' => $nodes->firstWhere('id', $connectFrom)?->title
                            ?? $nodes->firstWhere('id', $connectFrom)?->name
                            ?? '—',
                    ]) }}
                </span>
                <button type="button" wire:click="cancelLink"
                        class="ml-auto rounded-[9px] px-3 py-1.5 text-[11.5px] font-semibold text-t80"
                        style="border: 1px solid var(--border2)">
                    {{ __('org.editor.cancel_connect') }}
                </button>
            </div>
        @else
            <p class="mt-2.5 text-[11.5px] text-t60">
                <i class="ph-duotone ph-hand-grabbing text-[13px]" aria-hidden="true"></i>
                {{ __('org.editor.drag_hint') }}
            </p>
        @endif
    </div>

    {{-- ══ Panel butiran ══ --}}
    <div class="dbena-card w-full shrink-0 p-4 sm:p-5 xl:w-[330px]">
        <h2 class="mb-3 text-base font-bold">{{ __('org.editor.panel') }}</h2>

        @if (! $selected)
            <p class="text-[12.5px] leading-relaxed text-t65">{{ __('org.editor.panel_none') }}</p>
        @else
            <div class="flex flex-col gap-3">
                <div>
                    <label for="org-title" class="mb-1.5 block text-[11.5px] text-t60">{{ __('org.editor.field_title') }}</label>
                    <input id="org-title" type="text" wire:model="title" class="dbena-input">
                </div>

                <div>
                    <label for="org-name" class="mb-1.5 block text-[11.5px] text-t60">{{ __('org.editor.field_name') }}</label>
                    <input id="org-name" type="text" wire:model="name" class="dbena-input">
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label for="org-style" class="mb-1.5 block text-[11.5px] text-t60">{{ __('org.editor.field_style') }}</label>
                        <select id="org-style" wire:model="style" class="dbena-input">
                            @foreach ($styles as $s)
                                <option value="{{ $s->value }}">{{ $s->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="org-width" class="mb-1.5 block text-[11.5px] text-t60">{{ __('org.editor.field_width') }}</label>
                        <input id="org-width" type="number" min="120" max="420" step="10"
                               wire:model="width" class="dbena-input">
                    </div>
                </div>

                <div>
                    <label for="org-icon" class="mb-1.5 block text-[11.5px] text-t60">{{ __('org.editor.field_icon') }}</label>
                    <select id="org-icon" wire:model="icon" class="dbena-input">
                        @foreach ([
                            'ph-user' => 'Orang', 'ph-user-circle' => 'Orang (bulat)',
                            'ph-users-three' => 'Pasukan', 'ph-briefcase' => 'Beg kerja',
                            'ph-chart-bar' => 'Carta', 'ph-megaphone' => 'Pemasaran',
                            'ph-identification-card' => 'Kad ID', 'ph-calculator' => 'Akaun',
                            'ph-file-text' => 'Dokumen', 'ph-folder' => 'Folder',
                            'ph-buildings' => 'Bangunan', 'ph-hard-hat' => 'Operasi',
                            'ph-gear' => 'Gear', 'ph-pencil-simple' => 'Reka bentuk',
                            'ph-wrench' => 'Kerja', 'ph-house-line' => 'Rumah',
                        ] as $kelas => $label)
                            <option value="{{ $kelas }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="saveNode"
                            class="dbena-btn-gold flex items-center gap-1.5 px-3.5 py-2 text-[12px]">
                        <i class="ph-duotone ph-floppy-disk text-sm" aria-hidden="true"></i>
                        {{ __('app.save') }}
                    </button>

                    <button type="button" wire:click="deleteNode"
                            wire:confirm="{{ __('org.editor.delete') }}?"
                            class="flex items-center gap-1.5 rounded-[9px] px-3.5 py-2 text-[12px] font-semibold"
                            style="border: 1px solid oklch(0.6 0.2 25/0.45); color: oklch(0.72 0.16 25)">
                        <i class="ph-duotone ph-trash text-sm" aria-hidden="true"></i>
                        {{ __('org.editor.delete') }}
                    </button>
                </div>

                {{-- ══ Sambungan ══ --}}
                <div class="mt-1 border-t pt-3" style="border-color: var(--border3)">
                    <div class="mb-2 flex flex-wrap items-center gap-2">
                        <select wire:model="linkStyle" class="dbena-input w-auto flex-1 text-[11.5px]"
                                aria-label="{{ __('org.link.solid') }}">
                            @foreach ($linkStyles as $ls)
                                <option value="{{ $ls->value }}">{{ $ls->label() }}</option>
                            @endforeach
                        </select>

                        <button type="button" wire:click="startLink"
                                class="flex shrink-0 items-center gap-1.5 rounded-[9px] px-3 py-2 text-[11.5px] font-semibold text-t85"
                                style="border: 1px solid var(--border2)">
                            <i class="ph-duotone ph-flow-arrow text-sm" aria-hidden="true"></i>
                            {{ __('org.editor.connect') }}
                        </button>
                    </div>

                    <p class="mb-2.5 text-[11px] text-t60">{{ __('org.editor.connect_hint') }}</p>

                    <div class="text-[11.5px] font-bold text-t70">{{ __('org.editor.links_here') }}</div>

                    <div class="mt-1.5 flex flex-col gap-1">
                        @forelse ($selectedLinks as $link)
                            <div class="flex items-center gap-2 rounded-lg px-2.5 py-1.5"
                                 style="background: var(--hover-bg3)">
                                <i class="ph-duotone {{ $link->from_node_id === $selected->id ? 'ph-arrow-down-right' : 'ph-arrow-up-left' }} shrink-0 text-[13px] text-t55"
                                   aria-hidden="true"></i>
                                <span class="min-w-0 flex-1 truncate text-[11.5px] text-t85">
                                    {{ $link->from_node_id === $selected->id
                                        ? ($link->to->title ?? $link->to->name ?? '—')
                                        : ($link->from->title ?? $link->from->name ?? '—') }}
                                </span>
                                <span class="shrink-0 text-[10px] text-t55">{{ $link->style->label() }}</span>
                                <button type="button" wire:click="removeLink({{ $link->id }})"
                                        class="shrink-0 text-t55 transition-colors hover:text-t90"
                                        aria-label="{{ __('org.editor.unlink') }}">
                                    <i class="ph-duotone ph-x text-xs" aria-hidden="true"></i>
                                </button>
                            </div>
                        @empty
                            <span class="text-[11.5px] text-t60">{{ __('org.editor.no_links') }}</span>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
