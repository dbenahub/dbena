<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MetricStatus;
use App\Enums\MetricValueType;
use Illuminate\Support\Collection;

/**
 * Menyusun laporan pemilik menjadi dokumen pengurusan.
 *
 * Laporan asal ialah senarai metrik dengan peratusan. Berguna untuk
 * memantau, tetapi bukan sesuatu yang boleh dibentangkan dalam mesyuarat
 * pengurusan atau diserahkan kepada seseorang sebagai arahan.
 *
 * PENGUMPULAN MENGIKUT SERVIS ialah keputusan utama di sini. Laporan
 * "semua servis" versi pertama meratakan setiap metrik ke dalam satu
 * jadual, jadi "No of Lead — ZIKRI — 600" muncul lima kali berturut-turut
 * dengan nombor berbeza dan tiada apa-apa menunjukkan servis mana. Pembaca
 * tidak boleh mempercayai satu baris pun.
 *
 * Lebih teruk: corong jualan dikira daripada senarai rata itu, dan
 * kunci metrik yang sama daripada lima servis bertindih antara satu sama
 * lain. Rantaian yang dipaparkan sebagai "syarikat" sebenarnya nombor satu
 * servis rawak. Itu bukan susun atur yang mengelirukan — itu angka yang
 * salah.
 *
 * Kini setiap servis mendapat blok sendiri: metriknya, corongnya, punca
 * akarnya, sasaran mingguannya. Tahap syarikat hanya menghimpunkan.
 */
class ExecutiveReportService
{
    /** Metrik yang diberi sasaran mingguan bernama dalam jadual operasi. */
    private const OPERATIONAL = [
        'no_of_lead' => 'harian',
        'no_of_site_visit' => 'harian',
        'no_of_appointment' => 'harian',
        'no_of_new_quotation' => 'dua_kali',
        'amount_quotation_release' => 'dua_kali',
        'revenue_sales' => 'mingguan',
        'sales_collection_new' => 'mingguan',
    ];

    private const PRIORITY_LIMIT = 5;

    private const ROOT_CAUSE_LIMIT = 6;

    public function __construct(
        private readonly SalesJourneyService $journey,
        private readonly FunnelAnalysisService $funnel,
    ) {}

    /**
     * @param  array<string, mixed>  $report  Hasil OwnerReportService::build()
     * @return array<string, mixed>
     */
    public function build(array $report): array
    {
        /** @var Collection<int, array<string, mixed>> $owners */
        $owners = $report['owners'];

        $metrics = $owners->flatMap(fn (array $o) => $this->ownerMetrics($o));

        $services = $this->byService($metrics);

        // Isu daripada SETIAP servis, disatukan dan diisih semula. Setiap
        // satu membawa nama servisnya, jadi jadual keutamaan di muka depan
        // tidak lagi menyenaraikan empat baris "No of New Quotation" yang
        // kelihatan serupa.
        $diagnoses = $services->flatMap(fn (array $s) => $s['diagnoses']);

        $score = (int) $report['summary']['teamScore'];

        return [
            'severity' => $this->severity($score),
            'severityKey' => $this->severityKey($score),
            'gapTotal' => $this->gapTotal($metrics),

            'multiService' => $services->count() > 1,
            'services' => $services,

            'scorecard' => $metrics->sortBy('pct', SORT_REGULAR)->values(),
            'priorities' => $this->priorities($diagnoses),
            'rootCauses' => $this->rootCauses($diagnoses),
            'observations' => $services->flatMap(fn (array $s) => $s['observations'])->values()->all(),
            'weeklyTargets' => $services->flatMap(fn (array $s) => $s['weeklyTargets'])->values()->all(),

            // Nama metrik berulang merentas servis. Tanpa dedup, senarai
            // "tiada sasaran" menyebut Sales Collection (Progress Claim)
            // lima kali dan kelihatan seperti pepijat.
            'missingTargets' => $metrics
                ->whereNull('target')
                ->map(fn (array $m) => $m['serviceName'].' — '.$m['label'])
                ->unique()
                ->values(),

            'noPlanCount' => $metrics->where('hasPlan', false)->where('status', MetricStatus::Red)->count(),

            // Corong peringkat syarikat hanya bermakna apabila satu servis
            // dipilih. Merentas servis, setiap satu ada corongnya sendiri.
            'journey' => $services->count() === 1
                ? $services->first()['journey']
                : null,
        ];
    }

