<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MetricStatus;
use Illuminate\Support\Collection;

/**
 * Analisis punca kegagalan metrik mengikut corong jualan DBENA.
 *
 * Melaporkan "Quotation gagal capai sasaran" sahaja tidak membantu — PIC sudah
 * tahu. Yang mereka perlu tahu ialah MENGAPA, dan apa yang perlu dibuat.
 *
 * Corongnya:
 *
 *     Ads Spend
 *        ↓
 *     No of Lead
 *        ↓
 *     Site Visit / Appointment
 *        ↓
 *     No of New Quotation ←→ Amount Quotation Release
 *        ↓
 *     Revenue / Sales
 *        ↓
 *     Sales Collection
 *
 * Apabila satu metrik gagal, enjin ini memeriksa pemacu di HULU-nya. Kalau
 * pemacu itu turut gagal, itulah punca sebenar — dan membaiki metrik hilir
 * secara langsung adalah sia-sia. Kalau pemacu sihat tetapi metrik ini gagal,
 * masalahnya penukaran, bukan jumlah aktiviti.
 *
 * Ia juga melaporkan kesan HILIR: quotation yang lemah menyeret revenue, yang
 * seterusnya menyeret kutipan — jadi kegagalan seorang PIC menjadi masalah
 * syarikat, bukan sekadar angka peribadi.
 */
class FunnelAnalysisService
{
    /**
     * Pemacu hulu bagi setiap metrik.
     *
     * @var array<string, array<int, string>>
     */
    private const DRIVERS = [
        'no_of_lead' => ['ads_spend'],
        'no_of_site_visit' => ['no_of_lead'],
        'no_of_appointment' => ['no_of_lead'],
        'no_of_new_quotation' => ['no_of_site_visit', 'no_of_appointment', 'no_of_lead'],
        'amount_quotation_release' => ['no_of_new_quotation'],
        'revenue_sales' => ['amount_quotation_release', 'no_of_new_quotation'],
        'sales_collection_new' => ['revenue_sales'],
        'sales_collection_progress' => ['revenue_sales'],
    ];

    /** Metrik kecekapan — nilai RENDAH lebih baik, jadi dinilai terbalik. */
    private const EFFICIENCY_METRICS = ['cost_per_lead', 'cost_per_appointment', 'cost_per_quotation'];

    /** Ambang: di bawah ini, pemacu dianggap gagal dan menjadi punca. */
    private const DRIVER_FAIL_THRESHOLD = 60.0;

    /** Ambang: di atas ini, pemacu dianggap sihat — masalahnya penukaran. */
    private const DRIVER_HEALTHY_THRESHOLD = 80.0;

    public function __construct(private readonly DashboardMetricsService $metrics) {}

    /**
     * Diagnos satu metrik yang gagal.
     *
     * @param  Collection<int, array<string, mixed>>  $allRows  semua baris servis
     * @return array<string, mixed>|null null jika metrik ini tidak bermasalah
     */
    public function diagnose(array $row, Collection $allRows): ?array
    {
        if (! in_array($row['status'], [MetricStatus::Red, MetricStatus::Yellow], true)) {
            return null;
        }

        $key = $row['metricKey'];
        $byKey = $allRows->keyBy('metricKey');

        $causes = $this->findCauses($key, $row, $byKey);
        $impacts = $this->findImpacts($key, $byKey);
        $required = $this->requiredActivity($key, $row, $byKey);

        return [
            'metricKey' => $key,
            'label' => $row['label'],
            'pct' => $row['pct'],
            'actualLabel' => $row['actualLabel'],
            'targetLabel' => $row['targetLabel'],
            'gapLabel' => $this->gapLabel($row),
            'causes' => $causes,
            'impacts' => $impacts,
            'required' => $required,
            'severity' => $row['status'] === MetricStatus::Red ? 'critical' : 'warning',
            'narrative' => $this->narrative($row, $causes, $impacts),
            'actions' => $this->actions($key, $row, $causes, $required),
        ];
    }

