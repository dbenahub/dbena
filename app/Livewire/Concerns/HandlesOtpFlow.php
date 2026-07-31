<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Enums\OtpType;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Alur log masuk berbilang langkah, dikongsi oleh UserLoginFlow dan
 * AdminLoginFlow.
 *
 * Prototaip menduplikasi 21 fungsi yang IDENTIK antara Login.dc.html dan
 * Admin Login.dc.html. Di sini logik ditulis sekali sahaja; kedua-dua
 * komponen hanya berbeza pada tema visual dan role yang dibenarkan.
 *
 * Langkah: login → otp → success
 *          login → forgot → resetOtp → resetPassword → resetSuccess
 */
trait HandlesOtpFlow
{
    public string $step = 'login';

    public string $username = '';
    public string $password = '';
    public bool $passwordVisible = false;
    public string $loginError = '';

    public string $otpInput = '';
    public string $otpError = '';

    public string $forgotEmail = '';
    public string $forgotError = '';

    public string $resetOtpInput = '';
    public string $resetOtpError = '';

    public string $newPassword = '';
    public string $confirmPassword = '';
    public string $resetPwError = '';

    /** Dinaikkan pada setiap ralat untuk memaksa animasi shake dimainkan semula. */
    public int $shakeKey = 0;

    public int $resendCooldown = 0;

    /** ID pengguna yang sedang mengesahkan — TIDAK didedahkan ke borang. */
    public ?int $pendingUserId = null;
    public ?int $resetUserId = null;

    abstract protected function allowedRole(): ?UserRole;

    abstract protected function redirectAfterLogin(): string;

    // ── Langkah 1: kelayakan ──────────────────────────────────────────────

    public function submitLogin(OtpService $otp): void
    {
        $this->loginError = '';

        if (trim($this->username) === '' || trim($this->password) === '') {
            $this->fail('loginError', __('auth.fill_both_fields'));

            return;
        }

        // PEMBETULAN isu #6 — had 5 percubaan / 15 minit per IP+username.
        $throttleKey = Str::lower($this->username).'|'.request()->ip();
        $maxAttempts = (int) config('dbena.login.max_attempts');

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $this->fail('loginError', __('auth.throttle', [
                'seconds' => RateLimiter::availableIn($throttleKey),
            ]));

            return;
        }

        $user = User::where('username', $this->username)->first();

        if (! $user || ! Hash::check($this->password, $user->password)) {
            RateLimiter::hit($throttleKey, (int) config('dbena.login.decay_minutes') * 60);
            $this->fail('loginError', __('auth.invalid_credentials'));

            return;
        }

        if (! $user->is_active) {
            $this->fail('loginError', __('auth.account_inactive'));

            return;
        }

        if ($this->allowedRole() !== null && $user->role !== $this->allowedRole()) {
            RateLimiter::hit($throttleKey, (int) config('dbena.login.decay_minutes') * 60);
            $this->fail('loginError', __('auth.not_admin'));

            return;
        }

        RateLimiter::clear($throttleKey);

        // Kelayakan sah — hantar OTP. Kod TIDAK dikembalikan ke komponen.
        $otp->issue($user, OtpType::Login, request()->ip());

        $this->pendingUserId = $user->id;
        $this->password = '';
        $this->otpInput = '';
        $this->otpError = '';
        $this->step = 'otp';
        $this->resendCooldown = (int) config('dbena.otp.resend_cooldown');

