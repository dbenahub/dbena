<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * PEMBETULAN isu #2: prototaip menyimpan kata laluan terus dalam kod sumber
 * untuk kedua-dua akaun. Di sini kata laluan dijana RAWAK pada setiap larian
 * seeder dan dipaparkan sekali sahaja dalam output konsol — tidak pernah
 * ditulis ke dalam kod, repositori, atau pangkalan data sebagai teks biasa.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = Str::password(16, symbols: false);
        $userPassword = Str::password(16, symbols: false);

        $admin = User::updateOrCreate(['username' => 'DBENASB'], [
            'name' => 'Ahmad Nizam',
            'email' => 'dbenareport@gmail.com',
            'password' => $adminPassword,
            'role' => UserRole::Admin,
            'position' => 'Managing Director',
            'phone' => '012-345 6789',
            'locale' => 'ms',
            'theme' => 'dark',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $user = User::updateOrCreate(['username' => 'dbena'], [
            'name' => 'Pengguna Operasi',
            'email' => 'operasi@dbena.com.my',
            'password' => $userPassword,
            'role' => UserRole::User,
            'position' => 'Executive',
            'locale' => 'ms',
            'theme' => 'dark',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->command?->newLine();
        $this->command?->warn('╔══════════════════════════════════════════════════════════════╗');
        $this->command?->warn('║  AKAUN DEMO DBENA — simpan sekarang, tidak dipapar semula.   ║');
        $this->command?->warn('╚══════════════════════════════════════════════════════════════╝');
        $this->command?->line("  ADMIN  →  username: <fg=yellow>{$admin->username}</>  kata laluan: <fg=yellow>{$adminPassword}</>");
        $this->command?->line("           log masuk di /admin/login");
        $this->command?->line("  USER   →  username: <fg=yellow>{$user->username}</>  kata laluan: <fg=yellow>{$userPassword}</>");
        $this->command?->line("           log masuk di /login");
        $this->command?->newLine();
        $this->command?->comment('  OTP dihantar ke emel. Semasa pembangunan (MAIL_MAILER=log),');
        $this->command?->comment('  baca kod dalam storage/logs/laravel.log.');
        $this->command?->newLine();
    }
}
