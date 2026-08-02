<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MetricStatus;
use App\Enums\ReportPeriod;
use App\Enums\RoadmapStatus;
use App\Models\RoadmapCell;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Laporan prestasi menyeluruh.
 *
 * Laporan yang menyenaraikan nombor tanpa memberitahu maksudnya
 * memindahkan kerja analisis kepada pembaca — dan pembaca ialah orang
 * yang meminta laporan itu kerana dia tiada masa untuk analisis. Jadi
 * setiap seksyen di sini menjawab satu soalan mengikut urutan yang
 * seseorang benar-benar bertanya:
 *
 *   1. Berapa banyak? (ringkasan eksekutif)
 *   2. Lebih baik atau lebih teruk daripada sebelumnya? (perbandingan)
 *   3. Ke arah mana ia bergerak? (trend)
 *   4. Siapa? (pecahan servis dan pemilik)
 *   5. KENAPA? (analisis corong dan punca)
 *   6. Jadi apa saya patut buat? (cadangan dan tindakan segera)
 *
 * Seksyen 5 dan 6 ialah sebab laporan ini wujud. Empat yang pertama sudah
 * ada pada dashboard.
 */
class ComprehensiveReportService
{
    /** Bawah paras ini, prestasi dianggap kritikal. */
    private const CRITICAL_PCT = 60.0;

    private const WARN_PCT = 90.0;

