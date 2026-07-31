{{--
    Langkah alur log masuk yang dikongsi oleh kedua-dua skrin.
    $accent      — warna aksen (emas untuk user, merah untuk admin)
    $accentSolid — warna butang penuh
    $accentText  — warna teks atas butang
    $inputBg / $inputBorder — gaya medan
    $centered    — susunan teks rata tengah (skrin user) atau kiri (admin)
--}}
@php
    $align = $centered ? 'text-center' : 'text-left';
    $errorBox = 'flex items-center gap-2 rounded-[9px] px-3 py-2.5 text-[12.5px]';
@endphp

{{-- ══ LANGKAH: LOG MASUK ══ --}}
@if ($step === 'login')
    <div class="mb-6 {{ $align }}">
        <h1 class="font-display text-[20px] font-extrabold sm:text-[22px]">
            {{ $isAdmin ? __('auth.admin_sign_in') : __('auth.sign_in') }}
        </h1>
        <p class="mt-1 text-[12.5px] text-t60">
            {{ $isAdmin ? __('auth.admin_subtitle') : __('auth.subtitle') }}
        </p>
    </div>

    <form wire:submit="submitLogin" class="flex flex-col gap-4">
        <div>
            <label for="username" class="mb-1.5 block text-[11.5px] text-t55">
                {{ $isAdmin ? __('auth.admin_username') : __('auth.username') }}
            </label>
            <input id="username" type="text" wire:model="username" autocomplete="username" autofocus
                   placeholder="{{ __('auth.username_placeholder') }}"
                   class="w-full rounded-[10px] px-3.5 py-3 text-sm text-t94 transition-colors focus:outline-none"
                   style="background: {{ $inputBg }}; border: 1px solid {{ $inputBorder }}">
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-[11.5px] text-t55">{{ __('auth.password_label') }}</label>
            <div class="relative">
                <input id="password" type="{{ $passwordVisible ? 'text' : 'password' }}"
                       wire:model="password" autocomplete="current-password"
                       placeholder="{{ __('auth.password_placeholder') }}"
                       class="w-full rounded-[10px] py-3 pl-3.5 pr-11 text-sm text-t94 transition-colors focus:outline-none"
                       style="background: {{ $inputBg }}; border: 1px solid {{ $inputBorder }}">
                <button type="button" wire:click="togglePasswordVisible"
                        aria-label="{{ $passwordVisible ? __('auth.hide_password') : __('auth.show_password') }}"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-[17px] text-t55 hover:text-t80">
                    <i class="ph-duotone {{ $passwordVisible ? 'ph-eye-slash' : 'ph-eye' }}" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        @if ($loginError)
            <div role="alert" class="{{ $errorBox }}"
                 style="background: color-mix(in oklch, {{ $accent }} 12%, transparent);
                        border: 1px solid color-mix(in oklch, {{ $accent }} 35%, transparent);
                        color: {{ $accent }}">
                <i class="ph-duotone ph-warning-circle shrink-0 text-base" aria-hidden="true"></i>
                {{ $loginError }}
            </div>
        @endif

        <button type="submit"
                class="mt-1 w-full rounded-[10px] py-3.5 text-sm font-bold transition-[filter] hover:brightness-110"
                style="background: {{ $accentSolid }}; color: {{ $accentText }}"
                wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="submitLogin">{{ __('auth.sign_in') }}</span>
            <span wire:loading wire:target="submitLogin">{{ __('app.loading') }}</span>
        </button>

        <button type="button" wire:click="goForgot"
                class="text-center text-[12.5px] font-semibold" style="color: {{ $accent }}">
            {{ __('auth.forgot_password') }}
        </button>
    </form>
@endif