    /**
     * Diagnos semua metrik bermasalah bagi satu pemilik.
     *
     * @param  Collection<int, array<string, mixed>>  $ownerRows
     * @param  Collection<int, array<string, mixed>>  $allRows
     * @return Collection<int, array<string, mixed>>
     */
    public function diagnoseOwner(Collection $ownerRows, Collection $allRows): Collection
    {
        return $ownerRows
            ->map(fn (array $row) => $this->diagnose($row, $allRows))
            ->filter()
            // Utamakan yang paling teruk, dan yang paling tinggi dalam corong.
            ->sortBy(fn (array $d) => [$d['severity'] === 'critical' ? 0 : 1, $d['pct'] ?? 999])
            ->values();
    }

    // ══ Punca ═════════════════════════════════════════════════════════════

    /**
     * @param  Collection<string, array<string, mixed>>  $byKey
     * @return array<int, array<string, mixed>>
     */
    private function findCauses(string $key, array $row, Collection $byKey): array
    {
        $drivers = self::DRIVERS[$key] ?? [];
        $causes = [];
        $anyDriverPresent = false;

        foreach ($drivers as $driverKey) {
            $driver = $byKey->get($driverKey);

            if (! $driver) {
                continue;
            }

            $anyDriverPresent = true;

            // Pemacu tiada data langsung
            if ($driver['actual'] === null) {
                $causes[] = [
                    'type' => 'driver_no_data',
                    'metricKey' => $driverKey,
                    'label' => $driver['label'],
                    'pct' => null,
                ];

                continue;
            }

            $driverPct = $driver['pct'];

            // Pemacu ada nilai tetapi sifar — aktiviti langsung tiada
            if ((float) $driver['actual'] === 0.0) {
                $causes[] = [
                    'type' => 'driver_zero',
                    'metricKey' => $driverKey,
                    'label' => $driver['label'],
                    'pct' => 0.0,
                ];

                continue;
            }

            if ($driverPct !== null && $driverPct < self::DRIVER_FAIL_THRESHOLD) {
                $causes[] = [
                    'type' => 'driver_failed',
                    'metricKey' => $driverKey,
                    'label' => $driver['label'],
                    'pct' => $driverPct,
                    'actualLabel' => $driver['actualLabel'],
                    'targetLabel' => $driver['targetLabel'],
                ];

                continue;
            }

            if ($driverPct !== null && $driverPct >= self::DRIVER_HEALTHY_THRESHOLD) {
                $causes[] = [
                    'type' => 'conversion',
                    'metricKey' => $driverKey,
                    'label' => $driver['label'],
                    'pct' => $driverPct,
                ];
            }
        }

        // Tiada pemacu langsung dalam corong — metrik puncak atau kecekapan
        if (! $anyDriverPresent && $causes === []) {
            $causes[] = [
                'type' => in_array($key, self::EFFICIENCY_METRICS, true) ? 'efficiency' : 'top_of_funnel',
                'metricKey' => $key,
                'label' => $row['label'],
                'pct' => $row['pct'],
            ];
        }

        // Tiada pelan tindakan langsung — jurang disiplin, bukan prestasi
        if (! filled(trim((string) ($row['actionPlan'] ?? '')))) {
            $causes[] = ['type' => 'no_action_plan', 'metricKey' => $key, 'label' => $row['label'], 'pct' => null];
        }

        return $causes;
    }

    // ══ Kesan hilir ═══════════════════════════════════════════════════════

