<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Menyenarai akaun dan menetapkan kata laluan dari baris arahan.
 *
 * Seeder menjana kata laluan rawak dan mencetaknya sekali sahaja. Apabila
 * Forge gagal menangkap output arahan — yang berlaku secara berkala —
 * kata laluan itu hilang selama-lamanya dan tiada siapa dapat log masuk.
 *
 * Arahan ini mengambil kata laluan sebagai input, bukan mengeluarkannya
 * sebagai output. Ia berfungsi walaupun Forge langsung tidak memaparkan
 * apa-apa.
 */
class AksesCommand extends Command
{
    protected $signature = 'dbena:akses
                            {username? : Username akaun yang hendak ditetapkan}
                            {--kata-laluan= : Kata laluan baharu (minimum 12 aksara)}
                            {--aktifkan : Aktifkan semula akaun yang dinyahaktifkan}';

    protected $description = 'Senarai akaun, atau tetapkan kata laluan sebuah akaun';

    public function handle(): int
    {
        $username = $this->argument('username');

        if ($username === null) {
            return $this->senarai();
        }

        return $this->tetapkan((string) $username);
    }

    private function senarai(): int
    {
        $users = User::orderByDesc('role')->orderBy('username')->get();

        if ($users->isEmpty()) {
            $this->error('  Tiada akaun dalam pangkalan data. Jalankan: php artisan db:seed');

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('  Akaun sedia ada');
        $this->line('  ─────────────────────────────────────────────────────────');

        foreach ($users as $user) {
            $peranan = $user->role === UserRole::Admin ? 'ADMIN' : 'PENGGUNA';
            $status = $user->is_active ? 'aktif' : 'DINYAHAKTIFKAN';
            $laluan = $user->role === UserRole::Admin ? '/admin/login' : '/login';

            $this->line(sprintf(
                '  %-12s %-10s %-16s %s',
                $user->username,
                $peranan,
                $status,
                $laluan
            ));
        }

        $this->newLine();
        $this->line('  Untuk menetapkan kata laluan:');
        $this->line('    php artisan dbena:akses NAMAPENGGUNA --kata-laluan="KataLaluanAnda123"');
        $this->newLine();

        return self::SUCCESS;
    }

    private function tetapkan(string $username): int
    {
        $user = User::where('username', $username)->first();

        if (! $user) {
            $this->newLine();
            $this->error('  Akaun tidak dijumpai: '.$username);
            $this->line('  Jalankan "php artisan dbena:akses" untuk melihat senarai.');
            $this->newLine();

            return self::FAILURE;
        }

        $kataLaluan = (string) $this->option('kata-laluan');

        if ($kataLaluan === '') {
            $this->newLine();
            $this->error('  Sila berikan --kata-laluan');
            $this->line('    php artisan dbena:akses '.$username.' --kata-laluan="KataLaluanAnda123"');
            $this->newLine();

            return self::FAILURE;
        }

        // Minimum 6 aksara sahaja. OTP bertindak sebagai faktor kedua, jadi
        // kata laluan pendek tidak memadai untuk masuk dengan sendirinya.
        // Had yang lebih ketat hanya akan menghalang pemilik daripada
        // menetapkan kata laluan yang dia mahu di sistemnya sendiri.
        if (mb_strlen($kataLaluan) < 6) {
            $this->newLine();
            $this->error('  Kata laluan terlalu pendek — minimum 6 aksara.');
            $this->newLine();

            return self::FAILURE;
        }

        if (mb_strlen($kataLaluan) < 12) {
            $this->warn('  Nota: kata laluan pendek ('.mb_strlen($kataLaluan).' aksara).');
        }

        $perubahan = ['password' => $kataLaluan];

        if ($this->option('aktifkan')) {
            $perubahan['is_active'] = true;
        }

        $user->update($perubahan);

        $this->newLine();
        $this->info('  Kata laluan ditetapkan.');
        $this->newLine();
        $this->line('  Username : '.$user->username);
        $this->line('  Nama     : '.$user->name);
        $this->line('  Peranan  : '.($user->role === UserRole::Admin ? 'ADMIN' : 'PENGGUNA'));
        $this->line('  Log masuk: '.($user->role === UserRole::Admin ? '/admin/login' : '/login'));
        $this->line('  Status   : '.($user->is_active ? 'aktif' : 'DINYAHAKTIFKAN — guna --aktifkan'));
        $this->newLine();

        if (config('mail.default') === 'log') {
            $this->warn('  AMARAN — log masuk belum boleh diselesaikan.');
            $this->line('  MAIL_MAILER masih "log", jadi kod OTP tidak dihantar ke mana-mana.');
            $this->line('  Selepas memasukkan kata laluan, skrin akan meminta kod 6 angka');
            $this->line('  yang tidak akan sampai. Tetapkan SMTP dahulu — lihat');
            $this->line('  PANDUAN_EMEL_OTP.md.');
            $this->newLine();
        }

        return self::SUCCESS;
    }
}
