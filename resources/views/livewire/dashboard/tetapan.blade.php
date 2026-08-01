<div class="flex flex-col gap-5 sm:gap-6 xl:max-w-4xl">

    {{-- ══ Keutamaan Sistem ══ --}}
    <div class="dbena-card p-5 sm:p-6">
        <div class="mb-1.5 flex items-center gap-2.5">
            <i class="ph-duotone ph-shield-check text-xl" style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
            <h2 class="text-base font-bold">{{ __('tetapan.system_preferences') }}</h2>
        </div>

        <div class="mt-5 flex flex-col gap-[18px]">
            <x-toggle-switch :on="$notifEmail" wire:click="$toggle('notifEmail')" :label="__('tetapan.notif_email')" />
            <x-toggle-switch :on="$notifWeekly" wire:click="$toggle('notifWeekly')" :label="__('tetapan.notif_weekly')" />
            <x-toggle-switch :on="$notifSound" wire:click="$toggle('notifSound')" :label="__('tetapan.notif_sound')" />
        </div>

        <button type="button" wire:click="savePreferences"
                class="dbena-btn-gold mt-6 w-full py-3 text-[13.5px] sm:w-auto sm:px-8">
            {{ __('tetapan.save_settings') }}
        </button>
    </div>

    {{-- ══ Kad Profil (keputusan D2 — dibina semula) ══ --}}
    <div class="dbena-card p-5 sm:p-6">
        <div class="mb-1.5 flex items-center gap-2.5">
            <i class="ph-duotone ph-user-circle text-xl" style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
            <h2 class="text-base font-bold">{{ __('tetapan.profile') }}</h2>
        </div>
        <p class="mb-5 text-[12px] text-t55">{{ __('tetapan.profile_hint') }}</p>

        {{-- Avatar — upload fail SEBENAR ke storan (betulkan isu #22) --}}
        <div class="mb-6 flex flex-wrap items-center gap-4 border-b pb-6" style="border-color: var(--border3)">
            @if ($avatar)
                <img src="{{ $avatar->temporaryUrl() }}" alt=""
                     class="h-20 w-20 rounded-full object-cover"
                     style="border: 2px solid oklch(0.78 0.12 85/0.5)">
            @else
                <x-avatar :url="$user->avatarUrl()" :initials="$user->initials()" :size="80" />
            @endif

            <div class="min-w-0 flex-1">
                <label class="mb-1 block text-[12.5px] font-semibold">{{ __('tetapan.avatar') }}</label>
                <p class="mb-2.5 text-[11.5px] text-t55">{{ __('tetapan.avatar_hint') }}</p>

                <div class="flex flex-wrap items-center gap-2">
                    <label class="cursor-pointer rounded-[9px] px-3.5 py-2 text-[12.5px] font-semibold text-t80"
                           style="border: 1px solid var(--border2)">
                        <i class="ph-duotone ph-upload-simple mr-1" aria-hidden="true"></i>
                        {{ __('tetapan.upload_avatar') }}
                        <input type="file" wire:model="avatar" accept="image/jpeg,image/png,image/webp" class="sr-only">
                    </label>

                    @if ($avatar)
                        <button type="button" wire:click="saveAvatar"
                                class="dbena-btn-gold px-3.5 py-2 text-[12.5px]">{{ __('app.save') }}</button>
                    @elseif ($user->avatar_path)
                        <button type="button" wire:click="removeAvatar"
                                class="rounded-[9px] px-3.5 py-2 text-[12.5px] font-semibold"
                                style="border: 1px solid oklch(0.6 0.2 25/0.4); color: oklch(0.7 0.15 25)">
                            {{ __('tetapan.remove_avatar') }}
                        </button>
                    @endif

                    <span wire:loading wire:target="avatar" class="text-[12px] text-t55">{{ __('app.loading') }}</span>
                </div>

                @error('avatar')
                    <p class="mt-2 text-[12px]" style="color: oklch(0.65 0.2 25)">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <form wire:submit="saveProfile" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ([
                ['model' => 'name', 'label' => __('tetapan.name'), 'type' => 'text'],
                ['model' => 'position', 'label' => __('tetapan.position'), 'type' => 'text'],
                ['model' => 'email', 'label' => __('tetapan.email'), 'type' => 'email'],
                ['model' => 'phone', 'label' => __('tetapan.phone'), 'type' => 'tel'],
            ] as $field)
                <div>
                    <label for="f-{{ $field['model'] }}" class="mb-1.5 block text-[11.5px] text-t55">{{ $field['label'] }}</label>
                    <input id="f-{{ $field['model'] }}" type="{{ $field['type'] }}"
                           wire:model="{{ $field['model'] }}" class="dbena-input">
                    @error($field['model'])
                        <p class="mt-1.5 text-[12px]" style="color: oklch(0.65 0.2 25)">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <div class="sm:col-span-2">
                <button type="submit" class="dbena-btn-gold w-full py-3 text-[13.5px] sm:w-auto sm:px-8">
                    {{ __('app.save') }}
                </button>
            </div>
        </form>
    </div>

    {{-- ══ Paparan & Bahasa ══ --}}
    <div class="dbena-card p-5 sm:p-6">
        <div class="mb-5 flex items-center gap-2.5">
            <i class="ph-duotone ph-palette text-xl" style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
            <h2 class="text-base font-bold">{{ __('tetapan.appearance') }}</h2>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <span class="mb-2 block text-[11.5px] text-t55">{{ __('tetapan.theme') }}</span>
                <div class="flex gap-2">
                    @foreach (['dark' => __('tetapan.theme_dark'), 'light' => __('tetapan.theme_light')] as $value => $label)
                        <button type="button" wire:click="$set('theme', '{{ $value }}')"
                                aria-pressed="{{ $theme === $value ? 'true' : 'false' }}"
                                class="flex-1 rounded-[10px] py-2.5 text-[12.5px] font-semibold transition-colors"
                                @style([
                                    'background: oklch(0.78 0.12 85); color: oklch(0.15 0.02 260)' => $theme === $value,
                                    'border: 1px solid var(--border2); color: var(--t70)' => $theme !== $value,
                                ])>{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <div>
                <span class="mb-2 block text-[11.5px] text-t55">{{ __('tetapan.language') }}</span>
                <div class="flex gap-2">
                    @foreach (['ms' => __('tetapan.language_ms'), 'en' => __('tetapan.language_en')] as $value => $label)
                        <button type="button" wire:click="$set('locale', '{{ $value }}')"
                                aria-pressed="{{ $locale === $value ? 'true' : 'false' }}"
                                class="flex-1 rounded-[10px] py-2.5 text-[12.5px] font-semibold transition-colors"
                                @style([
                                    'background: oklch(0.78 0.12 85); color: oklch(0.15 0.02 260)' => $locale === $value,
                                    'border: 1px solid var(--border2); color: var(--t70)' => $locale !== $value,
                                ])>{{ $label }}</button>
                    @endforeach
                </div>
            </div>
        </div>

        <button type="button" wire:click="saveAppearance"
                class="dbena-btn-gold mt-6 w-full py-3 text-[13.5px] sm:w-auto sm:px-8">
            {{ __('app.save') }}
        </button>
    </div>

    {{-- ══ Pautan Admin Panel — hanya untuk role admin ══ --}}
    @if ($user->isAdmin())
        <a href="{{ route('admin.panel') }}" wire:navigate
           class="dbena-card flex items-center justify-center gap-2 p-4 text-[12.5px] font-semibold text-t70 transition-colors hover:bg-hover">
            <i class="ph-duotone ph-shield-check" style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
            {{ __('app.admin_panel') }}
        </a>
    @endif
</div>
