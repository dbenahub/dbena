<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MetricStatus;
use App\Models\CriticalMetric;
use App\Models\Service;
use Illuminate\Support\Collection;

/**
 * Membina baris jadual Data Kritikal Mingguan untuk satu servis/bulan/tahun.
 *
 * Menggantikan blok `criticalRows = ...` dalam renderVals() prototaip,
 * tetapi mengambil data dari MySQL dan bukan array hardcoded.
 */
class CriticalDataService
{
    public function __construct(private readonly DashboardMetricsService $metrics) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function rowsFor(Service $service, int $year, int $month): Collection
    {
        $metricModels = $service->criticalMetrics()
            ->with([
                'defaultOwner',
                'targets' => fn ($q) => $q->where('year', $year),
                'weeklyEntries' => fn ($q) => $q->where('year', $year)->where('month', $month),
                'months' => fn ($q) => $q->where('year', $year)->where('month', $month)->with('owner'),
            ])
            ->orderBy('sort_order')
            ->get();

        return $metricModels->map(fn (CriticalMetric $metric) => $this->buildRow($metric, $year, $month));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRow(CriticalMetric $metric, int $year, int $month): array
    {
        $target = $metric->targets->first();
        $monthMeta = $metric->months->first();

        $weekValues = [];
        for ($week = 1; $week <= 4; $week++) {
            $entry = $metric->weeklyEntries->firstWhere('week_number', $week);
            $weekValues[$week] = $entry?->value !== null ? (float) $entry->value : null;
        }

        $hasAnyInput = collect($weekValues)->contains(fn (?float $v) => $v !== null);
        $actual = $hasAnyInput ? $metric->type->aggregate(array_values($weekValues)) : null;

        $targetValue = $target?->monthly_target !== null ? (float) $target->monthly_target : null;
        $owner = $monthMeta?->owner ?? $metric->defaultOwner;
        $actionPlan = $monthMeta?->action_plan;

        $status = $this->metrics->calculateMetricStatus($actual, $targetValue, $actionPlan, $hasAnyInput);

        $pct = ($actual !== null && $targetValue !== null && $targetValue > 0)
            ? $actual / $targetValue * 100
            : null;

        return [
            'metric' => $metric,
            'id' => $metric->id,
            'label' => $metric->label,
            'metricKey' => $metric->metric_key,
            'type' => $metric->type,
            'valueType' => $metric->value_type,
            'weeks' => $weekValues,
            'actual' => $actual,
            'actualLabel' => $actual === null ? '—' : $metric->value_type->format($actual),
            'target' => $targetValue,
            'targetLabel' => $targetValue === null
                ? ($target?->target_text ?? '—')
                : $metric->value_type->format($targetValue),
            'targetIsNumeric' => $targetValue !== null,
            'pct' => $pct,
            'status' => $status,
            'statusColor' => $status->color(),
            'statusLabel' => $status->label(),
            'owner' => $owner,
            'ownerId' => $owner?->id,
            'ownerName' => $owner?->name ?? '—',
            'ownerColor' => $owner?->color_token ?? 'var(--t60)',
            'actionPlan' => $actionPlan ?? '',
        ];
    }

    /**
     * Prestasi pemilik data — dikumpulkan daripada baris jadual.
     * PIC sistem (INFO) dikecualikan (PEMBETULAN isu #21).
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    public function ownerPerformance(Collection $rows): Collection
    {
        return $rows
            ->filter(fn (array $r) => $r['owner'] !== null && ! $r['owner']->is_system)
            ->groupBy(fn (array $r) => $r['owner']->id)
            ->map(function (Collection $ownerRows) {
                $owner = $ownerRows->first()['owner'];

                $score = $this->metrics->calculateOwnerScore(
                    $ownerRows->map(fn (array $r) => ['status' => $r['status'], 'label' => $r['label']])
                );

                return [
                    'owner' => $owner,
                    'name' => $owner->name,
                    'color' => $owner->color_token,
                    'scorePct' => $score['scorePct'],
                    'greenCount' => $score['green'],
                    'yellowCount' => $score['yellow'],
                    'redCount' => $score['red'],
                    'total' => $score['total'],
                    'criticalMetrics' => $score['criticalMetrics'],
                    'hasCritical' => $score['red'] > 0,
                    'barColor' => $this->metrics->ownerScoreColor($score['scorePct']),
                ];
            })
            ->values();
    }

    /** Bilangan metrik berstatus Red bagi satu servis — untuk notifikasi. */
    public function redCountFor(Service $service, int $year, int $month): int
    {
        return $this->rowsFor($service, $year, $month)
            ->filter(fn (array $r) => $r['status'] === MetricStatus::Red)
            ->count();
    }
}