    /**
     * Satu blok setiap servis: metrik, corong, punca, sasaran mingguan.
     *
     * @param  Collection<int, array<string, mixed>>  $metrics
     * @return Collection<int, array<string, mixed>>
     */
    private function byService(Collection $metrics): Collection
    {
        return $metrics
            ->groupBy('serviceId')
            ->map(function (Collection $rows): array {
                $pertama = $rows->first();
                $funnelRows = $this->funnelRows($rows);

                $diagnoses = $this->funnel
                    ->diagnoseOwner($funnelRows, $funnelRows)
                    ->map(function (array $d) use ($pertama): array {
                        $d['serviceName'] = $pertama['serviceName'];

                        return $d;
                    });

                $total = $rows->count();
                $green = $rows->where('status', MetricStatus::Green)->count();

                return [
                    'id' => $pertama['serviceId'],
                    'name' => $pertama['serviceName'],
                    'sort' => $pertama['serviceSort'],

                    'total' => $total,
                    'green' => $green,
                    'red' => $rows->where('status', MetricStatus::Red)->count(),
                    'score' => $total > 0 ? (int) round($green / $total * 100) : 0,
                    'gap' => $this->gapTotal($rows),

                    // Pemilik yang menyentuh servis ini — supaya tajuk kecil
                    // boleh menyebut siapa bertanggungjawab tanpa pembaca
                    // perlu mengimbas seluruh jadual.
                    'owners' => $rows->pluck('ownerName')->unique()->sort()->values()->all(),

                    'metrics' => $rows->sortBy('pct', SORT_REGULAR)->values(),
                    'journey' => $this->journey->build($funnelRows),
                    'diagnoses' => $diagnoses,
                    'rootCauses' => $this->rootCauses($diagnoses),
                    'observations' => $this->observations($rows),
                    'weeklyTargets' => $this->weeklyTargets($rows),
                ];
            })
            ->sortBy('sort')
            ->values();
    }

    /**
     * Ratakan metrik seorang pemilik, dengan servis DAN nama PIC dilekatkan.
     *
     * @param  array<string, mixed>  $owner
     * @return Collection<int, array<string, mixed>>
     */
    private function ownerMetrics(array $owner): Collection
    {
        return collect($owner['metrics'])->map(fn (array $m) => [
            'metricKey' => $m['metric']->metric_key,
            'label' => $m['label'],
            'ownerName' => $owner['name'],

            'serviceId' => $m['metric']->service_id,
            'serviceName' => $m['metric']->service->name,
            'serviceSort' => $m['metric']->service->sort_order,

            'actual' => $m['actual'],
            'actualLabel' => $m['actualLabel'],
            'target' => $m['target'],
            'targetLabel' => $m['targetLabel'],
            'valueType' => $m['metric']->value_type,
            'pct' => $m['pct'],
            'pctLabel' => $m['pct'] !== null ? number_format((float) $m['pct'], 1).'%' : '—',
            'status' => $m['status'],
            'statusLabel' => $m['status']->label(),
            'actionPlan' => $m['actionPlan'],
            'hasPlan' => filled($m['actionPlan']),
            'gapLabel' => $this->gapLabel($m),
        ]);
    }

    /** @param  array<string, mixed>  $m */
    private function gapLabel(array $m): string
    {
        if ($m['target'] === null || $m['actual'] === null) {
            return '—';
        }

        $delta = (float) $m['actual'] - (float) $m['target'];
        $type = $m['metric']->value_type;

        return ($delta >= 0 ? '+' : '').$type->format(abs($delta));
    }

    /**
     * Jumlah jurang RM merentas metrik kewangan.
     *
     * Peratusan memberitahu sejauh mana ketinggalan; ringgit memberitahu
     * berapa banyak. Pengurusan bertindak atas yang kedua.
     *
     * @param  Collection<int, array<string, mixed>>  $metrics
     */
    private function gapTotal(Collection $metrics): float
    {
        return (float) $metrics
            ->filter(fn (array $m) => $m['valueType'] === MetricValueType::Currency
                && $m['target'] !== null
                && $m['actual'] !== null
                && (float) $m['actual'] < (float) $m['target'])
            ->sum(fn (array $m) => (float) $m['target'] - (float) $m['actual']);
    }

    private function severity(int $score): string
    {
        return __('exec.severity.'.$this->severityKey($score));
    }

    private function severityKey(int $score): string
    {
        return match (true) {
            $score < 40 => 'critical',
            $score < 70 => 'attention',
            default => 'stable',
        };
    }

