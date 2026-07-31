<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MetricStatus;
use App\Enums\MetricValueType;
use App\Enums\PeriodMode;
use App\Enums\ServiceStatus;
use App\Enums\ViewMode;
use App\Models\CriticalMetric;
use App\Models\IndexTier;
use App\Models\Project;
use App\Models\Service;
use App\Models\YearGrowthFactor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Semua formula bisnes DBENA — satu tempat, boleh diuji unit.
 *
 * Kelas ini menggantikan pengiraan yang bertaburan dalam renderVals()
 * prototaip. Setiap kaedah bebas daripada Livewire, HTTP dan sesi.
 *
 * Rujukan: PRD.md §7.1 dan ANALISIS_KOMPONEN.md §5.6.
 */
class DashboardMetricsService
{
    // ── Format ────────────────────────────────────────────────────────────

    /** Prototaip: fmtRM(n) = 'RM' + Math.round(n).toLocaleString('en-US') */
    public function formatRm(float|int|null $value): string
    {
        return $value === null ? '—' : 'RM'.number_format((float) round($value));
    }

    /** Prototaip: fmtNum(n) = Math.round(n).toLocaleString('en-US') */
    public function formatNumber(float|int|null $value): string
    {
        return $value === null ? '—' : number_format((float) round($value));
    }

    public function formatPercent(?float $pct, int $decimals = 1): string
    {
        return $pct === null ? '—' : number_format($pct, $decimals).'%';
    }

    // ── Faktor tahun ──────────────────────────────────────────────────────

    public function yearFactor(int $year): float
    {
        return YearGrowthFactor::factorFor($year);
    }

    // ── Kalendar minggu ───────────────────────────────────────────────────

    /**
     * Sempadan 4 minggu dalam bulan, ditakrif oleh HARI KHAMIS.
     *
     * Ini bukan minggu kalendar Isnin–Ahad standard. Prototaip mengumpul semua
     * hari Khamis dalam bulan, kemudian:
     *   w1end = Khamis KEDUA (thursdays[1]), w2end = ketiga, w3end = keempat.
     * Jika bulan tidak cukup Khamis, gunakan hari terakhir bulan.
     *
     * @return array<int, array{int, int}> [[mulaHari, akhirHari], ...] × 4
     */
    public function getMonthWeeks(int $month, int $year): array
    {
        $start = CarbonImmutable::create($year, $month, 1);
        $daysInMonth = $start->daysInMonth;

        $thursdays = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            if ($start->setDay($day)->dayOfWeek === CarbonImmutable::THURSDAY) {
                $thursdays[] = $day;
            }
        }

        $w1End = $thursdays[1] ?? $daysInMonth;
        $w2End = $thursdays[2] ?? $daysInMonth;
        $w3End = $thursdays[3] ?? $daysInMonth;

