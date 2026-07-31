<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CriticalMetric;
use App\Models\CriticalMetricMonth;
use App\Models\CriticalWeeklyEntry;
use Illuminate\Database\Seeder;

/**
 * Keputusan D3 — data untuk BANYAK bulan, bukan Julai sahaja.
 *
 * Prototaip mengunci `hasMonthData = criticalMonth === 'Jul'` (isu #13),
 * bermakna 11 bulan lain sentiasa kosong. Seeder ini mengisi Jan–Jul 2026
 * dengan lengkung pencapaian menaik yang munasabah supaya carta trend,
 * penukar bulan dan dropdown Period boleh diuji dengan bermakna.
 */
class DemoWeeklyDataSeeder extends Seeder
{
    /** Kadar pencapaian bulanan (peratus daripada sasaran) Jan–Jul. */
    private const MONTHLY_ACHIEVEMENT = [
        1 => 0.24, 2 => 0.31, 3 => 0.38, 4 => 0.46, 5 => 0.55, 6 => 0.68, 7 => 0.82,
    ];

    /** Taburan mingguan dalam satu bulan (jumlah = 1.0). */
    private const WEEK_SPLIT = [0.18, 0.27, 0.29, 0.26];

    private const YEAR = 2026;

    public function run(): void
    {
        $metrics = CriticalMetric::with(['targets', 'defaultOwner'])->get();

        foreach ($metrics as $metric) {
            $target = $metric->targets->firstWhere('year', self::YEAR);

            // Sasaran bukan-angka ('Progress') tidak boleh dijana secara berkadar.
            if (! $target?->monthly_target) {
                continue;
            }

            $monthlyTarget = (float) $target->monthly_target;

            foreach (self::MONTHLY_ACHIEVEMENT as $month => $rate) {
                // Sedikit variasi per metrik supaya data tidak kelihatan sintetik.
                $jitter = 1 + ((crc32($metric->metric_key.$month) % 21) - 10) / 100;
                $monthActual = $monthlyTarget * $rate * $jitter;

                foreach (self::WEEK_SPLIT as $index => $share) {
                    $value = $metric->type->value === 'avg'
                        ? $monthlyTarget * $rate * $jitter
                        : $monthActual * $share;

                    CriticalWeeklyEntry::updateOrCreate(
                        [
                            'critical_metric_id' => $metric->id,
                            'year' => self::YEAR,
                            'month' => $month,
                            'week_number' => $index + 1,
                        ],
                        ['value' => round($value, 2)]
                    );
                }

                // Pelan tindakan hanya pada sebahagian baris supaya ketiga-tiga
                // status (Green / Yellow / Red) muncul dalam demo.
                $needsPlan = $rate < 1.0 && (crc32($metric->metric_key) % 3) !== 0;

                CriticalMetricMonth::updateOrCreate(
                    [
                        'critical_metric_id' => $metric->id,
                        'year' => self::YEAR,
                        'month' => $month,
                    ],
                    [
                        'owner_id' => $metric->default_owner_id,
                        'action_plan' => $needsPlan
                            ? 'Tingkatkan aktiviti susulan mingguan dan pantau kadar penukaran.'
                            : null,
                    ]
                );
            }
        }
    }
}
