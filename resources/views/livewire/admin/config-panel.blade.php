<div x-data="{ showUser: @entangle('showUserModal') }" class="flex flex-col gap-5 sm:gap-6 xl:max-w-6xl">

    {{-- ══ Bar Simpan Semua ══ --}}
    <div class="flex flex-wrap items-center justify-between gap-3.5 rounded-2xl px-5 py-5"
         style="background: var(--card-bg); border: 1px solid oklch(0.78 0.12 85/0.3)">
        <p class="max-w-2xl text-[13px] leading-relaxed text-t75">{{ __('admin.save_note') }}</p>
        <button type="button" wire:click="saveAll"
                class="dbena-btn-gold flex shrink-0 items-center gap-2 px-5 py-2.5 text-[13.5px]"
                wire:loading.attr="disabled">
            <i class="ph-duotone ph-floppy-disk" aria-hidden="true"></i>
            <span wire:loading.remove wire:target="saveAll">{{ __('admin.save_all') }}</span>
            <span wire:loading wire:target="saveAll">{{ __('app.loading') }}</span>
        </button>
    </div>

    {{-- ══ Servis & Sasaran ══ --}}
    <div class="dbena-card p-5 sm:p-6">
        <h2 class="mb-1 text-base font-bold">{{ __('admin.services_title') }}</h2>
        <p class="mb-5 text-[12px] text-t55">{{ __('admin.services_hint') }}</p>

        <div class="hidden gap-3.5 border-b pb-2.5 text-[11px] font-bold uppercase text-t60 md:grid"
             style="grid-template-columns: 1.4fr 1.4fr 1fr; border-color: var(--border2)">
            <div>{{ __('admin.col_service_ms') }}</div>
            <div>{{ __('admin.col_service_en') }}</div>
            <div>{{ __('admin.col_monthly_target') }}</div>
        </div>

        @foreach ($serviceModels as $service)
            <div class="grid gap-3 border-b py-3 md:grid-cols-[1.4fr_1.4fr_1fr] md:items-center md:gap-3.5"
                 style="border-color: var(--border3)" wire:key="svc-{{ $service->id }}">
                <div class="flex items-center gap-2">
                    <i class="ph-duotone {{ $service->icon_class }}" style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
                    <input type="text" wire:model="services.{{ $service->id }}.name_ms"
                           aria-label="{{ __('admin.col_service_ms') }}"
                           class="w-full rounded-lg px-2.5 py-2 text-[12.5px] text-t90 focus:outline-none"
                           style="background: var(--input-bg); border: 1px solid var(--border2)">
                </div>
                <input type="text" wire:model="services.{{ $service->id }}.name_en"
                       aria-label="{{ __('admin.col_service_en') }}"
                       class="w-full rounded-lg px-2.5 py-2 text-[12.5px] text-t90 focus:outline-none"
                       style="background: var(--input-bg); border: 1px solid var(--border2)">
                <input type="text" inputmode="decimal" wire:model="services.{{ $service->id }}.monthly_target"
                       aria-label="{{ __('admin.col_monthly_target') }}"
                       class="w-full rounded-lg px-2.5 py-2 text-[12.5px] font-semibold focus:outline-none"
                       style="background: var(--input-bg); border: 1px solid var(--border2); color: oklch(0.72 0.15 145)">
            </div>
        @endforeach
    </div>

    {{-- ══ Index Tier Threshold ══ --}}
    <div class="dbena-card p-5 sm:p-6">
        <h2 class="mb-1 text-base font-bold">{{ __('admin.tiers_title') }}</h2>
        <p class="mb-5 text-[12px] text-t55">{{ __('admin.tiers_hint') }}</p>

        <div class="hidden gap-3.5 border-b pb-2.5 text-[11px] font-bold uppercase text-t60 md:grid"
             style="grid-template-columns: 1.3fr 1fr 1fr; border-color: var(--border2)">
            <div>{{ __('admin.col_tier') }}</div>
            <div>{{ __('admin.col_revenue') }}</div>
            <div>{{ __('admin.col_profit') }}</div>
        </div>

        @foreach ($tierModels as $tier)
            <div class="grid gap-3 border-b py-3 md:grid-cols-[1.3fr_1fr_1fr] md:items-center md:gap-3.5"
                 style="border-color: var(--border3)" wire:key="tier-{{ $tier->id }}">
                <div class="flex items-center gap-2 text-[13px] font-bold">
                    <span class="h-[9px] w-[9px] rounded-sm" style="background: {{ $tier->color_token }}"></span>
                    {{ $tier->name }}
                </div>
                <input type="text" inputmode="decimal" wire:model="tiers.{{ $tier->id }}.monthly_revenue_threshold"
                       aria-label="{{ $tier->name }} — {{ __('admin.col_revenue') }}"
                       class="w-full rounded-lg px-2.5 py-2 text-[12.5px] text-t90 focus:outline-none"
                       style="background: var(--input-bg); border: 1px solid var(--border2)">
                <input type="text" inputmode="decimal" wire:model="tiers.{{ $tier->id }}.monthly_profit_threshold"
                       aria-label="{{ $tier->name }} — {{ __('admin.col_profit') }}"
                       class="w-full rounded-lg px-2.5 py-2 text-[12.5px] text-t90 focus:outline-none"
                       style="background: var(--input-bg); border: 1px solid var(--border2)">
            </div>
        @endforeach
    </div>

    {{-- ══ Pemilik Data (PIC) ══ --}}
    <div class="dbena-card p-5 sm:p-6">
        <h2 class="mb-1 text-base font-bold">{{ __('admin.owners_title') }}</h2>
        <p class="mb-4 text-[12px] text-t55">{{ __('admin.owners_hint') }}</p>

        <div class="mb-4 flex flex-wrap gap-2.5">
            @foreach ($activeOwners as $owner)
                <span wire:key="owner-{{ $owner->id }}"
                      class="flex items-center gap-2 rounded-[20px] py-[7px] pl-3.5 pr-2"
                      style="background: color-mix(in oklch, {{ $owner->color_token }} 16%, transparent);
                             border: 1px solid color-mix(in oklch, {{ $owner->color_token }} 40%, transparent)">
                    <span class="text-[12.5px] font-bold" style="color: {{ $owner->color_token }}">{{ $owner->name }}</span>

                    @if ($owner->is_system)
                        <i class="ph-duotone ph-lock-simple text-[13px] opacity-60"
                           style="color: {{ $owner->color_token }}"
                           title="{{ __('admin.owner_system_locked') }}" aria-hidden="true"></i>
                    @elseif ($owner->is_core)
                        <i class="ph-duotone ph-lock-simple text-[13px] opacity-60"
                           style="color: {{ $owner->color_token }}"
                           title="{{ __('admin.owner_core_locked') }}" aria-hidden="true"></i>
                    @else
                        <button type="button" wire:click="removeOwner({{ $owner->id }})"
                                aria-label="{{ __('app.remove') }} {{ $owner->name }}">
                            <i class="ph-duotone ph-x-circle text-[15px]" style="color: {{ $owner->color_token }}" aria-hidden="true"></i>
                        </button>
                    @endif
                </span>
            @endforeach
        </div>

        <div class="flex flex-col gap-2.5 sm:flex-row">
            <input type="text" wire:model="newOwnerName" wire:keydown.enter="addOwner"
                   placeholder="{{ __('admin.owner_placeholder') }}" aria-label="{{ __('admin.owner_placeholder') }}"
                   class="flex-1 rounded-[9px] px-3 py-2.5 text-[13px] uppercase text-t90 focus:outline-none"
                   style="background: var(--input-bg); border: 1px solid var(--border2)">
            <button type="button" wire:click="addOwner"
                    class="dbena-btn-gold flex items-center justify-center gap-1.5 px-4 py-2.5 text-[13px]">
                <i class="ph-duotone ph-plus" aria-hidden="true"></i> {{ __('app.add') }}
            </button>
        </div>
    </div>

    {{-- ══ PIC Menunggu Kelulusan (keputusan D2) ══ --}}
    <div class="dbena-card p-5 sm:p-6">
        <h2 class="mb-1 text-base font-bold">{{ __('admin.pending_owners_title') }}</h2>
        <p class="mb-4 text-[12px] text-t55">{{ __('admin.pending_owners_hint') }}</p>

        @forelse ($pendingOwners as $owner)
            <div wire:key="pending-{{ $owner->id }}"
                 class="mb-2.5 flex flex-wrap items-center justify-between gap-3 rounded-xl px-4 py-3"
                 style="background: var(--hover-bg3); border: 1px solid var(--border3)">
                <div>
                    <div class="text-[13px] font-bold" style="color: {{ $owner->color_token }}">{{ $owner->name }}</div>
                    <div class="mt-0.5 text-[11.5px] text-t55">
                        {{ __('admin.proposed_by', ['name' => $owner->creator?->name ?? '—']) }}
                        · {{ $owner->created_at->translatedFormat('d M Y') }}
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" wire:click="approveOwner({{ $owner->id }})"
                            class="rounded-[9px] px-3.5 py-2 text-[12.5px] font-semibold"
                            style="background: oklch(0.55 0.15 145/0.16); color: oklch(0.72 0.15 145); border: 1px solid oklch(0.55 0.15 145/0.4)">
                        {{ __('admin.approve') }}
                    </button>
                    <button type="button" wire:click="rejectOwner({{ $owner->id }})"
                            class="rounded-[9px] px-3.5 py-2 text-[12.5px] font-semibold"
                            style="border: 1px solid oklch(0.6 0.2 25/0.4); color: oklch(0.7 0.15 25)">
                        {{ __('admin.reject') }}
                    </button>
                </div>
            </div>
        @empty
            <p class="py-4 text-center text-[12.5px] text-t55">{{ __('admin.no_pending_owners') }}</p>
        @endforelse
    </div>

    {{-- ══ Faktor Pertumbuhan Tahunan ══ --}}
    <div class="dbena-card p-5 sm:p-6">
        <h2 class="mb-1 text-base font-bold">{{ __('admin.growth_title') }}</h2>
        <p class="mb-5 text-[12px] text-t55">{{ __('admin.growth_hint') }}</p>

        <div class="grid grid-cols-3 gap-3 sm:grid-cols-5">
            @foreach ($growth as $year => $factor)
                <div wire:key="growth-{{ $year }}">
                    <label class="mb-1.5 block text-center text-[11.5px] text-t60">{{ $year }}</label>
                    <input type="text" inputmode="decimal" wire:model="growth.{{ $year }}"
                           aria-label="{{ __('admin.growth_title') }} {{ $year }}"
                           class="w-full rounded-lg px-1.5 py-2 text-center text-[12.5px] text-t90 focus:outline-none"
                           style="background: var(--input-bg); border: 1px solid var(--border2)">
                </div>
            @endforeach
        </div>

        <div class="mt-4 flex flex-col gap-2.5 sm:flex-row sm:items-center">
            <input type="text" inputmode="numeric" wire:model="newYear" wire:keydown.enter="addYear"
                   placeholder="2033" aria-label="{{ __('admin.add_year') }}"
                   class="w-full rounded-[9px] px-3 py-2.5 text-[13px] text-t90 focus:outline-none sm:w-32"
                   style="background: var(--input-bg); border: 1px solid var(--border2)">
            <button type="button" wire:click="addYear"
                    class="rounded-[9px] px-4 py-2.5 text-[13px] font-semibold text-t80"
                    style="border: 1px solid var(--border2)">{{ __('admin.add_year') }}</button>
        </div>
    </div>

    {{-- ══ Google Sheet Lalai ══ --}}
    <div class="dbena-card p-5 sm:p-6">
        <div class="mb-1 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base font-bold">{{ __('admin.sheet_title') }}</h2>
            <a href="{{ route('admin.sheets') }}" wire:navigate
               class="flex items-center gap-1.5 rounded-[9px] px-3.5 py-2 text-[12.5px] font-semibold"
               style="border: 1px solid oklch(0.78 0.12 85/0.5); color: oklch(0.78 0.12 85)">
                <i class="ph-duotone ph-plugs-connected" aria-hidden="true"></i>
                {{ __('admin.open_sheet_manager') }}
            </a>
        </div>
        <p class="mb-4 text-[12px] text-t55">{{ __('admin.sheet_hint') }}</p>
        <input type="url" wire:model="sheetUrl" placeholder="https://docs.google.com/spreadsheets/d/…"
               aria-label="{{ __('admin.sheet_title') }}"
               class="w-full rounded-[9px] px-3 py-2.5 text-[13px] text-t90 focus:outline-none"
               style="background: var(--input-bg); border: 1px solid var(--border2)">
    </div>

    {{-- ══ Urus Pengguna ══ --}}
    <div class="dbena-card p-5 sm:p-6">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-bold">{{ __('admin.users_title') }}</h2>
                <p class="mt-0.5 text-[12px] text-t55">{{ __('admin.users_hint') }}</p>
            </div>
            <button type="button" x-on:click="showUser = true"
                    class="flex items-center gap-1.5 rounded-[9px] px-3.5 py-2.5 text-[12.5px] font-semibold text-t80"
                    style="border: 1px solid var(--border2)">
                <i class="ph-duotone ph-user-plus text-base" aria-hidden="true"></i> {{ __('admin.add_user') }}
            </button>
        </div>

        <div class="overflow-x-auto">
            <div class="min-w-[700px]">
                <div class="grid gap-3.5 border-b pb-2.5 text-[11px] font-bold uppercase text-t60"
                     style="grid-template-columns: 1.6fr 1.2fr 1.8fr 1fr 1.2fr 0.8fr; border-color: var(--border2)">
                    <div>{{ __('admin.col_name') }}</div>
                    <div>{{ __('admin.col_username') }}</div>
                    <div>{{ __('admin.col_email') }}</div>
                    <div>{{ __('admin.col_role') }}</div>
                    <div>{{ __('admin.col_last_login') }}</div>
                    <div>{{ __('admin.col_active') }}</div>
                </div>

                @foreach ($users as $u)
                    <div wire:key="user-{{ $u->id }}"
                         class="grid items-center gap-3.5 border-b py-3 text-[12.5px]"
                         style="grid-template-columns: 1.6fr 1.2fr 1.8fr 1fr 1.2fr 0.8fr; border-color: var(--border3)">
                        <div class="font-semibold">{{ $u->name }}</div>
                        <div class="text-t70">{{ $u->username }}</div>
                        <div class="truncate text-t70">{{ $u->email }}</div>
                        <div>
                            <span class="rounded-md px-2 py-1 text-[11px] font-bold"
                                  @style([
                                      'background: oklch(0.78 0.12 85/0.16); color: oklch(0.78 0.12 85)' => $u->isAdmin(),
                                      'background: var(--hover-bg3); color: var(--t65)' => ! $u->isAdmin(),
                                  ])>{{ $u->role->label() }}</span>
                        </div>
                        <div class="text-t65">
                            {{ $u->last_login_at?->translatedFormat('d M, H:i') ?? __('admin.never') }}
                        </div>
                        <div>
                            <button type="button" wire:click="toggleUserActive({{ $u->id }})" role="switch"
                                    aria-checked="{{ $u->is_active ? 'true' : 'false' }}"
                                    aria-label="{{ __('admin.col_active') }} — {{ $u->name }}"
                                    class="relative rounded-xl transition-colors"
                                    style="width: 38px; height: 22px; background: {{ $u->is_active ? 'oklch(0.72 0.15 145)' : 'var(--switch-off)' }}">
                                <span class="absolute rounded-full bg-white transition-[left] duration-150"
                                      style="top: 2px; left: {{ $u->is_active ? '18px' : '2px' }}; width: 18px; height: 18px"></span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ══ Log Audit (betulkan isu #25) ══ --}}
    <div class="dbena-card p-5 sm:p-6">
        <h2 class="mb-1 text-base font-bold">{{ __('admin.audit_title') }}</h2>
        <p class="mb-4 text-[12px] text-t55">{{ __('admin.audit_hint') }}</p>

        @forelse ($auditLogs as $log)
            <div wire:key="audit-{{ $log->id }}"
                 class="grid gap-2 border-b py-3 text-[12.5px] md:grid-cols-[1.2fr_1.2fr_1.4fr_1.2fr_2fr] md:items-center md:gap-3.5"
                 style="border-color: var(--border3)">
                <div class="text-t65">{{ $log->created_at->translatedFormat('d M Y, H:i') }}</div>
                <div class="font-semibold">{{ $log->user?->name ?? '—' }}</div>
                <div class="text-t75">{{ __('admin.action.'.$log->action) }}</div>
                <div class="text-t70">{{ $log->subject_label ?? '—' }}</div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach ((array) $log->new_values as $field => $newValue)
                        <span class="rounded px-1.5 py-0.5 text-[11px]" style="background: var(--hover-bg3)">
                            <span class="text-t55">{{ $field }}:</span>
                            <span class="line-through opacity-60">{{ data_get($log->old_values, $field) ?? '—' }}</span>
                            <span class="mx-0.5">→</span>
                            <span class="font-semibold" style="color: oklch(0.72 0.15 145)">{{ is_scalar($newValue) ? $newValue : json_encode($newValue) }}</span>
                        </span>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="py-4 text-center text-[12.5px] text-t55">{{ __('admin.no_audit') }}</p>
        @endforelse
    </div>

    {{-- ══ MODAL: Cipta Pengguna ══ --}}
    <x-modal show="showUser" :title="__('admin.new_user_title')" icon="ph-user-plus" max-width="520px">
        @if ($generatedPassword)
            <div class="mb-4 rounded-lg px-3.5 py-3 text-[12.5px] leading-relaxed"
                 style="background: oklch(0.78 0.12 85/0.1); border: 1px solid oklch(0.78 0.12 85/0.4); color: oklch(0.78 0.12 85)">
                {{ __('admin.temp_password_note', ['password' => $generatedPassword]) }}
            </div>
        @endif

        <form wire:submit="createUser" class="flex flex-col gap-4">
            @foreach ([
                ['model' => 'userName', 'label' => __('admin.col_name'), 'type' => 'text'],
                ['model' => 'userUsername', 'label' => __('admin.col_username'), 'type' => 'text'],
                ['model' => 'userEmail', 'label' => __('admin.col_email'), 'type' => 'email'],
            ] as $field)
                <div>
                    <label for="u-{{ $field['model'] }}" class="mb-1.5 block text-[11.5px] text-t55">{{ $field['label'] }}</label>
                    <input id="u-{{ $field['model'] }}" type="{{ $field['type'] }}"
                           wire:model="{{ $field['model'] }}" class="dbena-input">
                    @error($field['model'])
                        <p class="mt-1.5 text-[12px]" style="color: oklch(0.65 0.2 25)">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <div>
                <label for="u-role" class="mb-1.5 block text-[11.5px] text-t55">{{ __('admin.col_role') }}</label>
                <select id="u-role" wire:model="userRole" class="dbena-input">
                    <option value="user">{{ __('app.role.user') }}</option>
                    <option value="admin">{{ __('app.role.admin') }}</option>
                </select>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" x-on:click="showUser = false"
                    class="rounded-[9px] px-4 py-2.5 text-[12.5px] font-semibold text-t70"
                    style="border: 1px solid var(--border2)">{{ __('app.close') }}</button>
            <button type="button" wire:click="createUser"
                    class="dbena-btn-gold px-4 py-2.5 text-[12.5px]">{{ __('admin.add_user') }}</button>
        </x-slot:footer>
    </x-modal>
</div>
