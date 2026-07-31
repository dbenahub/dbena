<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $summary
     */
    public function __construct(private readonly array $summary) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(__('laporan.page_title').' — '.config('app.name'))
            ->greeting(__('auth.mail.greeting', ['name' => $notifiable->name]))
            ->line(__('laporan.page_subtitle'));

        foreach ($this->summary['lines'] ?? [] as $line) {
            $mail->line($line);
        }

        return $mail
            ->action(__('app.view_dashboard'), route('dashboard'))
            ->salutation(__('auth.mail.salutation')."\n".config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return $this->summary;
    }
}
