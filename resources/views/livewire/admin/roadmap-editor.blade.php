<div class="flex flex-col gap-5 sm:gap-6">

    {{-- ══ Tahun ══ --}}
    <div class="dbena-card flex flex-wrap items-center gap-3 px-4 py-4 sm:px-5">
        <span class="text-[12.5px] font-bold text-t80">{{ __('roadmap.admin.title') }}</span>

        <div class="flex items-center gap-1.5">
            <button type="button" wire:click="changeYear({{ $year - 1 }})"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-t75"
                    style="border: 1px solid var(--border2)" aria-label="{{ $year - 1 }}">
                <i class="ph-duotone ph-caret-left text-sm" aria-hidden="true"></i>
            </button>
            <span class="min-w-[52px] text-center text-[14px] font-extrabold text-t94">{{ $year }}</span>
            <button type="button" wire:click="changeYear({{ $year + 1 }})"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-t75"
                    style="border: 1px solid var(--border2)" aria-label="{{ $year + 1 }}">
                <i class="ph-duotone ph-caret-right text-sm" aria-hidden="true"></i>
            </button>
        </div>

        <button type="button" wire:click="copyFromPreviousYear"
                class="flex items-center gap-1.5 rounded-[9px] px-3 py-2 text-[12px] font-semibold text-t80"
                style="border: 1px solid var(--border2)">
            <i class="ph-duotone ph-copy text-sm" aria-hidden="true"></i>
            {{ __('roadmap.admin.copy_year', ['year' => $year - 1]) }}
        </button>

        <span class="ml-auto text-[11.5px] text-t55">{{ __('roadmap.admin.cycle_hint') }}</span>
    </div>

    {{-- ══ Grid boleh klik ══ --}}
    <div class="dbena-card overflow-hidden p-4 sm:p-5">
        <p class="mb-3 text-[12px] text-t60">{{ __('roadmap.admin.note') }}</p>

        <div class="overflow-x-auto">
            <div class="min-w-[1080px]">
                <div class="grid gap-1.5" style="grid-template-columns: 210px repeat(12, 1fr)">
                    <div class="flex items-center px-2 text-[11px] font-extrabold uppercase tracking-wide text-t60">
                        {{ __('roadmap.service_col') }}
                    </div>
                    @foreach (range(1, 12) as $m)
                        <div class="rounded-md py-1.5 text-center text-[11px] font-bold text-t75"
                             style="background: var(--hover-bg2)">{{ $months[$m - 1] }}</div>
                    @endforeach
                </div>

                @foreach ($services as $service)
                    <div class="mt-1.5 grid gap-1.5" style="grid-template-columns: 210px repeat(12, 1fr)">
                        <div class="flex min-w-0 items-center gap-2 px-2">
                            <i class="ph-duotone {{ $service->icon_class }} shrink-0 text-base"
                               style="color: {{ $service->chart_color }}" aria-hidden="true"></i>
                            <span class="min-w-0 flex-1 truncate text-[12px] font-bold text-t90">{{ $service->name }}</span>

                            {{-- Isi baris: dua belas klik menjadi satu. --}}
                            <select wire:change="fillRow({{ $service->id }}, $event.target.value); $event.target.value = ''"
                                    class="w-[34px] shrink-0 rounded-md bg-transparent px-1 py-1 text-[10px] text-t60"
                                    style="border: 1px solid var(--border2)"
                                    aria-label="{{ __('roadmap.admin.fill_row') }} — {{ $service->name }}">
                                <option value="">⋯</option>
                                @foreach ($statuses as $st)
                                    <option value="{{ $st->value }}">{{ $st->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        @foreach (range(1, 12) as $m)
                            @php
                                $cell = $cells[$service->id.'-'.$m] ?? null;
                                $st = $cell?->status ?? \App\Enums\RoadmapStatus::None;
                            @endphp
                            <button type="button" wire:click="cycle({{ $service->id }}, {{ $m }})"
                                    class="flex h-[46px] flex-col items-center justify-center gap-0.5 rounded-lg px-0.5 transition-opacity hover:opacity-80"
                                    style="background: {{ $st->color() }}"
                                    title="{{ $service->name }} — {{ $months[$m - 1] }}: {{ $st->label() }}">
                                <i class="ph-duotone {{ $st->icon() }} text-[14px]"
                                   style="color: {{ $st->textColor() }}" aria-hidden="true"></i>
                                <span class="text-center text-[7.5px] font-bold leading-none"
                                      style="color: {{ $st->textColor() }}">{{ $st->label() }}</span>
                            </button>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ══ Teks & Kalendar ══ --}}
    <div class="grid gap-5 lg:grid-cols-2">
        <div class="dbena-card p-5">
            <h2 class="mb-3 text-base font-bold">{{ __('roadmap.admin.text') }}</h2>

            <div class="flex flex-col gap-3">
                <div>
                    <label for="rm-title" class="mb-1.5 block text-[11.5px] text-t55">{{ __('roadmap.admin.heading') }}</label>
                    <input id="rm-title" type="text" wire:model="title" class="dbena-input"
                           placeholder="{{ __('roadmap.title') }}">
                </div>

                <div>
                    <label for="rm-sub" class="mb-1.5 block text-[11.5px] text-t55">{{ __('roadmap.admin.sub') }}</label>
                    <input id="rm-sub" type="text" wire:model="subtitle" class="dbena-input"
                           placeholder="{{ __('roadmap.subtitle') }}">
                </div>

                <div>
                    <label for="rm-sum" class="mb-1.5 block text-[11.5px] text-t55">{{ __('roadmap.admin.summary_line') }}</label>
                    <textarea id="rm-sum" wire:model="summaryText" rows="5" class="dbena-input"
                              style="resize: vertical"></textarea>
                </div>
            </div>
        </div>

        <div class="dbena-card p-5">
            <h2 class="mb-1 text-base font-bold">{{ __('roadmap.calendar.title') }}</h2>

            {{-- Emel robot dinamakan di sini. Tanpa itu admin tidak tahu
                 alamat mana untuk ditambah pada senarai perkongsian, dan
                 ujian gagal dengan 403 yang kelihatan seperti bug kami. --}}
            @if ($serviceEmail)
                <p class="mb-3 break-all text-[11.5px] leading-relaxed text-t60">
                    {{ __('roadmap.calendar.share_hint', ['email' => $serviceEmail]) }}
                </p>
            @endif

            <div>
                <label for="rm-cal" class="mb-1.5 block text-[11.5px] text-t55">{{ __('roadmap.calendar.id') }}</label>
                <input id="rm-cal" type="text" wire:model="calendarId" class="dbena-input"
                       placeholder="contoh@group.calendar.google.com">
                <p class="mt-1.5 text-[11px] text-t50">{{ __('roadmap.calendar.id_hint') }}</p>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-2.5">
                <button type="button" wire:click="testCalendar"
                        class="flex items-center gap-1.5 rounded-[9px] px-3.5 py-2 text-[12px] font-semibold text-t80"
                        style="border: 1px solid var(--border2)">
                    <i class="ph-duotone ph-plugs-connected text-sm" aria-hidden="true"></i>
                    {{ __('roadmap.calendar.test') }}
                </button>

                @if ($calendarResult)
                    <span class="text-[11.5px] font-semibold"
                          style="color: {{ $calendarOk ? 'oklch(0.7 0.15 150)' : 'oklch(0.72 0.17 25)' }}">
                        {{ $calendarResult }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div>
        <button type="button" wire:click="save"
                class="dbena-btn-gold flex items-center gap-2 px-4 py-2.5 text-[12.5px]">
            <i class="ph-duotone ph-floppy-disk text-base" aria-hidden="true"></i>
            {{ __('roadmap.admin.save') }}
        </button>
    </div>

    {{-- ══ Pratonton ══
         Editor menunjukkan status mentah; ini menunjukkan apa yang pengguna
         sebenarnya akan lihat, termasuk bar gabungan dan sasaran terkira. --}}
    <x-annual-roadmap :roadmap="$preview" :year-nav="false" />
</div>