    public function __construct(
        private readonly DashboardMetricsService $metrics,
        private readonly CriticalDataService $critical,
        private readonly SalesJourneyService $journey,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(
        ReportPeriod $period,
        int $year,
        int $month,
        ?int $week = null,
        ?string $serviceKey = null,
    ): array {
        $selected = $serviceKey ? Service::where('key', $serviceKey)->first() : null;
        $services = $selected ? collect([$selected]) : Service::orderBy('sort_order')->get();

        $scope = $this->scope($period, $year, $month, $week);

        $breakdown = $services
            ->map(fn (Service $s) => $this->serviceRow($s, $scope, $year, $month))
            ->values();

        $totalActual = (float) $breakdown->sum('actual');
        $totalTarget = (float) $breakdown->sum('target');

        $previous = $this->previousPeriod($period, $year, $month, $services);

        return [
            'period' => $period,
            'year' => $year,
            'month' => $month,
            'week' => $week,
            'scope' => $scope,
            'service' => $selected,
            'services' => $services,

            'generatedAt' => now(),
            'generatedBy' => auth()->user()?->name ?? '—',

            'summary' => $this->summary($totalActual, $totalTarget, $previous),
            'previous' => $previous,
            'trend' => $this->trend($year, $services),
            'breakdown' => $breakdown,
            'funnel' => $this->funnel($services, $year, $month),
            'causes' => $this->causes($services, $year, $month),
            'owners' => $this->owners($services, $year, $month),
            'actions' => $this->actions($services, $year, $month, $breakdown),

            /*
             * Satu bahagian penuh setiap servis.
             *
             * Ringkasan syarikat menjawab "adakah kita pada landasan";
             * ia tidak boleh menjawab "kenapa Kabinet tersasar sedangkan
             * Renovation tidak". Menggabungkan lima servis ke dalam satu
             * ulasan menghasilkan ayat yang benar untuk purata dan salah
             * untuk setiap servis secara individu.
             */
            'perService' => $breakdown
                ->map(fn (array $row) => $this->serviceSection($row, $year, $month))
                ->values(),

            'roadmap' => $this->roadmapSummary($services, $year, $month),
        ];
    }

    /**
     * Berapa banyak bulan dan pengganda yang dirangkumi tempoh ini.
     *
     * @return array<string, mixed>
     */
    private function scope(ReportPeriod $period, int $year, int $month, ?int $week): array
    {
        $tarikh = Carbon::create($year, $month, 1);

        return match ($period) {
            ReportPeriod::Weekly => [
                'label' => __('report.scope.weekly', [
                    'week' => $week ?? 1,
                    'month' => $tarikh->translatedFormat('F Y'),
                ]),
                'months' => [$month],
                // Sasaran bulanan dibahagi empat untuk tempoh mingguan.
                // Membandingkan jualan seminggu dengan sasaran sebulan
                // menghasilkan 25% yang kelihatan seperti kegagalan pada
                // minggu yang berjalan tepat mengikut rancangan.
                'divisor' => 4.0,
            ],
            ReportPeriod::Monthly => [
                'label' => $tarikh->translatedFormat('F Y'),
                'months' => [$month],
                'divisor' => 1.0,
            ],
            ReportPeriod::Yearly => [
                'label' => (string) $year,
                'months' => range(1, 12),
                'divisor' => 1.0,
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $scope
     * @return array<string, mixed>
     */
    private function serviceRow(Service $service, array $scope, int $year, int $month): array
    {
        $faktor = $this->metrics->yearFactor($year);
        $actual = 0.0;

        foreach ($scope['months'] as $m) {
            $actual += $this->metrics->sumMetricActual(['revenue_sales'], $year, $m, $service->id);
        }

        $actual *= $faktor;

        $target = (float) $service->monthly_target * $faktor
            * count($scope['months']) / $scope['divisor'];

        $pct = $target > 0 ? $actual / $target * 100 : 0.0;

        return [
            'service' => $service,
            'actual' => $actual,
            'target' => $target,
            'pct' => $pct,
            'gap' => max(0.0, $target - $actual),
            'status' => $this->verdict($pct),
        ];
    }

    /**
     * Prestasi tempoh SEBELUMNYA, untuk perbandingan.
     *
     * Nombor tanpa titik rujukan tidak bermakna. RM450,000 ialah berita
     * baik selepas RM300,000 dan berita buruk selepas RM600,000, dan
     * laporan yang tidak menyatakan yang mana meninggalkan pembaca
     * membuat kesimpulan yang salah dengan yakin.
     *
     * @param  Collection<int, Service>  $services
     * @return array<string, mixed>
     */
    private function previousPeriod(ReportPeriod $period, int $year, int $month, Collection $services): array
    {
        $lepas = $period->isYearly()
            ? Carbon::create($year - 1, 1, 1)
            : Carbon::create($year, $month, 1)->subMonth();

        $bulan = $period->isYearly() ? range(1, 12) : [(int) $lepas->month];
        $faktor = $this->metrics->yearFactor((int) $lepas->year);

        $actual = 0.0;

        foreach ($services as $service) {
            foreach ($bulan as $m) {
                $actual += $this->metrics->sumMetricActual(['revenue_sales'], (int) $lepas->year, $m, $service->id);
            }
        }

        return [
            'label' => $period->isYearly()
                ? (string) $lepas->year
                : $lepas->translatedFormat('F Y'),
            'actual' => $actual * $faktor,
        ];
    }

    /**
     * @param  array<string, mixed>  $previous
     * @return array<string, mixed>
     */
    private function summary(float $actual, float $target, array $previous): array
    {
        $pct = $target > 0 ? $actual / $target * 100 : 0.0;
        $lepas = (float) $previous['actual'];

        return [
            'actual' => $actual,
            'target' => $target,
            'pct' => $pct,
            'gap' => max(0.0, $target - $actual),
            'status' => $this->verdict($pct),
            'change' => $lepas > 0 ? ($actual - $lepas) / $lepas * 100 : null,
            'changeAmount' => $actual - $lepas,
        ];
    }

    /**
     * Siri 12 bulan untuk carta trend.
     *
     * @param  Collection<int, Service>  $services
     * @return array<string, mixed>
     */
    private function trend(int $year, Collection $services): array
    {
        $faktor = $this->metrics->yearFactor($year);
        $sasaranBulanan = (float) $services->sum('monthly_target') * $faktor;

        $siri = [];

        foreach (range(1, 12) as $m) {
            $actual = 0.0;

            foreach ($services as $service) {
                $actual += $this->metrics->sumMetricActual(['revenue_sales'], $year, $m, $service->id);
            }

            $siri[] = [
                'month' => $m,
                'label' => __('calendar.months_short')[$m - 1],
                'actual' => $actual * $faktor,
                'target' => $sasaranBulanan,
            ];
        }

        // Skala carta dikira daripada nilai TERBESAR, termasuk sasaran.
        // Menskala hanya kepada jualan sebenar bermakna bar sasaran keluar
        // dari carta pada bulan yang paling teruk.
        $puncak = max(
            1.0,
            max(array_column($siri, 'actual')),
            $sasaranBulanan,
        );

        return ['series' => $siri, 'peak' => $puncak];
    }

    /**
     * Corong jualan, digabungkan merentas servis dalam skop.
     *
     * @param  Collection<int, Service>  $services
     * @return array<string, mixed>
     */
    private function funnel(Collection $services, int $year, int $month): array
    {
        $peringkat = [];

        foreach ($services as $service) {
            $rows = $this->critical->rowsFor($service, $year, $month);

            if ($rows->isEmpty()) {
                continue;
            }

            foreach ($this->journey->build($rows)['stages'] as $stage) {
                $kunci = $stage['key'];

                $peringkat[$kunci] ??= [
                    'key' => $kunci,
                    'title' => $stage['title'],
                    'actual' => 0.0,
                    'target' => 0.0,
                ];

                $peringkat[$kunci]['actual'] += (float) ($stage['actual'] ?? 0);
                $peringkat[$kunci]['target'] += (float) ($stage['target'] ?? 0);
            }
        }

        return collect($peringkat)
            ->map(function (array $s): array {
                $s['pct'] = $s['target'] > 0 ? $s['actual'] / $s['target'] * 100 : 0.0;
                $s['status'] = $this->verdict($s['pct']);

                return $s;
            })
            ->values()
            ->all();
    }

    /**
     * Punca — peringkat corong yang terputus dan metrik merah.
     *
     * @param  Collection<int, Service>  $services
     * @return array<int, array<string, mixed>>
     */
    private function causes(Collection $services, int $year, int $month): array
    {
        $keluar = [];

        foreach ($services as $service) {
            $rows = $this->critical->rowsFor($service, $year, $month);

            if ($rows->isEmpty()) {
                continue;
            }

            $journey = $this->journey->build($rows);
            $break = $journey['firstBreak'] ?? null;

            if ($break !== null) {
                $keluar[] = [
                    'service' => $service->name,
                    'stage' => $break['title'],
                    'owner' => $break['owner'] ?? '—',
                    'reason' => ($break['breakReason'] ?? null) === 'missing'
                        ? __('report.cause.missing', ['stage' => $break['title']])
                        : __('report.cause.below', [
                            'stage' => $break['title'],
                            'pct' => number_format((float) ($break['pct'] ?? 0), 0),
                        ]),
                    'effect' => __('report.cause.effect', [
                        'count' => $journey['blockedCount'] ?? 0,
                    ]),
                    'blocked' => collect($journey['waiting'] ?? [])->pluck('title')->implode(', '),
                ];
            }

            foreach ($rows->where('status', MetricStatus::Red)->take(3) as $row) {
                $keluar[] = [
                    'service' => $service->name,
                    'stage' => $row['label'],
                    'owner' => $row['ownerName'] ?? '—',
                    'reason' => __('report.cause.metric_red', [
                        'metric' => $row['label'],
                        'actual' => $row['actualLabel'],
                        'target' => $row['targetLabel'],
                    ]),
                    'effect' => trim((string) $row['actionPlan']) !== ''
                        ? __('report.cause.has_plan')
                        : __('report.cause.no_plan'),
                    'blocked' => '',
                ];
            }
        }

        return $keluar;
    }

    /**
     * Akauntabiliti pemilik — siapa memiliki berapa banyak metrik merah.
     *
     * @param  Collection<int, Service>  $services
     * @return array<int, array<string, mixed>>
     */
    private function owners(Collection $services, int $year, int $month): array
    {
        $ikutPemilik = [];

        foreach ($services as $service) {
            foreach ($this->critical->rowsFor($service, $year, $month) as $row) {
                $nama = $row['ownerName'] ?? '—';

                if ($nama === '—' || ($row['owner']?->is_system ?? false)) {
                    continue;
                }

                $ikutPemilik[$nama] ??= [
                    'name' => $nama, 'total' => 0, 'red' => 0, 'amber' => 0, 'green' => 0,
                    'services' => [],
                ];

                $ikutPemilik[$nama]['total']++;
                $ikutPemilik[$nama]['services'][$service->name] = true;

                match ($row['status']) {
                    MetricStatus::Red => $ikutPemilik[$nama]['red']++,
                    MetricStatus::Yellow => $ikutPemilik[$nama]['amber']++,
                    MetricStatus::Green => $ikutPemilik[$nama]['green']++,
                    default => null,
                };
            }
        }

        return collect($ikutPemilik)
            ->map(function (array $o): array {
                $o['services'] = implode(', ', array_keys($o['services']));
                $o['score'] = $o['total'] > 0 ? (int) round($o['green'] / $o['total'] * 100) : 0;

                return $o;
            })
            // Paling banyak merah di atas. Senarai mengikut abjad
            // menyembunyikan orang yang memerlukan sokongan paling banyak
            // di tengah-tengah halaman.
            ->sortByDesc('red')
            ->values()
            ->all();
    }

    /**
     * Cadangan tindakan, disusun mengikut apa yang menyekat perkara lain.
     *
     * @param  Collection<int, Service>  $services
     * @param  Collection<int, array<string, mixed>>  $breakdown
     * @return array<int, array<string, mixed>>
     */
    private function actions(Collection $services, int $year, int $month, Collection $breakdown): array
    {
        $keluar = [];

        foreach ($services as $service) {
            $rows = $this->critical->rowsFor($service, $year, $month);

            if ($rows->isEmpty()) {
                continue;
            }

            $journey = $this->journey->build($rows);
            $break = $journey['firstBreak'] ?? null;

            if ($break !== null) {
                $keluar[] = [
                    'priority' => 1,
                    'urgency' => __('report.action.immediate'),
                    'service' => $service->name,
                    'owner' => $break['owner'] ?? '—',
                    'what' => __('report.action.fix_stage', ['stage' => $break['title']]),
                    'why' => __('report.action.fix_stage_why', [
                        'count' => $journey['blockedCount'] ?? 0,
                    ]),
                    'when' => __('report.action.this_week'),
                ];
            }

            foreach ($rows->where('status', MetricStatus::Red) as $row) {
                if (trim((string) $row['actionPlan']) === '') {
                    $keluar[] = [
                        'priority' => 2,
                        'urgency' => __('report.action.immediate'),
                        'service' => $service->name,
                        'owner' => $row['ownerName'] ?? '—',
                        'what' => __('report.action.write_plan', ['metric' => $row['label']]),
                        'why' => __('report.action.write_plan_why'),
                        'when' => __('report.action.this_week'),
                    ];

                    continue;
                }

                $keluar[] = [
                    'priority' => 3,
                    'urgency' => __('report.action.ongoing'),
                    'service' => $service->name,
                    'owner' => $row['ownerName'] ?? '—',
                    'what' => trim((string) $row['actionPlan']),
                    'why' => __('report.action.metric_why', [
                        'metric' => $row['label'],
                        'actual' => $row['actualLabel'],
                        'target' => $row['targetLabel'],
                    ]),
                    'when' => __('report.action.this_month'),
                ];
            }
        }

        // Jurang jualan terbesar mendapat tindakan tersendiri: ia nombor
        // yang mesyuarat sebenarnya mengenainya.
        $terbesar = $breakdown->sortByDesc('gap')->first();

        if ($terbesar !== null && $terbesar['gap'] > 0) {
            $keluar[] = [
                'priority' => 1,
                'urgency' => __('report.action.immediate'),
                'service' => $terbesar['service']->name,
                'owner' => '—',
                'what' => __('report.action.close_gap', [
                    'amount' => $this->metrics->formatRm($terbesar['gap']),
                ]),
                'why' => __('report.action.close_gap_why', [
                    'pct' => number_format($terbesar['pct'], 1),
                ]),
                'when' => __('report.action.this_month'),
            ];
        }

        usort($keluar, fn (array $a, array $b) => $a['priority'] <=> $b['priority']);

        return $keluar;
    }

    /**
     * Satu bahagian analisis penuh bagi satu servis.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function serviceSection(array $row, int $year, int $month): array
    {
        $service = $row['service'];
        $satu = collect([$service]);

        $roadmap = $this->roadmapFor($service, $year, $month);

        return [
            'service' => $service,
            'summary' => $row,
            'roadmap' => $roadmap,
            'narrative' => $this->serviceNarrative($row, $roadmap),
            'funnel' => $this->funnel($satu, $year, $month),
            'causes' => $roadmap['active'] ? $this->causes($satu, $year, $month) : [],
            'owners' => $this->owners($satu, $year, $month),
            'actions' => $roadmap['active']
                ? $this->actions($satu, $year, $month, collect([$row]))
                : [],
        ];
    }

    /**
     * Ulasan khusus servis, dalam ayat.
     *
     * Servis yang roadmap tandakan dijeda mendapat ulasan yang BERBEZA
     * sepenuhnya: bulan di bawah sasaran ketika tiada kempen berjalan
     * bukan kegagalan, ia rancangan. Menghakimi kedua-duanya dengan ayat
     * yang sama menghukum keputusan yang betul.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $roadmap
     */
    private function serviceNarrative(array $row, array $roadmap): string
    {
        if (! $roadmap['active']) {
            return __('report.service.paused', [
                'service' => $row['service']->name,
                'status' => $roadmap['label'],
                'actual' => $this->metrics->formatRm($row['actual']),
            ]);
        }

        return __('report.service.narrative_'.$row['status']['key'], [
            'service' => $row['service']->name,
            'pct' => number_format($row['pct'], 1).'%',
            'actual' => $this->metrics->formatRm($row['actual']),
            'target' => $this->metrics->formatRm($row['target']),
            'gap' => $this->metrics->formatRm($row['gap']),
            'status' => $roadmap['label'],
        ]);
    }

    /**
     * Status roadmap servis bagi tempoh ini.
     *
     * @return array{active: bool, label: string, months: int, known: bool}
     */
    private function roadmapFor(Service $service, int $year, int $month): array
    {
        $cells = RoadmapCell::where('service_id', $service->id)
            ->where('year', $year)
            ->get()
            ->keyBy('month');

        if ($cells->isEmpty()) {
            /*
             * Tiada roadmap ditetapkan. Melaporkannya sebagai "dijeda"
             * akan menyenyapkan analisis untuk servis yang mungkin
             * sedang berjalan; melaporkannya sebagai "aktif" mendakwa
             * niat yang tiada siapa rekodkan. Dinyatakan seperti adanya.
             */
            return ['active' => true, 'label' => __('report.roadmap.unknown'), 'months' => 0, 'known' => false];
        }

        $status = $cells->get($month)?->status ?? RoadmapStatus::None;

        $aktif = $cells->filter(fn ($c) => $c->status->countsTowardTarget())->count();

        return [
            'active' => $status->countsTowardTarget(),
            'label' => $status->label(),
            'months' => $aktif,
            'known' => true,
        ];
    }

    /**
     * Berapa banyak servis sedang berjalan bulan ini.
     *
     * @param  Collection<int, Service>  $services
     * @return array<string, mixed>
     */
    private function roadmapSummary(Collection $services, int $year, int $month): array
    {
        $aktif = [];
        $jeda = [];

        foreach ($services as $service) {
            $r = $this->roadmapFor($service, $year, $month);

            if ($r['active']) {
                $aktif[] = $service->name;
            } else {
                $jeda[] = $service->name.' ('.$r['label'].')';
            }
        }

        return [
            'activeCount' => count($aktif),
            'total' => $services->count(),
            'active' => implode(', ', $aktif),
            'paused' => implode(', ', $jeda),
        ];
    }

    /**
     * @return array{key: string, label: string, color: string}
     */
    private function verdict(float $pct): array
    {
        return match (true) {
            $pct >= self::WARN_PCT => ['key' => 'green', 'label' => __('report.verdict.on_track'), 'color' => '#1E8449'],
            $pct >= self::CRITICAL_PCT => ['key' => 'amber', 'label' => __('report.verdict.watch'), 'color' => '#C98A12'],
            default => ['key' => 'red', 'label' => __('report.verdict.critical'), 'color' => '#C0392B'],
        };
    }
}
