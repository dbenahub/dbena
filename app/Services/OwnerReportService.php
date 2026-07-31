<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MetricStatus;
use App\Enums\ReportPeriod;
use App\Models\CriticalMetric;
use App\Models\CriticalMetricMonth;
use App\Models\CriticalWeeklyEntry;
use App\Models\Owner;
use App\Models\Service;
use Illuminate\Support\Collection;

/**
 * Menjana Laporan Prestasi Pemilik Data (PIC).
 *
 * Untuk setiap PIC ia mengumpul pencapaian merentasi tempoh yang dipilih
 * (mingguan / bulanan / tahunan), kemudian menulis ULASAN naratif dan
 * senarai TINDAKAN yang diperlukan — bukan sekadar nombor mentah.
 *
 * Ulasan dijana secara deterministik daripada data: skor, arah aliran,
 * metrik yang gagal dan ketiadaan pelan tindakan. Tiada AI, tiada rawak —
 * laporan yang sama menghasilkan teks yang sama.
 */
class OwnerReportService
{
    public function __construct(private readonly DashboardMetricsService $metrics) {}

    /**
     * @return array<string, mixed>
     */
    public function build(
        ReportPeriod $period,
        int $year,
        int $month,
        ?int $week = null,
        ?int $serviceId = null,
    ): array {
        $owners = Owner::scorable()->orderBy('name')->get();
        $months = $this->monthsFor($period, $month);

        $rows = $owners
            ->map(fn (Owner $owner) => $this->buildOwnerBlock($owner, $period, $year, $months, $week, $serviceId))
            ->filter(fn (array $block) => $block['total'] > 0)
            ->sortByDesc('scorePct')
            ->values();

        return [
            'period' => $period,
            'year' => $year,
            'month' => $month,
            'week' => $week,
            'months' => $months,
            'serviceId' => $serviceId,
            'service' => $serviceId ? Service::find($serviceId) : null,
            'periodLabel' => $this->periodLabel($period, $year, $month, $week),
            'owners' => $rows,
            'summary' => $this->buildSummary($rows, $period),
            'generatedAt' => now(),
        ];
    }

    /** @return array<int, int> */
    private function monthsFor(ReportPeriod $period, int $month): array
    {
        return $period->isYearly() ? range(1, 12) : [$month];
    }