        $this->toast(__('auth.otp_sent_toast'));
    }

    // ── Langkah 2: OTP ────────────────────────────────────────────────────

    public function updatedOtpInput(string $value): void
    {
        $this->otpInput = substr(preg_replace('/\D/', '', $value) ?? '', 0, 6);
        $this->otpError = '';
    }

    public function submitOtp(OtpService $otp): void
    {
        if (strlen($this->otpInput) !== 6) {
            $this->fail('otpError', __('auth.otp_length'));

            return;
        }

        $user = User::find($this->pendingUserId);

        if (! $user) {
            $this->backToLogin();

            return;
        }

        if ($error = $otp->verify($user, $this->otpInput, OtpType::Login)) {
            $this->fail('otpError', __($error));

            return;
        }

        Auth::login($user, remember: false);
        request()->session()->regenerate();
        $user->update(['last_login_at' => now()]);

        // Selaraskan locale sesi dengan keutamaan pengguna.
        request()->session()->put('locale', $user->locale);

        $this->step = 'success';
    }

    public function resendOtp(OtpService $otp): void
    {
        $user = User::find($this->pendingUserId);

        if (! $user) {
            $this->backToLogin();

            return;
        }

        if (($remaining = $otp->resendCooldownRemaining($user, OtpType::Login)) > 0) {
            $this->resendCooldown = $remaining;
            $this->toast(__('auth.otp_resend_in', ['seconds' => $remaining]), 'error');

            return;
        }

        $otp->issue($user, OtpType::Login, request()->ip());
        $this->otpInput = '';
        $this->otpError = '';
        $this->resendCooldown = (int) config('dbena.otp.resend_cooldown');

        $this->toast(__('auth.otp_resent_toast'));
    }

    public function tickCooldown(): void
    {
        if ($this->resendCooldown > 0) {
            $this->resendCooldown--;
        }
    }

    // ── Lupa kata laluan ──────────────────────────────────────────────────

    public function goForgot(): void
    {
        $this->step = 'forgot';
        $this->forgotEmail = '';
        $this->forgotError = '';
    }

    public function submitForgot(OtpService $otp): void
    {
        $email = trim(Str::lower($this->forgotEmail));

        if ($email === '') {
            $this->fail('forgotError', __('auth.email_required'));

            return;
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($this->allowedRole() !== null && $user && $user->role !== $this->allowedRole()) {
            $user = null;
        }

        if (! $user || ! $user->is_active) {
            $this->fail('forgotError', __('auth.email_unknown'));

            return;
        }

        $otp->issue($user, OtpType::Reset, request()->ip());

        $this->resetUserId = $user->id;
        $this->resetOtpInput = '';
        $this->resetOtpError = '';
        $this->step = 'resetOtp';
        $this->resendCooldown = (int) config('dbena.otp.resend_cooldown');

        $this->toast(__('auth.reset_code_sent_toast'));
    }

    public function updatedResetOtpInput(string $value): void
    {
        $this->resetOtpInput = substr(preg_replace('/\D/', '', $value) ?? '', 0, 6);
        $this->resetOtpError = '';
    }

    public function submitResetOtp(OtpService $otp): void
    {
        if (strlen($this->resetOtpInput) !== 6) {
            $this->fail('resetOtpError', __('auth.otp_length'));

            return;
        }

        $user = User::find($this->resetUserId);

        if (! $user) {
            $this->backToLogin();

            return;
        }

        if ($error = $otp->verify($user, $this->resetOtpInput, OtpType::Reset)) {
            $this->fail('resetOtpError', __($error));

            return;
        }

        $this->step = 'resetPassword';
        $this->newPassword = '';
        $this->confirmPassword = '';
        $this->resetPwError = '';
    }

    public function submitResetPassword(): void
    {
        // Dinaikkan dari 6 aksara (prototaip) kepada 8 + campuran huruf/nombor.
        if (strlen($this->newPassword) < 8) {
            $this->fail('resetPwError', __('auth.password_too_short'));

            return;
        }

        if (! preg_match('/[A-Za-z]/', $this->newPassword) || ! preg_match('/\d/', $this->newPassword)) {
            $this->fail('resetPwError', __('auth.password_weak'));

            return;
        }

        if ($this->newPassword !== $this->confirmPassword) {
            $this->fail('resetPwError', __('auth.password_mismatch'));

            return;
        }

        $user = User::find($this->resetUserId);

        if (! $user) {
            $this->backToLogin();

            return;
        }

        $user->update(['password' => $this->newPassword]);

        $this->reset(['newPassword', 'confirmPassword', 'resetUserId']);
        $this->step = 'resetSuccess';
    }

    // ── Navigasi & utiliti ────────────────────────────────────────────────

    public function backToLogin(): void
    {
        $this->reset([
            'username', 'password', 'loginError', 'otpInput', 'otpError',
            'forgotEmail', 'forgotError', 'resetOtpInput', 'resetOtpError',
            'newPassword', 'confirmPassword', 'resetPwError',
            'pendingUserId', 'resetUserId',
        ]);
        $this->step = 'login';
    }

    public function togglePasswordVisible(): void
    {
        $this->passwordVisible = ! $this->passwordVisible;
    }

    public function continueToApp(): void
    {
        $this->redirect($this->redirectAfterLogin(), navigate: true);
    }

    private function fail(string $property, string $message): void
    {
        $this->{$property} = $message;
        $this->shakeKey++;
    }

    private function toast(string $message, string $variant = 'success'): void
    {
        $this->dispatch('dbena-toast', message: $message, variant: $variant);
    }

    /** Emel bertopeng untuk skrin OTP — jangan dedahkan alamat penuh. */
    public function maskedEmail(): string
    {
        $user = User::find($this->pendingUserId ?? $this->resetUserId);

        if (! $user) {
            return '—';
        }

        [$name, $domain] = array_pad(explode('@', $user->email, 2), 2, '');
        $visible = mb_substr($name, 0, min(3, mb_strlen($name)));

        return $visible.str_repeat('•', max(3, mb_strlen($name) - 3)).'@'.$domain;
    }
}
