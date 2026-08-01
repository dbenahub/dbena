<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OtpType;
use App\Exceptions\OtpDeliveryException;
use App\Models\Otp;
use App\Models\User;
use App\Notifications\SendOtpNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * PEMBETULAN isu #1 — prototaip memaparkan OTP terus di skrin
 * ("Demo: OTP anda ialah 123456").
 *
 * Di sini kod:
 *   • dijana secara kriptografi selamat
 *   • di-HASH sebelum disimpan (tidak pernah plaintext dalam DB)
 *   • dihantar HANYA melalui emel
 *   • tidak pernah dikembalikan kepada lapisan paparan
 */
class OtpService
{
    /** Jana, simpan dan hantar OTP. Tiada nilai kod dikembalikan. */
    public function issue(User $user, OtpType $type, ?string $ip = null): void
    {
        // Batalkan OTP belum guna yang lain untuk jenis sama.
        Otp::where('user_id', $user->id)
            ->where('type', $type)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = $this->generateCode();

        Otp::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'type' => $type,
            'expires_at' => now()->addMinutes((int) config('dbena.otp.ttl_minutes')),
            'attempts' => 0,
            'ip_address' => $ip,
        ]);

        /*
         * Emel dihantar secara segerak, jadi kegagalan SMTP berlaku DI SINI
         * dan bukan senyap di latar belakang. Tanpa penangkapan ini, pengguna
         * melihat halaman ralat mentah dan tiada apa yang menyebut emel.
         */
        try {
            $user->notify(new SendOtpNotification($code, $type));
        } catch (Throwable $e) {
            Log::error('Kod OTP gagal dihantar', [
                'user_id' => $user->id,
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'ralat' => $e->getMessage(),
            ]);

            throw new OtpDeliveryException($e->getMessage(), previous: $e);
        }
    }

    /**
     * Sahkan kod. Pulangkan kunci mesej ralat, atau null jika berjaya.
     */
    public function verify(User $user, string $code, OtpType $type): ?string
    {
        $otp = Otp::where('user_id', $user->id)
            ->where('type', $type)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $otp) {
            return 'auth.otp_not_found';
        }

        if ($otp->isExpired()) {
            return 'auth.otp_expired';
        }

        if (! $otp->hasAttemptsLeft()) {
            return 'auth.otp_too_many_attempts';
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            return $otp->fresh()->hasAttemptsLeft()
                ? 'auth.otp_invalid'
                : 'auth.otp_too_many_attempts';
        }

        $otp->update(['consumed_at' => now()]);

        return null;
    }

    /** Saat berbaki sebelum "Hantar Semula" dibenarkan (cooldown 60s). */
    public function resendCooldownRemaining(User $user, OtpType $type): int
    {
        $latest = Otp::where('user_id', $user->id)
            ->where('type', $type)
            ->latest('id')
            ->first();

        if (! $latest) {
            return 0;
        }

        $cooldown = (int) config('dbena.otp.resend_cooldown');
        $elapsed = $latest->created_at->diffInSeconds(now());

        return (int) max(0, $cooldown - $elapsed);
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
