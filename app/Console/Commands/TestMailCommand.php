<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Menguji tetapan emel tanpa perlu melalui skrin log masuk.
 *
 * Apabila kod OTP tidak sampai, terdapat empat sebab yang berbeza dan
 * skrin log masuk menunjukkan mesej "kod telah dihantar" yang sama untuk
 * kesemuanya. Arahan ini memisahkan sebab-sebab itu.
 */
class TestMailCommand extends Command
{
    protected $signature = 'dbena:uji-emel {kepada : Alamat emel penerima}';

    protected $description = 'Hantar emel ujian untuk mengesahkan tetapan SMTP';

    public function handle(): int
    {
        $kepada = (string) $this->argument('kepada');
        $mailer = (string) config('mail.default');

        $this->newLine();
        $this->line('  Tetapan semasa');
        $this->line('  ─────────────────────────────────────────────');
        $this->line('  MAIL_MAILER    : '.$mailer);
        $this->line('  MAIL_HOST      : '.(string) config('mail.mailers.smtp.host'));
        $this->line('  MAIL_PORT      : '.(string) config('mail.mailers.smtp.port'));
        $this->line('  MAIL_USERNAME  : '.($this->penyamar((string) config('mail.mailers.smtp.username'))));
        $this->line('  MAIL_PASSWORD  : '.($this->rahsia((string) config('mail.mailers.smtp.password'))));
        $this->line('  MAIL_FROM      : '.(string) config('mail.from.address'));
        $this->newLine();

        if ($mailer === 'log') {
            $this->warn('  MAIL_MAILER masih "log".');
            $this->line('  Emel akan ditulis ke storage/logs/laravel.log dan');
            $this->line('  TIDAK dihantar kepada sesiapa. Ini sebab kod OTP');
            $this->line('  tidak sampai. Tukar kepada smtp dalam .env,');
            $this->line('  kemudian jalankan: php artisan config:clear');
            $this->newLine();

            return self::FAILURE;
        }

        $this->line('  Menghantar ke '.$kepada.' ...');

        try {
            Mail::raw(
                "Ini emel ujian dari Dashboard DBENA.\n\n"
                ."Jika anda menerimanya, tetapan SMTP sudah betul dan kod OTP\n"
                ."akan sampai dengan cara yang sama.\n\n"
                .'Dihantar pada '.now()->format('d/m/Y H:i:s'),
                fn ($mesej) => $mesej->to($kepada)->subject('Ujian Emel — Dashboard DBENA')
            );
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('  GAGAL: '.$e->getMessage());
            $this->newLine();
            $this->line('  Punca lazim:');
            $this->line('  • "Username and Password not accepted" — Gmail menolak');
            $this->line('    kata laluan biasa. Anda perlu App Password 16 aksara.');
            $this->line('  • "Connection could not be established" — port disekat.');
            $this->line('    Cuba MAIL_PORT=465 dengan MAIL_ENCRYPTION=ssl.');
            $this->line('  • "MAIL_FROM_ADDRESS" berbeza daripada MAIL_USERNAME —');
            $this->line('    Gmail hanya benarkan menghantar sebagai diri sendiri.');
            $this->newLine();

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('  BERJAYA DIHANTAR.');
        $this->line('  Periksa peti masuk '.$kepada.' (termasuk folder Spam).');
        $this->newLine();

        return self::SUCCESS;
    }

    /** Tunjuk cukup untuk pengesahan mata, sembunyikan selebihnya. */
    private function penyamar(string $nilai): string
    {
        if ($nilai === '' || $nilai === 'null') {
            return '(kosong)';
        }

        [$sebelum, $selepas] = array_pad(explode('@', $nilai, 2), 2, '');

        return $selepas === ''
            ? mb_substr($sebelum, 0, 2).str_repeat('*', max(0, mb_strlen($sebelum) - 2))
            : mb_substr($sebelum, 0, 2).str_repeat('*', max(0, mb_strlen($sebelum) - 2)).'@'.$selepas;
    }

    private function rahsia(string $nilai): string
    {
        if ($nilai === '' || $nilai === 'null') {
            return '(kosong)';
        }

        return str_repeat('*', mb_strlen($nilai)).'  ('.mb_strlen($nilai).' aksara)';
    }
}