{{-- ══ LANGKAH: OTP ══ --}}
@if ($step === 'otp' || $step === 'resetOtp')
    @php
        $isReset = $step === 'resetOtp';
        $model = $isReset ? 'resetOtpInput' : 'otpInput';
        $error = $isReset ? $resetOtpError : $otpError;
        $submit = $isReset ? 'submitResetOtp' : 'submitOtp';
    @endphp

    <div class="mb-5 {{ $align }}">
        <i class="ph-duotone ph-shield-check text-[34px]" style="color: {{ $accent }}" aria-hidden="true"></i>
        <h1 class="mt-2.5 font-display text-[19px] font-extrabold">
            {{ $isReset ? __('auth.reset_code_title') : __('auth.otp_title') }}
        </h1>
        <p class="mt-1.5 text-[12.5px] leading-relaxed text-t60">
            {{ __('auth.otp_sent_to') }}<br>
            <b class="text-t94">{{ $this->maskedEmail() }}</b>
        </p>
    </div>

    <form wire:submit="{{ $submit }}" class="flex flex-col gap-4">
        <label for="otp" class="sr-only">{{ __('auth.otp_title') }}</label>
        <input id="otp" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6"
               wire:model.live="{{ $model }}" placeholder="000000" autofocus
               class="w-full rounded-[10px] py-3.5 text-center font-display text-[22px] font-bold text-t94 focus:outline-none"
               style="background: {{ $inputBg }}; border: 1px solid {{ $inputBorder }}; letter-spacing: 10px">

        @if ($error)
            <div role="alert" class="{{ $errorBox }}"
                 style="background: color-mix(in oklch, {{ $accent }} 12%, transparent);
                        border: 1px solid color-mix(in oklch, {{ $accent }} 35%, transparent);
                        color: {{ $accent }}">
                <i class="ph-duotone ph-warning-circle shrink-0 text-base" aria-hidden="true"></i>
                {{ $error }}
            </div>
        @endif

        <button type="submit"
                class="w-full rounded-[10px] py-3.5 text-sm font-bold transition-[filter] hover:brightness-110"
                style="background: {{ $accentSolid }}; color: {{ $accentText }}">
            {{ $isReset ? __('auth.verify_code') : __('auth.otp_verify') }}
        </button>

        <div class="flex items-center justify-between gap-3">
            <button type="button" wire:click="backToLogin"
                    class="flex items-center gap-1.5 text-[12.5px] text-t60 hover:text-t85">
                <i class="ph-duotone ph-arrow-left" aria-hidden="true"></i> {{ __('app.back') }}
            </button>

            @unless ($isReset)
                <div x-data="{ left: @entangle('resendCooldown').live }"
                     x-init="setInterval(() => { if (left > 0) $wire.tickCooldown() }, 1000)">
                    <button type="button" wire:click="resendOtp" x-show="left <= 0"
                            class="text-[12.5px] font-semibold" style="color: {{ $accent }}">
                        {{ __('auth.otp_resend') }}
                    </button>
                    <span x-show="left > 0" x-cloak class="text-[12.5px] text-t50"
                          x-text="'{{ __('auth.otp_resend_in', ['seconds' => '__S__']) }}'.replace('__S__', left)"></span>
                </div>
            @endunless
        </div>

        {{--
            PEMBETULAN isu #1: prototaip memaparkan kotak
            "Demo: OTP anda ialah 123456" di sini. Kotak itu dibuang sepenuhnya —
            kod hanya wujud dalam emel dan sebagai hash dalam pangkalan data.
        --}}
    </form>
@endif

{{-- ══ LANGKAH: LUPA KATA LALUAN ══ --}}
@if ($step === 'forgot')
    <div class="mb-5 {{ $align }}">
        <i class="ph-duotone ph-key text-[34px]" style="color: {{ $accent }}" aria-hidden="true"></i>
        <h1 class="mt-2.5 font-display text-[19px] font-extrabold">{{ __('auth.forgot_title') }}</h1>
        <p class="mt-1.5 text-[12.5px] text-t60">{{ __('auth.forgot_hint') }}</p>
    </div>

    <form wire:submit="submitForgot" class="flex flex-col gap-4">
        <div>
            <label for="forgot-email" class="mb-1.5 block text-[11.5px] text-t55">{{ __('auth.registered_email') }}</label>
            <input id="forgot-email" type="email" wire:model="forgotEmail" autocomplete="email" autofocus
                   placeholder="nama@syarikat.com"
                   class="w-full rounded-[10px] px-3.5 py-3 text-sm text-t94 focus:outline-none"
                   style="background: {{ $inputBg }}; border: 1px solid {{ $inputBorder }}">
        </div>

        @if ($forgotError)
            <div role="alert" class="{{ $errorBox }}"
                 style="background: color-mix(in oklch, {{ $accent }} 12%, transparent);
                        border: 1px solid color-mix(in oklch, {{ $accent }} 35%, transparent);
                        color: {{ $accent }}">
                <i class="ph-duotone ph-warning-circle shrink-0 text-base" aria-hidden="true"></i>
                {{ $forgotError }}
            </div>
        @endif

        <button type="submit"
                class="w-full rounded-[10px] py-3.5 text-sm font-bold transition-[filter] hover:brightness-110"
                style="background: {{ $accentSolid }}; color: {{ $accentText }}">
            {{ __('auth.send_reset_code') }}
        </button>

        <button type="button" wire:click="backToLogin"
                class="flex items-center justify-center gap-1.5 text-[12.5px] text-t60 hover:text-t85">
            <i class="ph-duotone ph-arrow-left" aria-hidden="true"></i> {{ __('auth.back_to_login') }}
        </button>
    </form>