    /**
     * Metrik hilir yang turut terjejas oleh kegagalan ini.
     *
     * @param  Collection<string, array<string, mixed>>  $byKey
     * @return array<int, array<string, mixed>>
     */
    private function findImpacts(string $key, Collection $byKey): array
    {
        $impacts = [];
        $queue = [$key];
        $seen = [$key => true];

        while ($queue !== []) {
            $current = array_shift($queue);

            foreach (self::DRIVERS as $downstream => $drivers) {
                if (! in_array($current, $drivers, true) || isset($seen[$downstream])) {
                    continue;
                }

                $seen[$downstream] = true;
                $row = $byKey->get($downstream);

                if (! $row) {
                    continue;
                }

                $queue[] = $downstream;

                // Hanya laporkan yang benar-benar terjejas
                if (in_array($row['status'], [MetricStatus::Red, MetricStatus::Yellow], true)) {
                    $impacts[] = [
                        'metricKey' => $downstream,
                        'label' => $row['label'],
                        'pct' => $row['pct'],
                        'actualLabel' => $row['actualLabel'],
                        'targetLabel' => $row['targetLabel'],
                    ];
                }
            }
        }

        return $impacts;
    }

    // ══ Aktiviti diperlukan ═══════════════════════════════════════════════

    /**
     * Berapa banyak aktiviti hulu diperlukan untuk menutup jurang.
     *
     * Menggunakan kadar penukaran SEBENAR PIC bila ada data; jika tidak,
     * menggunakan nisbah sasaran sebagai anggaran.
     *
     * @param  Collection<string, array<string, mixed>>  $byKey
     * @return array<int, array<string, mixed>>
     */
    private function requiredActivity(string $key, array $row, Collection $byKey): array
    {
        if ($row['target'] === null || $row['actual'] === null) {
            return [];
        }

        $gap = max(0, (float) $row['target'] - (float) $row['actual']);

        if ($gap <= 0) {
            return [];
        }

        $required = [];

        foreach (self::DRIVERS[$key] ?? [] as $driverKey) {
            $driver = $byKey->get($driverKey);

            if (! $driver || $driver['target'] === null || (float) $driver['target'] <= 0) {
                continue;
            }

            // Kadar penukaran sebenar bila kedua-dua nilai ada dan bermakna
            $rate = null;

            if ($driver['actual'] !== null && (float) $driver['actual'] > 0 && (float) $row['actual'] > 0) {
                $rate = (float) $row['actual'] / (float) $driver['actual'];
            }

            // Jika tidak, gunakan nisbah sasaran sebagai anggaran perancangan
            if ($rate === null || $rate <= 0) {
                $rate = (float) $row['target'] / (float) $driver['target'];
            }

            if ($rate <= 0) {
                continue;
            }

            $needed = (int) ceil($gap / $rate);
            $have = (int) round((float) ($driver['actual'] ?? 0));

            $required[] = [
                'metricKey' => $driverKey,
                'label' => $driver['label'],
                'needed' => $needed,
                'have' => $have,
                'perWeek' => (int) ceil($needed / 4),
                'rate' => $rate,
                'isActual' => $driver['actual'] !== null && (float) $driver['actual'] > 0 && (float) $row['actual'] > 0,
            ];

            // Satu cadangan hulu sudah memadai — yang paling langsung.
            break;
        }

        return $required;
    }

    // ══ Naratif ═══════════════════════════════════════════════════════════

    /**
     * @param  array<int, array<string, mixed>>  $causes
     * @param  array<int, array<string, mixed>>  $impacts
     */
    private function narrative(array $row, array $causes, array $impacts): string
    {
        $parts = [];

        $parts[] = __('funnel.headline', [
            'metric' => $row['label'],
            'actual' => $row['actualLabel'],
            'target' => $row['targetLabel'],
            'pct' => $row['pct'] !== null ? number_format($row['pct'], 1) : '—',
        ]);

        $upstream = collect($causes)->whereIn('type', ['driver_failed', 'driver_zero', 'driver_no_data']);

        if ($upstream->isNotEmpty()) {
            $parts[] = __('funnel.because_upstream', [
                'drivers' => $upstream->map(function (array $c): string {
                    return match ($c['type']) {
                        'driver_zero' => __('funnel.driver_zero_inline', ['metric' => $c['label']]),
                        'driver_no_data' => __('funnel.driver_no_data_inline', ['metric' => $c['label']]),
                        default => __('funnel.driver_failed_inline', [
                            'metric' => $c['label'],
                            'pct' => number_format((float) $c['pct'], 1),
                        ]),
                    };
                })->implode('; '),
            ]);
        } elseif (collect($causes)->contains('type', 'conversion')) {
            $driver = collect($causes)->firstWhere('type', 'conversion');
            $parts[] = __('funnel.because_conversion', [
                'driver' => $driver['label'],
                'pct' => number_format((float) $driver['pct'], 1),
            ]);
        }

        if ($impacts !== []) {
            $parts[] = __('funnel.impact', [
                'downstream' => collect($impacts)->pluck('label')->implode(', '),
            ]);
        }

        if (collect($causes)->contains('type', 'no_action_plan')) {
            $parts[] = __('funnel.no_plan');
        }

        return implode(' ', $parts);
    }