        return [
            [1, $w1End],
            [min($w1End + 1, $daysInMonth), $w2End],
            [min($w2End + 1, $daysInMonth), $w3End],
            [min($w3End + 1, $daysInMonth), $daysInMonth],
        ];
    }

    /**
     * Label dua baris untuk header jadual Data Kritikal.
     * Prototaip: 'Minggu {n}\n{DD}/{MM}-{DD}/{MM}'
     *
     * @return array<int, string>
     */
    public function getCriticalWeekLabels(int $month, int $year): array
    {
        $mm = str_pad((string) $month, 2, '0', STR_PAD_LEFT);

        return collect($this->getMonthWeeks($month, $year))
            ->map(function (array $week, int $index) use ($mm): string {
                $from = str_pad((string) $week[0], 2, '0', STR_PAD_LEFT);
                $to = str_pad((string) $week[1], 2, '0', STR_PAD_LEFT);

                return __('service.week_n', ['n' => $index + 1])."\n{$from}/{$mm}-{$to}/{$mm}";
            })
            ->all();
    }

    // ── Status ────────────────────────────────────────────────────────────

    /** Ambang 35% (config: dbena.service_status_threshold). */
    public function calculateServiceStatus(?float $pct): ServiceStatus
    {
        $threshold = (float) config('dbena.service_status_threshold');

        return ($pct ?? 0) >= $threshold
            ? ServiceStatus::Memuaskan
            : ServiceStatus::PerluDipertingkat;
    }

    /**
     * Status satu baris Data Kritikal.
     *
     * Susunan keputusan sama seperti prototaip:
     *   1. tiada data bulan itu  → Belum Update
     *   2. pencapaian >= 100%    → Green
     *   3. ada pelan tindakan    → Yellow
     *   4. selainnya             → Red
     *
     * Nota: apabila sasaran bukan-angka ('Progress'), $pct adalah null dan
     * baris jatuh ke Yellow/Red bergantung pada pelan tindakan — sama seperti
     * prototaip, tetapi kini terserlah sebagai keputusan sedar (soalan Q6).
     */
    public function calculateMetricStatus(
        ?float $actual,
        ?float $target,
        ?string $actionPlan,
        bool $hasMonthData
    ): MetricStatus {
        if (! $hasMonthData || $actual === null) {
            return MetricStatus::BelumUpdate;
        }

        if ($target !== null && $target > 0 && ($actual / $target * 100) >= 100) {
            return MetricStatus::Green;
        }

        return filled(trim((string) $actionPlan)) ? MetricStatus::Yellow : MetricStatus::Red;
    }

    // ── Sasaran mingguan ──────────────────────────────────────────────────

    /**
     * Prototaip menggunakan pembundaran BERBEZA mengikut jenis metrik:
     *   ceil()  untuk bilangan (quotation, site visit)
     *   round() untuk amaun (Amount Quotation Release)
     */
    public function calculateWeeklyTarget(?float $monthlyTarget, MetricValueType $valueType): ?float
    {
        if ($monthlyTarget === null) {
            return null;
        }

        return $valueType === MetricValueType::Currency
            ? round($monthlyTarget / 4)
            : ceil($monthlyTarget / 4);
    }

    // ── Untung & tier ─────────────────────────────────────────────────────

    /** Margin anggaran 18% (config: dbena.profit_margin). */
    public function calculateEstimatedProfit(float $actual): float
    {
        return $actual * (float) config('dbena.profit_margin');
    }

    /**
     * Tier TERTINGGI yang dipenuhi oleh hasil semasa.
     * Mod tahunan mendarabkan threshold dengan 12.
     *
     * @param  Collection<int, IndexTier>  $tiers
     */
    public function calculateTierIndex(Collection $tiers, float $actual, ViewMode $mode): IndexTier
    {
        $multiplier = $mode->tierMultiplier();
        $sorted = $tiers->sortBy('sort_order')->values();
        $current = $sorted->first();

        foreach ($sorted as $tier) {
            if ($actual >= $tier->revenueFor($multiplier)) {
                $current = $tier;
            }
        }

        return $current;
    }

    /** Lebar bar piramid: 100% − (index × 16%). */
    public function calculateTierWidthPct(int $index): float
    {
        return max(0, 100 - ($index * (int) config('dbena.tier_pyramid_step')));
    }

    // ── Analisis ──────────────────────────────────────────────────────────

    /**
     * PEMBETULAN isu #14: prototaip mengunci `monthsLeft = 5`.
     * Kini dikira daripada bulan semasa berbanding akhir tahun fiskal.
     */
    public function monthsLeftInFiscalYear(?CarbonImmutable $now = null): int
    {
        $now ??= CarbonImmutable::now();
        $endMonth = (int) config('dbena.fiscal_year_end_month');

        $remaining = $endMonth - $now->month;

        // Sekurang-kurangnya 1 supaya pembahagian tidak pecah pada bulan akhir.
        return max(1, $remaining);
    }

    public function calculateRequiredRunRate(float $gap, ?CarbonImmutable $now = null): float
    {
        return $gap / $this->monthsLeftInFiscalYear($now);
    }

    public function calculateAvgProjectValue(float $sales, int $projectCount): float
    {
        return $projectCount > 0 ? $sales / $projectCount : 0.0;
    }

    /**
     * PEMBETULAN isu #15: prototaip mengunci '8.2%'.
     * Kini: (projek disahkan ÷ quotation dikeluarkan) × 100.
     */
    public function calculateConversionRate(int $year, ?int $month = null, ?int $serviceId = null): float
    {
        $projects = Project::query()
            ->converted()
            ->forPeriod($year, $month)
            ->when($serviceId, fn ($q) => $q->where('service_id', $serviceId))
            ->count();

        $quotations = $this->sumMetricActual(['no_of_new_quotation'], $year, $month, $serviceId);

        return $quotations > 0 ? ($projects / $quotations) * 100 : 0.0;
    }

    public function calculateAvgDealValue(float $revenue, int $projectCount): float
    {
        return $projectCount > 0 ? $revenue / $projectCount : 0.0;
    }

    // ── Period (keputusan D3) ─────────────────────────────────────────────

    /**
     * PEMBETULAN isu #18: dalam prototaip `periodConfig.mult` dikira tetapi
     * tidak pernah digunakan. Kini pengganda benar-benar dipakai:
     * Mingguan ×1 · Bulanan ×4.33 · Suku Tahunan ×13.
     */
    public function applyPeriodMultiplier(float $weeklyValue, PeriodMode $mode): float
    {
        return $weeklyValue * $mode->multiplier();
    }

    /** Tukar nilai bulanan kepada unit period yang dipilih. */
    public function toPeriodUnit(float $monthlyValue, PeriodMode $mode): float
    {
        return match ($mode) {
            PeriodMode::Weekly => $monthlyValue / 4.33,
            PeriodMode::Monthly => $monthlyValue,
            PeriodMode::Quarterly => $monthlyValue * 3,
        };
    }

    // ── Agregat data kritikal ─────────────────────────────────────────────

    /**
     * Jumlah nilai sebenar bagi metrik tertentu merentasi servis.
     *
     * @param  array<int, string>  $metricKeys
     */
    public function sumMetricActual(array $metricKeys, int $year, ?int $month = null, ?int $serviceId = null): float
    {
        return (float) CriticalMetric::query()
            ->whereIn('metric_key', $metricKeys)
            ->when($serviceId, fn ($q) => $q->where('service_id', $serviceId))
            ->join('critical_weekly_entries as e', 'e.critical_metric_id', '=', 'critical_metrics.id')
            ->where('e.year', $year)
            ->when($month, fn ($q) => $q->where('e.month', $month))
            ->sum('e.value');
    }

    /**
     * Jumlah sasaran bulanan bagi metrik tertentu merentasi servis.
     *
     * @param  array<int, string>  $metricKeys
     */
    public function sumMetricTarget(array $metricKeys, int $year, ?int $serviceId = null, int $monthCount = 1): float
    {
        $sum = (float) CriticalMetric::query()
            ->whereIn('metric_key', $metricKeys)
            ->when($serviceId, fn ($q) => $q->where('service_id', $serviceId))
            ->join('critical_metric_targets as t', 't.critical_metric_id', '=', 'critical_metrics.id')
            ->where('t.year', $year)
            ->whereNotNull('t.monthly_target')
            ->sum('t.monthly_target');

        return $sum * $monthCount;
    }

    /** Peratus perubahan actual berbanding sasaran. */
    public function percentChange(float $actual, float $target): float
    {
        return $target > 0 ? (($actual - $target) / $target) * 100 : 0.0;
    }

    /** Label arah: '↑ +12.3%' atau '↓ -8.1%'. */
    public function changeLabel(float $pct): string
    {
        return ($pct >= 0 ? '↑ +' : '↓ ').number_format($pct, 1).'%';
    }

    public function changeColor(float $pct): string
    {
        return $pct >= 0 ? 'oklch(0.72 0.15 145)' : 'oklch(0.6 0.2 25)';
    }

    // ── Carta ─────────────────────────────────────────────────────────────

    /**
     * Bina data carta bar + garis sasaran untuk <x-trend-chart>.
     *
     * Koordinat SVG mengikut prototaip tepat (viewBox 1180×380):
     *   x = i / (n−1) × 1160 + 20
     *   y = (1 − nilai/max) × 340 + 20
     *
     * @param  array<int, string>  $labels
     * @param  array<int, float|null>  $actuals
     * @param  array<int, float>  $targets
     * @return array{bars: array, dots: array, linePoints: string, maxLabel: string}
     */
    public function buildChart(array $labels, array $actuals, array $targets, MetricValueType $valueType = MetricValueType::Currency): array
    {
        $cfg = config('dbena.chart');
        $count = count($labels);

        $allValues = array_merge(
            array_filter($actuals, fn ($v) => $v !== null),
            $targets
        );
        $max = $allValues === [] ? 0.0 : (float) max($allValues);
        $max = $max > 0 ? $max : 1.0;

        $bars = [];
        foreach ($labels as $i => $label) {
            $value = $actuals[$i] ?? null;
            $bars[] = [
                'label' => $label,
                'hasValue' => $value !== null,
                'pctHeight' => $value !== null ? min(100, $value / $max * 100).'%' : '0%',
                'valueLabel' => $value !== null ? $valueType->format($value) : '',
            ];
        }

        $dots = [];
        foreach ($targets as $i => $value) {
            $dots[] = [
                'x' => number_format(
                    $count > 1 ? ($i / ($count - 1) * $cfg['plot_width'] + $cfg['offset']) : $cfg['offset'],
                    1, '.', ''
                ),
                'y' => number_format(
                    (1 - $value / $max) * $cfg['plot_height'] + $cfg['offset'],
                    1, '.', ''
                ),
            ];
        }

        return [
            'bars' => $bars,
            'dots' => $dots,
            'linePoints' => collect($dots)->map(fn (array $d) => $d['x'].','.$d['y'])->implode(' '),
            'maxLabel' => $this->formatNumber($max),
        ];
    }

    /**
     * Carta unjuran 10 tahun.
     * Prototaip: actual[y] = baseSales × factor[y] × 12
     */
    public function buildYearlyChart(float $baseMonthlySales, float $baseMonthlyTarget, array $years): array
    {
        $factors = YearGrowthFactor::map();

        $actuals = [];
        $targets = [];
        $labels = [];

        foreach ($years as $year) {
            $factor = $factors[$year] ?? 1.0;
            $labels[] = (string) $year;
            $actuals[] = $baseMonthlySales * $factor * 12;
            $targets[] = $baseMonthlyTarget * $factor * 12;
        }

        return $this->buildChart($labels, $actuals, $targets);
    }

    /**
     * Bar bertindan mengikut servis.
     * `flexVal` menggunakan max(0.001, nilai) supaya segmen sifar tidak
     * meruntuhkan susun atur flex (sama seperti prototaip).
     *
     * @param  Collection<int, Service>  $services
     * @param  array<int, array<int, float>>  $monthlyByService  [serviceId => [month => value]]
     * @param  array<int, string>  $monthLabels
     */
    public function buildStackedBars(Collection $services, array $monthlyByService, array $monthLabels): array
    {
        $totals = [];
        foreach (array_keys($monthLabels) as $i) {
            $totals[$i] = collect($services)->sum(fn (Service $s) => $monthlyByService[$s->id][$i] ?? 0);
        }

        $maxTotal = max($totals) ?: 1;

        $bars = [];
        foreach ($monthLabels as $i => $label) {
            $segments = [];
            foreach ($services as $service) {
                $value = $monthlyByService[$service->id][$i] ?? 0;
                $segments[] = [
                    'key' => $service->key,
                    'name' => $service->name,
                    'color' => $service->chart_color,
                    'flexVal' => max(0.001, $value),
                    'value' => $value,
                ];
            }

            $bars[] = [
                'label' => $label,
                'totalPct' => ($totals[$i] / $maxTotal * 100).'%',
                'totalLabel' => $this->formatRm($totals[$i]),
                'segments' => $segments,
            ];
        }

        return $bars;
    }

    // ── Prestasi PIC ──────────────────────────────────────────────────────

    /**
     * Skor pemilik data.
     * PIC sistem (INFO) dikecualikan oleh pemanggil melalui scopeScorable().
     *
     * @param  Collection<int, array{status: MetricStatus, label: string}>  $rows
     * @return array{scorePct: int, green: int, yellow: int, red: int, total: int, criticalMetrics: array<int, string>}
     */
    public function calculateOwnerScore(Collection $rows): array
    {
        $total = $rows->count();
        $green = $rows->where('status', MetricStatus::Green)->count();
        $yellow = $rows->where('status', MetricStatus::Yellow)->count();
        $red = $rows->where('status', MetricStatus::Red)->count();

        return [
            'scorePct' => $total > 0 ? (int) round($green / $total * 100) : 0,
            'green' => $green,
            'yellow' => $yellow,
            'red' => $red,
            'total' => $total,
            'criticalMetrics' => $rows->where('status', MetricStatus::Red)->pluck('label')->all(),
        ];
    }

    public function ownerScoreColor(int $scorePct): string
    {
        return match (true) {
            $scorePct >= 70 => 'oklch(0.55 0.15 145)',
            $scorePct >= 40 => 'oklch(0.78 0.15 85)',
            default => 'oklch(0.55 0.2 25)',
        };
    }

    /** Warna bar pencapaian mingguan / kad actual-vs-target. */
    public function achievementBarColor(float $pct, bool $weekly = false): string
    {
        return match (true) {
            $pct >= 100 => 'oklch(0.55 0.15 145)',
            $pct >= 50 => 'oklch(0.78 0.15 85)',
            default => $weekly ? 'oklch(0.6 0.22 350)' : 'oklch(0.55 0.2 25)',
        };
    }

    /** Gradien donut kon — prototaip: conic-gradient(pink 0% n%, track n% 100%). */
    public function donutGradient(?float $pct): string
    {
        $clamped = min(100, max(0, $pct ?? 0));

        return "conic-gradient(oklch(0.6 0.22 350) 0% {$clamped}%, var(--track-bg) {$clamped}% 100%)";
    }
}
