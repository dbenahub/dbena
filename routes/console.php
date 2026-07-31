<?php

declare(strict_types=1);

use App\Jobs\SendWeeklyReports;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Tugasan Berjadual
|--------------------------------------------------------------------------
| Forge cron: * * * * * php artisan schedule:run
*/

// ── Tarik Google Sheet automatik ──
$interval = (int) config('dbena.sheets.sync_interval_minutes');

if ($interval > 0) {
    $schedule = Schedule::command('dbena:sync-sheets')
        ->name('dbena:sync-sheets')
        ->withoutOverlapping()
        ->runInBackground();

    match (true) {
        $interval <= 1 => $schedule->everyMinute(),
        $interval <= 5 => $schedule->everyFiveMinutes(),
        $interval <= 10 => $schedule->everyTenMinutes(),
        $interval <= 15 => $schedule->everyFifteenMinutes(),
        $interval <= 30 => $schedule->everyThirtyMinutes(),
        default => $schedule->hourly(),
    };
}

// ── Laporan Mingguan automatik — Isnin 8:00 pagi ──
Schedule::job(new SendWeeklyReports)
    ->weeklyOn(1, '08:00')
    ->timezone('Asia/Kuala_Lumpur')
    ->name('dbena:weekly-report')
    ->withoutOverlapping();

// ── Pembersihan ──
Schedule::command('model:prune', ['--model' => App\Models\Otp::class])->hourly();
Schedule::command('dbena:prune-sheet-logs')->daily();