    /**
     * Isu mengikut keutamaan, diisih mengikut bilangan metrik hilir yang
     * disekat. Isu yang menyekat empat metrik lain mendahului isu yang
     * menyekat satu, walaupun peratusannya lebih baik.
     *
     * @param  Collection<int, array<string, mixed>>  $diagnoses
     * @return array<int, array<string, mixed>>
     */
    private function priorities(Collection $diagnoses): array
    {
        return $diagnoses
            ->sortByDesc(fn (array $d) => [count($d['impacts']), -(float) ($d['pct'] ?? 100)])
            ->take(self::PRIORITY_LIMIT)
            ->values()
            ->map(fn (array $d, int $i) => [
                'rank' => $i + 1,
                'service' => $d['serviceName'] ?? '—',
                'issue' => $d['label'],
                'evidence' => $d['actualLabel'].' / '.$d['targetLabel']
                    .($d['pct'] !== null ? ' ('.number_format((float) $d['pct'], 1).'%)' : ''),
                'implication' => $d['impacts'] !== []
                    ? __('exec.implication.blocks', [
                        'downstream' => collect($d['impacts'])->pluck('label')->take(3)->implode(', '),
                    ])
                    : __('exec.implication.direct'),
                'severity' => $d['severity'],
            ])
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $diagnoses
     * @return array<int, array<string, mixed>>
     */
    private function rootCauses(Collection $diagnoses): array
    {
        return $diagnoses
            ->sortByDesc(fn (array $d) => count($d['impacts']))
            ->take(self::ROOT_CAUSE_LIMIT)
            ->values()
            ->map(fn (array $d) => [
                'service' => $d['serviceName'] ?? '—',
                'cause' => $d['points'][0]['text'] ?? $d['label'],
                'evidence' => $d['actualLabel'].' / '.$d['targetLabel'],
                'effect' => $d['impacts'] !== []
                    ? collect($d['impacts'])->pluck('label')->take(2)->implode(', ')
                    : __('exec.effect.none'),
                'level' => count($d['impacts']) >= 3
                    ? __('exec.level.very_high')
                    : (count($d['impacts']) >= 1 ? __('exec.level.high') : __('exec.level.moderate')),
            ])
            ->all();
    }

    /**
     * Nisbah antara peringkat corong, dinyatakan sebagai ayat.
     *
     * Dikira PER SERVIS. Membahagikan jumlah lead semua servis dengan
     * jumlah site visit semua servis menghasilkan nombor yang tidak
     * menerangkan apa-apa tentang mana-mana servis.
     *
     * @param  Collection<int, array<string, mixed>>  $metrics
     * @return array<int, string>
     */
    private function observations(Collection $metrics): array
    {
        $byKey = $metrics->keyBy('metricKey');
        $servis = $metrics->first()['serviceName'] ?? '';
        $nilai = fn (string $k) => $byKey->has($k) && $byKey[$k]['actual'] !== null
            ? (float) $byKey[$k]['actual']
            : null;

        $out = [];

        $lead = $nilai('no_of_lead');
        $visit = $nilai('no_of_site_visit') ?? $nilai('no_of_appointment');
        $quote = $nilai('no_of_new_quotation');
        $amount = $nilai('amount_quotation_release');
        $sales = $nilai('revenue_sales');
        $ads = $byKey->get('ads_spend');

        $awalan = fn (string $teks) => $servis === '' ? $teks : $servis.': '.$teks;

        if ($ads && $ads['pct'] !== null && $byKey->has('no_of_lead') && $byKey['no_of_lead']['pct'] !== null) {
            $out[] = $awalan(__('exec.obs.ads_vs_lead', [
                'ads' => number_format((float) $ads['pct'], 1),
                'lead' => number_format((float) $byKey['no_of_lead']['pct'], 1),
            ]));
        }

        if ($lead > 0 && $visit !== null) {
            $out[] = $awalan(__('exec.obs.lead_to_visit', [
                'lead' => number_format($lead),
                'visit' => number_format($visit),
                'rate' => number_format($visit / $lead * 100, 1),
            ]));
        }

        if ($visit > 0 && $quote !== null) {
            $out[] = $awalan(__('exec.obs.visit_to_quote', [
                'visit' => number_format($visit),
                'quote' => number_format($quote),
                'rate' => number_format($quote / $visit * 100, 1),
            ]));
        }

        if ($amount > 0 && $sales !== null) {
            $out[] = $awalan(__('exec.obs.quote_to_sales', [
                'amount' => 'RM'.number_format($amount),
                'sales' => 'RM'.number_format($sales),
                'rate' => number_format($sales / $amount * 100, 1),
            ]));
        }

        return $out;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $metrics
     * @return array<int, array<string, string>>
     */
    private function weeklyTargets(Collection $metrics): array
    {
        return $metrics
            ->filter(fn (array $m) => isset(self::OPERATIONAL[$m['metricKey']]) && $m['target'] !== null)
            ->unique('metricKey')
            ->map(fn (array $m) => [
                'service' => $m['serviceName'],
                'label' => $m['label'],
                'weekly' => $m['valueType']->format(ceil((float) $m['target'] / 4)),
                'owner' => $m['ownerName'],
                'cadence' => __('exec.cadence.'.self::OPERATIONAL[$m['metricKey']]),
                // Pencetus eskalasi ialah separuh rentak menjelang pertengahan
                // minggu. Menunggu hingga Jumaat bermakna minggu itu sudah
                // hilang sebelum sesiapa menyedarinya.
                'trigger' => __('exec.trigger', [
                    'value' => $m['valueType']->format(ceil((float) $m['target'] / 8)),
                ]),
            ])
            ->values()
            ->all();
    }

    /**
     * Baris untuk analisis corong dan peta perjalanan.
     *
     * @param  Collection<int, array<string, mixed>>  $metrics
     * @return Collection<int, array<string, mixed>>
     */
    private function funnelRows(Collection $metrics): Collection
    {
        return $metrics->map(fn (array $m) => [
            'metricKey' => $m['metricKey'],
            'label' => $m['label'],
            'actual' => $m['actual'],
            'actualLabel' => $m['actualLabel'],
            'target' => $m['target'],
            'targetLabel' => $m['targetLabel'],
            'valueType' => $m['valueType'],
            'pct' => $m['pct'],
            'status' => $m['status'],
            'actionPlan' => $m['actionPlan'],
            'ownerName' => $m['ownerName'],
        ])->values();
    }
}