@endif

{{-- ══ LANGKAH: SET SEMULA KATA LALUAN ══ --}}
@if ($step === 'resetPassword')
    <div class="mb-5 {{ $align }}">
        <i class="ph-duotone ph-lock-key text-[34px]" style="color: {{ $accent }}" aria-hidden="true"></i>
        <h1 class="mt-2.5 font-display text-[19px] font-extrabold">{{ __('auth.reset_password_title') }}</h1>
    </div>

    <form wire:submit="submitResetPassword" class="flex flex-col gap-4">
        <div>
            <label for="new-pw" class="mb-1.5 block text-[11.5px] text-t55">{{ __('auth.new_password') }}</label>
            <input id="new-pw" type="password" wire:model="newPassword" autocomplete="new-password" autofocus
                   placeholder="{{ __('auth.new_password_placeholder') }}"
                   class="w-full rounded-[10px] px-3.5 py-3 text-sm text-t94 focus:outline-none"
                   style="background: {{ $inputBg }}; border: 1px solid {{ $inputBorder }}">
        </div>
        <div>
            <label for="confirm-pw" class="mb-1.5 block text-[11.5px] text-t55">{{ __('auth.confirm_password') }}</label>
            <input id="confirm-pw" type="password" wire:model="confirmPassword" autocomplete="new-password"
                   placeholder="{{ __('auth.confirm_password_placeholder') }}"
                   class="w-full rounded-[10px] px-3.5 py-3 text-sm text-t94 focus:outline-none"
                   style="background: {{ $inputBg }}; border: 1px solid {{ $inputBorder }}">
        </div>

        @if ($resetPwError)
            <div role="alert" class="{{ $errorBox }}"
                 style="background: color-mix(in oklch, {{ $accent }} 12%, transparent);
                        border: 1px solid color-mix(in oklch, {{ $accent }} 35%, transparent);
                        color: {{ $accent }}">
                <i class="ph-duotone ph-warning-circle shrink-0 text-base" aria-hidden="true"></i>
                {{ $resetPwError }}
            </div>
        @endif

        <button type="submit"
                class="w-full rounded-[10px] py-3.5 text-sm font-bold transition-[filter] hover:brightness-110"
                style="background: {{ $accentSolid }}; color: {{ $accentText }}">
            {{ __('auth.reset_password_button') }}
        </button>
    </form>
@endif

{{-- ══ LANGKAH: KEJAYAAN ══ --}}
@if ($step === 'success')
    <div class="py-2.5 text-center">
        <i class="ph-duotone ph-check-circle text-[46px]" style="color: oklch(0.72 0.15 145)" aria-hidden="true"></i>
        <h1 class="mt-3.5 font-display text-[19px] font-extrabold">{{ __('auth.login_success') }}</h1>
        <p class="mt-2 text-[12.5px] text-t60">
            {{ $isAdmin ? __('auth.redirecting_admin') : __('auth.redirecting') }}
        </p>
        <button type="button" wire:click="continueToApp"
                class="mt-5 w-full rounded-[10px] py-3.5 text-sm font-bold transition-[filter] hover:brightness-110"
                style="background: {{ $accentSolid }}; color: {{ $accentText }}"
                x-init="setTimeout(() => $wire.continueToApp(), 900)">
            {{ __('auth.continue') }}
        </button>
    </div>
@endif

{{-- ══ LANGKAH: KATA LALUAN DIKEMASKINI ══ --}}
@if ($step === 'resetSuccess')
    <div class="py-2.5 text-center">
        <i class="ph-duotone ph-check-circle text-[46px]" style="color: oklch(0.72 0.15 145)" aria-hidden="true"></i>
        <h1 class="mt-3.5 font-display text-[19px] font-extrabold">{{ __('auth.password_updated') }}</h1>
        <p class="mt-2 text-[12.5px] text-t60">{{ __('auth.password_updated_hint') }}</p>
        <button type="button" wire:click="backToLogin"
                class="mt-5 w-full rounded-[10px] py-3.5 text-sm font-bold transition-[filter] hover:brightness-110"
                style="background: {{ $accentSolid }}; color: {{ $accentText }}">
            {{ __('auth.back_to_login') }}
        </button>
    </div>
@endif