    // ══ Tindakan ══════════════════════════════════════════════════════════

    /**
     * @param  array<int, array<string, mixed>>  $causes
     * @param  array<int, array<string, mixed>>  $required
     * @return array<int, array<string, string>>
     */
    private function actions(string $key, array $row, array $causes, array $required): array
    {
        $actions = [];

        // 1. Aktiviti hulu berkuantiti — tindakan paling konkrit
        foreach ($required as $need) {
            $actions[] = [
                'priority' => 'high',
                'label' => __('funnel.action.raise_upstream', [
                    'driver' => $need['label'],
                    'count' => number_format($need['needed']),
                ]),
                'detail' => __('funnel.action.raise_upstream_detail', [
                    'perWeek' => number_format($need['perWeek']),
                    'have' => number_format($need['have']),
                    'basis' => $need['isActual']
                        ? __('funnel.basis_actual', ['rate' => number_format($need['rate'] * 100, 1)])
                        : __('funnel.basis_target'),
                ]),
            ];
        }

        // 2. Pemacu bernilai sifar — aktiviti langsung tiada
        foreach (collect($causes)->where('type', 'driver_zero') as $cause) {
            $actions[] = [
                'priority' => 'high',
                'label' => __('funnel.action.start_activity', ['driver' => $cause['label']]),
                'detail' => __('funnel.action.start_activity_detail', ['driver' => $cause['label']]),
            ];
        }

        // 3. Data hulu tiada — tidak boleh diurus tanpa diukur
        foreach (collect($causes)->where('type', 'driver_no_data') as $cause) {
            $actions[] = [
                'priority' => 'high',
                'label' => __('funnel.action.record_data', ['driver' => $cause['label']]),
                'detail' => __('funnel.action.record_data_detail'),
            ];
        }

        // 4. Aktiviti mencukupi tetapi penukaran lemah
        if (collect($causes)->contains('type', 'conversion')) {
            $driver = collect($causes)->firstWhere('type', 'conversion');
            $actions[] = [
                'priority' => 'medium',
                'label' => __('funnel.action.fix_conversion', ['metric' => $row['label']]),
                'detail' => __('funnel.action.fix_conversion_detail', ['driver' => $driver['label']]),
            ];
        }

        // 5. Metrik kecekapan
        if (collect($causes)->contains('type', 'efficiency')) {
            $actions[] = [
                'priority' => 'medium',
                'label' => __('funnel.action.improve_efficiency', ['metric' => $row['label']]),
                'detail' => __('funnel.action.improve_efficiency_detail'),
            ];
        }

        // 6. Tiada pelan tindakan direkodkan
        if (collect($causes)->contains('type', 'no_action_plan')) {
            $actions[] = [
                'priority' => 'high',
                'label' => __('funnel.action.write_plan', ['metric' => $row['label']]),
                'detail' => __('funnel.action.write_plan_detail'),
            ];
        }

        return $actions;
    }

    private function gapLabel(array $row): string
    {
        if ($row['target'] === null || $row['actual'] === null) {
            return '—';
        }

        $gap = max(0, (float) $row['target'] - (float) $row['actual']);

        return $row['valueType']->format($gap);
    }
}
