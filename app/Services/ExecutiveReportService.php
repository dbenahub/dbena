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
 * Perkhidmatan ini menambah lapisan yang menjadikannya dokumen: keterukan
 * keseluruhan, isu mengikut keutamaan, rantaian punca-kesan, sasaran
 * mingguan bernama, dan keputusan yang perlu diluluskan pengurusan.
 *
 * Semua angka datang daripada laporan sedia ada — tiada satu pun ditaip
 * semula. Laporan yang mengandungi nombor yang tidak sepadan dengan
 * dashboard akan meruntuhkan kepercayaan pada kedua-duanya.
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

    private const PRIORITY_LIMIT = 4;

    private const ROOT_CAUSE_LIMIT = 6;

    public function __construct(private readonly SalesJourneyService $journey) {}

    /**
     * @param  array<string, mixed>  $report  Hasil OwnerReportService::build()
     * @return array<string, mixed>
     */
    public function build(array $report): array
    {
        /** @var Collection<int, array<string, mixed>> $owners */
        $owners = $report['owners'];

        $metrics = $owners->flatMap(fn (array $o) => $this->ownerMetrics($o));
        $diagnoses = $owners->flatMap(fn (array $o) => $o['diagnoses']);

        $score = (int) $report['summary']['teamScore'];

        return [
            'severity' => $this->severity($score),
            'severityKey' => $this->severityKey($score),
            'gapTotal' => $this->gapTotal($metrics),
            'scorecard' => $metrics->sortBy('pct', SORT_REGULAR)->values(),
            'priorities' => $this->priorities($diagnoses),
            'journey' => $this->journey->build($this->journeyRows($metrics)),
            'rootCauses' => $this->rootCauses($diagnoses),
            'observations' => $this->observations($metrics),
            'weeklyTargets' => $this->weeklyTargets($metrics),
            'missingTargets' => $metrics->whereNull('target')->pluck('label')->values(),
            'noPlanCount' => $metrics->where('hasPlan', false)->where('status', MetricStatus::Red)->count(),
        ];
    }

    /**
     * Ratakan metrik setiap pemilik menjadi satu senarai dengan nama PIC
     * dilekatkan, supaya scorecard boleh dibaca sebagai satu jadual.
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
            'actual' => $m['actual'],
            'actualLabel' => $m['actualLabel'],
            'target' => $m['target'],
            'targetLabel' => $m['targetLabel'],
            'valueType' => $m['metric']->value_type,
            'pct' => $m['pct'],
            'pctLabel' => $m['pct'] !== null ? number_format((float) $m['pct'], 1).'%' : '—',
            'status' => $m['status'],
            'statusLabel' => $m['status']->label(),
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
     * Isu mengikut keutamaan — diambil daripada diagnosis corong yang
     * sedia ada dan diisih mengikut bilangan metrik hilir yang terjejas.
     *
     * Isu yang menyekat empat metrik lain mendahului isu yang menyekat
     * satu, walaupun peratusannya lebih baik. Itu perbezaan antara
     * senarai masalah dan senarai keutamaan.
     *
     * @param  Collection<int, array<string, mixed>>  $diagnoses
     * @return array<int, array<string, mixed>>
     */
    private function priorities(Collection $diagnoses): array
    {
        return $diagnoses
            ->unique('metricKey')
            ->sortByDesc(fn (array $d) => [count($d['impacts']), -(float) ($d['pct'] ?? 100)])
            ->take(self::PRIORITY_LIMIT)
            ->values()
            ->map(fn (array $d, int $i) => [
                'rank' => $i + 1,
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
            ->unique('metricKey')
            ->sortByDesc(fn (array $d) => count($d['impacts']))
            ->take(self::ROOT_CAUSE_LIMIT)
            ->values()
            ->map(fn (array $d) => [
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
     * "474 lead menghasilkan 11 site visit" memberitahu lebih banyak
     * daripada dua baris berasingan yang menunjukkan 79% dan 45.8%.
     *
     * @param  Collection<int, array<string, mixed>>  $metrics
     * @return array<int, string>
     */
    private function observations(Collection $metrics): array
    {
        $byKey = $metrics->keyBy('metricKey');
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

        if ($ads && $ads['pct'] !== null && $byKey->has('no_of_lead') && $byKey['no_of_lead']['pct'] !== null) {
            $out[] = __('exec.obs.ads_vs_lead', [
                'ads' => number_format((float) $ads['pct'], 1),
                'lead' => number_format((float) $byKey['no_of_lead']['pct'], 1),
            ]);
        }

        if ($lead > 0 && $visit !== null) {
            $out[] = __('exec.obs.lead_to_visit', [
                'lead' => number_format($lead),
                'visit' => number_format($visit),
                'rate' => number_format($visit / $lead * 100, 1),
            ]);
        }

        if ($visit > 0 && $quote !== null) {
            $out[] = __('exec.obs.visit_to_quote', [
                'visit' => number_format($visit),
                'quote' => number_format($quote),
                'rate' => number_format($quote / $visit * 100, 1),
            ]);
        }

        if ($amount > 0 && $sales !== null) {
            $out[] = __('exec.obs.quote_to_sales', [
                'amount' => 'RM'.number_format($amount),
                'sales' => 'RM'.number_format($sales),
                'rate' => number_format($sales / $amount * 100, 1),
            ]);
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
     * Bina baris untuk peta perjalanan daripada scorecard yang diratakan.
     *
     * @param  Collection<int, array<string, mixed>>  $metrics
     * @return Collection<int, array<string, mixed>>
     */
    private function journeyRows(Collection $metrics): Collection
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
            'ownerName' => $m['ownerName'],
        ]);
    }
}
