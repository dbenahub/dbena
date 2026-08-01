<?php

declare(strict_types=1);

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

/**
 * Menghantar emel melalui API HTTPS Brevo, bukan SMTP.
 *
 * Penyedia server ini menyekat setiap port SMTP keluar — 25, 465 dan 587
 * semuanya timed out. Tiada tetapan SMTP boleh mengatasinya. Port 443 pula
 * tidak pernah disekat, kerana menyekatnya bermakna server tidak boleh
 * mencapai apa-apa di internet.
 *
 * Ditulis sendiri dan bukan menggunakan pakej pihak ketiga supaya deploy
 * tidak bergantung pada composer menyelesaikan kebergantungan baharu di
 * server. Keperluannya kecil: satu permintaan POST.
 */
class BrevoApiTransport extends AbstractTransport
{
    private const ENDPOINT = 'https://api.brevo.com/v3/smtp/email';

    public function __construct(
        private readonly string $key,
        private readonly int $timeout = 15,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        if ($this->key === '') {
            throw new TransportException(
                'BREVO_API_KEY tidak ditetapkan. Emel tidak dapat dihantar.'
            );
        }

        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'api-key' => $this->key,
                'accept' => 'application/json',
            ])
            ->asJson()
            ->post(self::ENDPOINT, $this->payload($email));

        if ($response->failed()) {
            // Brevo memulangkan {"code": "...", "message": "..."} apabila
            // gagal. Mesej itu jauh lebih berguna daripada kod status,
            // jadi keluarkannya ke permukaan.
            $sebab = $response->json('message') ?? $response->body();

            throw new TransportException(sprintf(
                'Brevo menolak emel (HTTP %d): %s',
                $response->status(),
                is_string($sebab) ? $sebab : json_encode($sebab)
            ));
        }
    }

    /** @return array<string, mixed> */
    private function payload(Email $email): array
    {
        $payload = [
            'subject' => (string) $email->getSubject(),
            'to' => $this->addresses($email->getTo()),
        ];

        if ($from = $email->getFrom()[0] ?? null) {
            $payload['sender'] = $this->address($from);
        }

        if ($replyTo = $email->getReplyTo()[0] ?? null) {
            $payload['replyTo'] = $this->address($replyTo);
        }

        if ($cc = $this->addresses($email->getCc())) {
            $payload['cc'] = $cc;
        }

        if ($bcc = $this->addresses($email->getBcc())) {
            $payload['bcc'] = $bcc;
        }

        $html = $email->getHtmlBody();
        $text = $email->getTextBody();

        if (filled($html)) {
            $payload['htmlContent'] = is_string($html) ? $html : (string) stream_get_contents($html);
        }

        if (filled($text)) {
            $payload['textContent'] = is_string($text) ? $text : (string) stream_get_contents($text);
        }

        // Brevo menolak permintaan yang tiada kandungan langsung.
        if (! isset($payload['htmlContent']) && ! isset($payload['textContent'])) {
            $payload['textContent'] = ' ';
        }

        return $payload;
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return array<int, array<string, string>>
     */
    private function addresses(array $addresses): array
    {
        return array_values(array_map(fn (Address $a) => $this->address($a), $addresses));
    }

    /** @return array<string, string> */
    private function address(Address $address): array
    {
        $entry = ['email' => $address->getAddress()];

        if ($address->getName() !== '') {
            $entry['name'] = $address->getName();
        }

        return $entry;
    }

    public function __toString(): string
    {
        return 'brevo-api';
    }
}
