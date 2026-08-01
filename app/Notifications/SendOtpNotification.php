<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\OtpType;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * PEMBETULAN isu #1 — satu-satunya saluran kod OTP sampai kepada pengguna.
 * Kod TIDAK PERNAH dikembalikan ke UI.
 *
 * SENGAJA TIDAK BERBARIS (tiada ShouldQueue). Kod ini sah selama beberapa
 * minit sahaja dan pengguna sedang menunggu di skrin. Jika ia dibaris,
 * emel hanya keluar apabila pekerja barisan berjalan — dan jika pekerja
 * itu tidak dipasang di server, kod tersangkut dalam jadual `jobs`
 * selama-lamanya tanpa sebarang ralat. Menghantar terus bermakna kegagalan
 * SMTP muncul serta-merta sebagai ralat yang boleh dilihat.
 */
class SendOtpNotification extends Notification
{
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

        // Peti masuk ini dikongsi — satu alamat menerima kod untuk ramai
        // orang. Tanpa nama dalam subjek, penerima terpaksa membuka setiap
        // emel untuk mencari kod yang betul, dan dua permintaan serentak
        // menjadi mustahil dibezakan.
        $subject = __($isLogin ? 'auth.mail.subject_login' : 'auth.mail.subject_reset')
            .' — '.$notifiable->name;

        return (new MailMessage)
            ->subject($subject)
            ->greeting(__('auth.mail.greeting', ['name' => $notifiable->name]))
            ->line(__($isLogin ? 'auth.mail.line_login' : 'auth.mail.line_reset'))
            ->line(__('auth.mail.for_account', [
                'username' => $notifiable->username,
                'role' => $notifiable->role->label(),
            ]))
            ->line('# '.$this->code)
            ->line(__('auth.mail.expiry', ['minutes' => config('dbena.otp.ttl_minutes')]))
            ->line(__('auth.mail.ignore'))
            ->salutation(__('auth.mail.salutation')."\n".config('app.name'));
    }
}
