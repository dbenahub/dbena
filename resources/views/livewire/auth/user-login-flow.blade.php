{{-- Port Login.dc.html — kad 440px berpusat, tema emas.
     PEMBETULAN isu #24: min-width:1440px asal digantikan susun atur cair. --}}
<div class="flex min-h-screen w-full items-center justify-center p-4 sm:p-6"
     style="background: oklch(0.15 0.025 260); color: oklch(0.96 0.01 260)">

    <div class="w-full max-w-[440px] rounded-[20px] p-6 shadow-[0_24px_60px_rgba(0,0,0,0.5)] sm:p-10"
         style="background: oklch(0.19 0.025 260); border: 1px solid oklch(1 0 0/0.08)"
         wire:key="card-{{ $shakeKey }}"
         @class(['animate-shake' => $loginError || $otpError || $forgotError || $resetOtpError || $resetPwError])>

        {{-- Kotak logo berbingkai emas --}}
        <div class="mb-7 rounded-[14px] px-3 py-4 text-center"
             style="background: oklch(0.97 0.005 260); border: 1px solid oklch(0.78 0.12 85/0.4)">
            <img src="{{ asset('images/logo-dbena.png') }}" alt="DBENA SDN BHD" class="block h-auto w-full">
        </div>

        @include('livewire.auth._steps', [
            'isAdmin' => false,
            'centered' => true,
            'accent' => 'oklch(0.78 0.12 85)',
            'accentSolid' => 'oklch(0.78 0.12 85)',
            'accentText' => 'oklch(0.15 0.02 260)',
            'inputBg' => 'oklch(0.15 0.02 260)',
            'inputBorder' => 'oklch(1 0 0/0.1)',
        ])

        {{-- Pemilih bahasa untuk guest (keputusan D1) --}}
        <div class="mt-7 flex items-center justify-center gap-1 border-t pt-5"
             style="border-color: oklch(1 0 0/0.08)">
            @foreach (['ms' => 'Bahasa Malaysia', 'en' => 'English'] as $code => $label)
                <form method="POST" action="{{ route('locale.update', $code) }}">
                    @csrf
                    <button type="submit"
                            class="rounded-lg px-3 py-1.5 text-[11.5px] font-semibold transition-colors"
                            @style([
                                'background: oklch(0.78 0.12 85/0.16); color: oklch(0.78 0.12 85)' => app()->getLocale() === $code,
                                'color: oklch(0.55 0.02 260)' => app()->getLocale() !== $code,
                            ])>{{ $label }}</button>
                </form>
            @endforeach
        </div>
    </div>
</div>