    /**
     * @param  array<int, int>  $months
     * @return array<string, mixed>
     */
    private function buildOwnerBlock(
        Owner $owner,
        ReportPeriod $period,
        int $year,
        array $months,
        ?int $week,
        ?int $serviceId,
    ): array {
        $assignments = CriticalMetricMonth::query()
            ->where('owner_id', $owner->id)
            ->where('year', $year)
            ->whereIn('month', $months)
            ->with(['criticalMetric.service', 'criticalMetric.targets' => fn ($q) => $q->where('year', $year)])
            ->get();

        // PIC lalai pada metrik yang belum ada rekod bulanan.
        $defaults = CriticalMetric::query()
            ->where('default_owner_id', $owner->id)
            ->when($serviceId, fn ($q) => $q->where('service_id', $serviceId))
            ->with(['service', 'targets' => fn ($q) => $q->where('year', $year)])
            ->get();

        $metricRows = collect();

        foreach ($months as $m) {
            $seen = [];

            foreach ($assignments->where('month', $m) as $assignment) {
                $metric = $assignment->criticalMetric;

                if (! $metric || ($serviceId && $metric->service_id !== $serviceId)) {
                    continue;
                }

                $seen[$metric->id] = true;
                $metricRows->push($this->evaluate($metric, $year, $m, $week, $assignment->action_plan));
            }

            foreach ($defaults as $metric) {
                if (isset($seen[$metric->id])) {
                    continue;
                }

                // Langkau jika metrik ini diberikan kepada PIC lain bulan itu.
                $reassigned = CriticalMetricMonth::where('critical_metric_id', $metric->id)
                    ->where('year', $year)->where('month', $m)
                    ->whereNotNull('owner_id')
                    ->where('owner_id', '!=', $owner->id)
                    ->exists();

                if ($reassigned) {
                    continue;
                }

                $metricRows->push($this->evaluate($metric, $year, $m, $week, null));
            }
        }

        $total = $metricRows->count();
        $green = $metricRows->where('status', MetricStatus::Green)->count();
        $yellow = $metricRows->where('status', MetricStatus::Yellow)->count();
        $red = $metricRows->where('status', MetricStatus::Red)->count();
        $pending = $metricRows->where('status', MetricStatus::BelumUpdate)->count();

        $scorePct = $total > 0 ? (int) round($green / $total * 100) : 0;
        $avgAchievement = $metricRows->whereNotNull('pct')->avg('pct');

        $trend = $this->trend($owner, $period, $year, $months, $serviceId, $scorePct);

        return [
            'owner' => $owner,
            'name' => $owner->name,
            'color' => $owner->color_token,
            'total' => $total,
            'green' => $green,
            'yellow' => $yellow,
            'red' => $red,
            'pending' => $pending,
            'scorePct' => $scorePct,
            'scoreColor' => $this->metrics->ownerScoreColor($scorePct),
            'grade' => $this->grade($scorePct),
            'avgAchievement' => $avgAchievement,
            'trend' => $trend,
            'metrics' => $metricRows->sortBy('pct', SORT_REGULAR)->values(),
            'criticalMetrics' => $metricRows->where('status', MetricStatus::Red)->values(),
            'commentary' => $this->commentary($owner, $scorePct, $total, $green, $yellow, $red, $pending, $trend, $metricRows),
            'actions' => $this->actions($scorePct, $red, $yellow, $pending, $metricRows),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function evaluate(CriticalMetric $metric, int $year, int $month, ?int $week, ?string $actionPlan): array
    {
        $entries = CriticalWeeklyEntry::where('critical_metric_id', $metric->id)
            ->where('year', $year)
            ->where('month', $month)
            ->when($week, fn ($q) => $q->where('week_number', $week))
            ->get();

        $values = $entries->pluck('value')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v)->all();
        $actual = $values === [] ? null : $metric->type->aggregate($values);

        $target = $metric->targetForYear($year);
        $targetValue = $target?->monthly_target !== null ? (float) $target->monthly_target : null;

        // Sasaran mingguan ialah sasaran bulanan ÷ 4.
        if ($week !== null && $targetValue !== null) {
            $targetValue = $this->metrics->calculateWeeklyTarget($targetValue, $metric->value_type);
        }

        $plan = $actionPlan ?? CriticalMetricMonth::where('critical_metric_id', $metric->id)
            ->where('year', $year)->where('month', $month)->value('action_plan');

        $status = $this->metrics->calculateMetricStatus($actual, $targetValue, $plan, $actual !== null);

        $pct = ($actual !== null && $targetValue !== null && $targetValue > 0)
            ? $actual / $targetValue * 100
            : null;

        return [
            'metric' => $metric,
            'label' => $metric->label,
            'service' => $metric->service->name,
            'month' => $month,
            'actual' => $actual,
            'actualLabel' => $actual === null ? '—' : $metric->value_type->format($actual),
            'target' => $targetValue,
            'targetLabel' => $targetValue === null
                ? ($target?->target_text ?? '—')
                : $metric->value_type->format($targetValue),
            'pct' => $pct,
            'gap' => ($actual !== null && $targetValue !== null) ? max(0, $targetValue - $actual) : null,
            'status' => $status,
            'statusColor' => $status->color(),
            'statusLabel' => $status->label(),
            'actionPlan' => $plan,
            'hasPlan' => filled(trim((string) $plan)),
        ];
    }

    /**
     * Bandingkan skor tempoh ini dengan tempoh sebelumnya.
     *
     * @param  array<int, int>  $months
     * @return array<string, mixed>
     */
    private function trend(Owner $owner, ReportPeriod $period, int $year, array $months, ?int $serviceId, int $currentScore): array
    {
        $previousMonth = min($months) - 1;
        $previousYear = $year;

        if ($previousMonth < 1) {
            $previousMonth = 12;
            $previousYear--;
        }

        if ($period->isYearly()) {
            $previousYear = $year - 1;
            $previousMonths = range(1, 12);
        } else {
            $previousMonths = [$previousMonth];
        }

        $rows = collect();

        foreach ($previousMonths as $m) {
            $metricIds = CriticalMetricMonth::where('owner_id', $owner->id)
                ->where('year', $previousYear)->where('month', $m)
                ->pluck('critical_metric_id');

            foreach (CriticalMetric::whereIn('id', $metricIds)
                ->when($serviceId, fn ($q) => $q->where('service_id', $serviceId))
                ->with(['service', 'targets' => fn ($q) => $q->where('year', $previousYear)])
                ->get() as $metric) {
                $rows->push($this->evaluate($metric, $previousYear, $m, null, null));
            }
        }

        if ($rows->isEmpty()) {
            return ['available' => false, 'delta' => 0, 'previousScore' => null, 'direction' => 'flat'];
        }

        $previousScore = (int) round($rows->where('status', MetricStatus::Green)->count() / $rows->count() * 100);
        $delta = $currentScore - $previousScore;

        return [
            'available' => true,
            'previousScore' => $previousScore,
            'delta' => $delta,
            'direction' => $delta > 4 ? 'up' : ($delta < -4 ? 'down' : 'flat'),
        ];
    }

    private function grade(int $scorePct): string
    {
        return match (true) {
            $scorePct >= 85 => 'A',
            $scorePct >= 70 => 'B',
            $scorePct >= 55 => 'C',
            $scorePct >= 40 => 'D',
            default => 'E',
        };
    }

    /**
     * Ulasan naratif terperinci — dibina daripada fakta dalam data.
     *
     * @return array<int, string>
     */
    private function commentary(
        Owner $owner,
        int $scorePct,
        int $total,
        int $green,
        int $yellow,
        int $red,
        int $pending,
        array $trend,
        Collection $metricRows,
    ): array {
        $lines = [];

        // 1. Penilaian keseluruhan
        $lines[] = __('owner_report.commentary.overall', [
            'name' => $owner->name,
            'score' => $scorePct,
            'grade' => $this->grade($scorePct),
            'green' => $green,
            'total' => $total,
            'verdict' => __('owner_report.verdict.'.match (true) {
                $scorePct >= 85 => 'excellent',
                $scorePct >= 70 => 'good',
                $scorePct >= 55 => 'fair',
                $scorePct >= 40 => 'weak',
                default => 'critical',
            }),
        ]);

        // 2. Arah aliran berbanding tempoh sebelum
        if ($trend['available']) {
            $lines[] = __('owner_report.commentary.trend_'.$trend['direction'], [
                'delta' => abs($trend['delta']),
                'previous' => $trend['previousScore'],
            ]);
        } else {
            $lines[] = __('owner_report.commentary.trend_none');
        }

        // 3. Metrik paling lemah — dinamakan secara spesifik
        $worst = $metricRows->whereNotNull('pct')->sortBy('pct')->take(3);

        if ($worst->isNotEmpty()) {
            $lines[] = __('owner_report.commentary.worst', [
                'list' => $worst->map(fn (array $r) => sprintf(
                    '%s (%s — %s)',
                    $r['label'],
                    number_format($r['pct'], 1).'%',
                    $r['service']
                ))->implode('; '),
            ]);
        }

        // 4. Metrik terbaik
        $best = $metricRows->whereNotNull('pct')->sortByDesc('pct')->take(2);

        if ($best->isNotEmpty() && $best->first()['pct'] >= 100) {
            $lines[] = __('owner_report.commentary.best', [
                'list' => $best->where('pct', '>=', 100)
                    ->map(fn (array $r) => $r['label'].' ('.number_format($r['pct'], 1).'%)')
                    ->implode('; '),
            ]);
        }

        // 5. Jurang disiplin — Red bermakna tiada pelan tindakan langsung
        if ($red > 0) {
            $lines[] = __('owner_report.commentary.no_plan', [
                'count' => $red,
                'list' => $metricRows->where('status', MetricStatus::Red)
                    ->take(4)->pluck('label')->implode('; '),
            ]);
        }

        if ($yellow > 0) {
            $lines[] = __('owner_report.commentary.has_plan', ['count' => $yellow]);
        }

        // 6. Data belum dikemas kini
        if ($pending > 0) {
            $lines[] = __('owner_report.commentary.pending', [
                'count' => $pending,
                'total' => $total,
            ]);
        }

        // 7. Jumlah jurang dalam RM bagi metrik kewangan
        $financialGap = $metricRows
            ->filter(fn (array $r) => $r['gap'] !== null && $r['metric']->isCurrency())
            ->sum('gap');

        if ($financialGap > 0) {
            $lines[] = __('owner_report.commentary.financial_gap', [
                'amount' => $this->metrics->formatRm($financialGap),
            ]);
        }

        return $lines;
    }

    /**
     * Tindakan yang diperlukan — spesifik, boleh dilaksanakan, berkeutamaan.
     *
     * @return array<int, array<string, string>>
     */
    private function actions(int $scorePct, int $red, int $yellow, int $pending, Collection $metricRows): array
    {
        $actions = [];

        // Keutamaan 1 — metrik tanpa pelan tindakan
        foreach ($metricRows->where('status', MetricStatus::Red)->take(5) as $row) {
            $actions[] = [
                'priority' => 'high',
                'label' => __('owner_report.action.write_plan', ['metric' => $row['label']]),
                'detail' => __('owner_report.action.write_plan_detail', [
                    'actual' => $row['actualLabel'],
                    'target' => $row['targetLabel'],
                    'service' => $row['service'],
                ]),
            ];
        }

        // Keutamaan 2 — data tidak dikemas kini
        if ($pending > 0) {
            $actions[] = [
                'priority' => 'high',
                'label' => __('owner_report.action.update_data'),
                'detail' => __('owner_report.action.update_data_detail', ['count' => $pending]),
            ];
        }

        // Keutamaan 3 — metrik dengan pelan tetapi masih di bawah sasaran
        $stalled = $metricRows->where('status', MetricStatus::Yellow)
            ->filter(fn (array $r) => $r['pct'] !== null && $r['pct'] < 50);

        foreach ($stalled->take(3) as $row) {
            $actions[] = [
                'priority' => 'medium',
                'label' => __('owner_report.action.revise_plan', ['metric' => $row['label']]),
                'detail' => __('owner_report.action.revise_plan_detail', [
                    'pct' => number_format($row['pct'], 1),
                ]),
            ];
        }

        // Keutamaan 4 — cadangan struktural mengikut skor
        if ($scorePct < 40) {
            $actions[] = [
                'priority' => 'high',
                'label' => __('owner_report.action.escalate'),
                'detail' => __('owner_report.action.escalate_detail'),
            ];
        } elseif ($scorePct < 70) {
            $actions[] = [
                'priority' => 'medium',
                'label' => __('owner_report.action.weekly_review'),
                'detail' => __('owner_report.action.weekly_review_detail'),
            ];
        } else {
            $actions[] = [
                'priority' => 'low',
                'label' => __('owner_report.action.maintain'),
                'detail' => __('owner_report.action.maintain_detail'),
            ];
        }

        return $actions;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function buildSummary(Collection $rows, ReportPeriod $period): array
    {
        $totalMetrics = $rows->sum('total');
        $totalGreen = $rows->sum('green');
        $totalRed = $rows->sum('red');
        $totalPending = $rows->sum('pending');

        $teamScore = $totalMetrics > 0 ? (int) round($totalGreen / $totalMetrics * 100) : 0;

        $needsAttention = $rows->filter(fn (array $r) => $r['scorePct'] < 55 || $r['red'] > 0);

        return [
            'ownerCount' => $rows->count(),
            'totalMetrics' => $totalMetrics,
            'totalGreen' => $totalGreen,
            'totalRed' => $totalRed,
            'totalPending' => $totalPending,
            'teamScore' => $teamScore,
            'teamScoreColor' => $this->metrics->ownerScoreColor($teamScore),
            'topPerformer' => $rows->first(),
            'needsAttention' => $needsAttention->values(),
            'headline' => __('owner_report.summary.headline', [
                'score' => $teamScore,
                'owners' => $rows->count(),
                'metrics' => $totalMetrics,
                'period' => $period->label(),
            ]),
        ];
    }

    private function periodLabel(ReportPeriod $period, int $year, int $month, ?int $week): string
    {
        $monthName = __('calendar.months_full')[$month - 1] ?? '';

        return match ($period) {
            ReportPeriod::Weekly => __('owner_report.label.weekly', [
                'week' => $week ?? 1, 'month' => $monthName, 'year' => $year,
            ]),
            ReportPeriod::Monthly => "{$monthName} {$year}",
            ReportPeriod::Yearly => (string) $year,
        };
    }
}
