<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Service;
use App\Models\User;
use App\Notifications\WeeklyReportNotification;
use App\Services\DashboardMetricsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Laporan Mingguan automatik.
 * PEMBETULAN: dalam prototaip, toggle "Laporan Mingguan" tidak melakukan apa-apa.
 */
class SendWeeklyReports implements ShouldQueue
{
    use Queueable;

    public function handle(DashboardMetricsService $metrics): void
    {
        $year = (int) now()->year;
        $month = (int) now()->month;

        $lines = [];

        foreach (Service::orderBy('sort_order')->get() as $service) {
            $actual = $metrics->sumMetricActual(['revenue_sales'], $year, $month, $service->id);
            $target = (float) $service->monthly_target;
            $pct = $target > 0 ? $actual / $target * 100 : 0;

            $lines[] = sprintf(
                '%s — %s / %s (%s)',
                $service->name,
                $metrics->formatRm($actual),
                $metrics->formatRm($target),
                $metrics->formatPercent($pct)
            );
        }

        $summary = [
            'year' => $year,
            'month' => $month,
            'lines' => $lines,
            'generated_at' => now()->toIso8601String(),
        ];

        User::query()
            ->where('is_active', true)
            ->where('notif_weekly', true)
            ->whereNotNull('email')
            ->cursor()
            ->each(fn (User $user) => $user->notify(new WeeklyReportNotification($summary)));
    }
}
