{{-- Port Admin Login.dc.html — split-panel 46/54, tema merah "RESTRICTED ACCESS".
     Mobile: panel kiri menjadi header ringkas — TIDAK disorok, kerana badge
     amaran perlu kekal kelihatan untuk konteks keselamatan (PRD §6.2). --}}
<div class="flex min-h-screen w-full flex-col lg:flex-row"
     style="background: oklch(0.12 0.01 25); color: oklch(0.96 0.01 260)">

    {{-- ══ PANEL JENAMA ══ --}}
    <div class="relative flex flex-col justify-center border-b px-6 py-8 lg:w-[46%] lg:border-b-0 lg:border-r lg:px-[70px] lg:py-16"
         style="background: oklch(0.16 0.03 25); border-color: oklch(0.6 0.2 25/0.3)">

        {{-- Badge RESTRICTED ACCESS --}}
        <div class="mb-5 inline-flex w-fit items-center gap-2 rounded-[20px] px-3.5 py-1.5 lg:absolute lg:left-[70px] lg:top-8 lg:mb-0"
             style="background: oklch(0.6 0.2 25/0.15); border: 1px solid oklch(0.6 0.2 25/0.4)">
            <i class="ph-duotone ph-lock-key text-sm" style="color: oklch(0.68 0.19 25)" aria-hidden="true"></i>
            <span class="text-[11px] font-bold" style="color: oklch(0.68 0.19 25); letter-spacing: 1px">
                {{ __('auth.restricted_access') }}
            </span>
        </div>

        <div class="mb-5 hidden h-16 w-16 items-center justify-center rounded-2xl lg:flex lg:mb-[26px]"
             style="background: oklch(0.6 0.2 25/0.16); border: 1px solid oklch(0.6 0.2 25/0.4)">
            <i class="ph-duotone ph-shield-warning text-[32px]" style="color: oklch(0.68 0.19 25)" aria-hidden="true"></i>
        </div>

        <div class="font-display text-[24px] font-extrabold leading-tight lg:text-[34px]">
            {{ __('auth.admin_panel_title') }}<br>
            <span style="color: oklch(0.68 0.19 25)">DBENA SDN BHD</span>
        </div>

        <p class="mt-4 max-w-[380px] text-[13px] leading-relaxed text-t65 lg:text-[13.5px]">
            {{ __('auth.admin_panel_desc') }}
        </p>

        <div class="mt-6 flex flex-wrap gap-4 lg:mt-10 lg:gap-6">
            <div class="flex items-center gap-2 text-[12px] text-t60">
                <i class="ph-duotone ph-key" style="color: oklch(0.68 0.19 25)" aria-hidden="true"></i>
                {{ __('auth.two_factor') }}
            </div>
            <div class="flex items-center gap-2 text-[12px] text-t60">
                <i class="ph-duotone ph-envelope-simple" style="color: oklch(0.68 0.19 25)" aria-hidden="true"></i>
                {{ config('mail.from.address') }}
            </div>
        </div>
    </div>

    {{-- ══ PANEL BORANG ══ --}}
    <div class="flex flex-1 items-center justify-center p-6 sm:p-10"
         style="background: oklch(0.14 0.015 25)">
        <div class="w-full max-w-[400px]"
             wire:key="form-{{ $shakeKey }}"
             @class(['animate-shake' => $loginError || $otpError || $forgotError || $resetOtpError || $resetPwError])>

            @include('livewire.auth._steps', [
                'isAdmin' => true,
                'centered' => false,
                'accent' => 'oklch(0.68 0.19 25)',
                'accentSolid' => 'oklch(0.55 0.19 25)',
                'accentText' => 'oklch(0.98 0.005 25)',
                'inputBg' => 'oklch(0.19 0.02 25)',
                'inputBorder' => 'oklch(0.6 0.2 25/0.3)',
            ])

            <div class="mt-7 flex items-center justify-center gap-1 border-t pt-5"
                 style="border-color: oklch(0.6 0.2 25/0.2)">
                @foreach (['ms' => 'Bahasa Malaysia', 'en' => 'English'] as $code => $label)
                    <form method="POST" action="{{ route('locale.update', $code) }}">
                        @csrf
                        <button type="submit"
                                class="rounded-lg px-3 py-1.5 text-[11.5px] font-semibold transition-colors"
                                @style([
                                    'background: oklch(0.6 0.2 25/0.16); color: oklch(0.68 0.19 25)' => app()->getLocale() === $code,
                                    'color: oklch(0.55 0.02 260)' => app()->getLocale() !== $code,
                                ])>{{ $label }}</button>
                    </form>
                @endforeach
            </div>
        </div>
    </div>
</div>
