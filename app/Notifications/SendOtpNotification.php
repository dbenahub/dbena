<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\OtpType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * PEMBETULAN isu #1 — satu-satunya saluran kod OTP sampai kepada pengguna.
 * Kod TIDAK PERNAH dikembalikan ke UI.
 */
class SendOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $code,
        private readonly OtpType $type,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isLogin = $this->type === OtpType::Login;

        return (new MailMessage)
            ->subject(__($isLogin ? 'auth.mail.subject_login' : 'auth.mail.subject_reset'))
            ->greeting(__('auth.mail.greeting', ['name' => $notifiable->name]))
            ->line(__($isLogin ? 'auth.mail.line_login' : 'auth.mail.line_reset'))
            ->line('# '.$this->code)
            ->line(__('auth.mail.expiry', ['minutes' => config('dbena.otp.ttl_minutes')]))
            ->line(__('auth.mail.ignore'))
            ->salutation(__('auth.mail.salutation')."\n".config('app.name'));
    }
}
