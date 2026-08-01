<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Peta Perjalanan Sales — corong jualan sebagai satu jalan raya.
 *
 * Jadual Data Kritikal menunjukkan sepuluh metrik sebagai baris berasingan,
 * setiap satu dengan peratusannya sendiri. Susunan itu menyembunyikan
 * perkara yang paling penting: metrik-metrik ini bukan bebas. Lead menjadi
 * site visit, site visit menjadi quotation, quotation menjadi jualan.
 *
 * Pemilik yang melihat "No of Lead 45%" sebagai satu baris antara sepuluh
 * akan menganggapnya masalah kecil. Pemilik yang melihatnya sebagai
 * halangan pertama di atas jalan — dengan tiga peringkat di hilirnya
 * bertanda merah kerananya — faham bahawa membetulkan lead adalah
 * satu-satunya perkara yang penting minggu ini.
 *
 * Peta ini menyusun corong itu mengikut urutan sebenar dan menandakan di
 * mana jalan terputus.
 */
class SalesJourneyService
{
    /**
     * Peringkat perjalanan, mengikut urutan.
     *
     * Setiap peringkat menerima beberapa kunci metrik kerana servis berbeza
     * mengukur langkah tengah secara berbeza — renovation merekod site
     * visit, bina-rumah merekod appointment. Kunci pertama yang wujud
     * digunakan.
     *
     * @var array<int, array<string, mixed>>
     */
    private const STAGES = [
        [
            'key' => 'lead',
            'metrics' => ['no_of_lead'],
            'icon' => 'ph-users-three',
            'accent' => 'oklch(0.62 0.19 255)',
        ],
        [
            'key' => 'site_visit',
            'metrics' => ['no_of_site_visit', 'no_of_appointment'],
            'icon' => 'ph-house-line',
            'accent' => 'oklch(0.62 0.17 150)',
        ],
        [
            'key' => 'quotation',
            'metrics' => ['no_of_new_quotation'],
            'amountMetric' => 'amount_quotation_release',
            'icon' => 'ph-file-text',
            'accent' => 'oklch(0.72 0.17 60)',
        ],
        [
            'key' => 'sales',
            'metrics' => ['revenue_sales'],
            'amountMetric' => 'sales_collection_new',
            'icon' => 'ph-chart-line-up',
            'accent' => 'oklch(0.58 0.19 305)',
        ],
    ];

    /** Bawah paras ini, peringkat dikira terputus. */
    private const BREAK_THRESHOLD = 60.0;

    /** Bawah paras ini, peringkat perlu diperhatikan. */
    private const WARN_THRESHOLD = 90.0;

    /**
     * @param  Collection<int, array<string, mixed>>  $rows  Baris Data Kritikal
     * @return array<string, mixed>
     */
    public function build(Collection $rows): array
    {
        $byKey = $rows->keyBy('metricKey');
        $stages = [];

        foreach (self::STAGES as $i => $def) {
            $row = $this->resolve($def['metrics'], $byKey);

            if ($row === null) {
                continue;
            }

            $stages[] = $this->stage($def, $row, $i + 1, $byKey);
        }

        // Halangan PERTAMA ialah satu-satunya yang benar-benar penting.
        // Peringkat hilir yang gagal selepasnya adalah gejala, bukan punca —
        // membetulkannya secara berasingan tidak akan berkesan.
        $firstBreak = collect($stages)->firstWhere('broken', true);

        $stages = $this->markBlocked($stages, $firstBreak);

        return [
            'stages' => $stages,
            'firstBreak' => $firstBreak,
            'healthy' => $firstBreak === null,
            'blockedCount' => $firstBreak === null
                ? 0
                : collect($stages)->where('blocked', true)->count(),
        ];
    }

    /**
     * @param  array<int, string>  $keys
     * @param  Collection<string, array<string, mixed>>  $byKey
     * @return array<string, mixed>|null
     */
    private function resolve(array $keys, Collection $byKey): ?array
    {
        foreach ($keys as $key) {
            if ($byKey->has($key)) {
                return $byKey->get($key);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $def
     * @param  array<string, mixed>  $row
     * @param  Collection<string, array<string, mixed>>  $byKey
     * @return array<string, mixed>
     */
    private function stage(array $def, array $row, int $no, Collection $byKey): array
    {
        $pct = $row['pct'] !== null ? (float) $row['pct'] : null;
        $target = $row['target'] !== null ? (float) $row['target'] : null;
        $actual = $row['actual'] !== null ? (float) $row['actual'] : null;

        $status = match (true) {
            $pct === null => 'none',
            $pct < self::BREAK_THRESHOLD => 'red',
            $pct < self::WARN_THRESHOLD => 'amber',
            default => 'green',
        };

        $amount = isset($def['amountMetric']) ? $byKey->get($def['amountMetric']) : null;

        return [
            'no' => $no,
            'key' => $def['key'],
            'title' => __('journey.stage.'.$def['key']),
            'metricLabel' => $row['label'],
            'icon' => $def['icon'],
            'accent' => $def['accent'],

            'actual' => $actual,
            'actualLabel' => $row['actualLabel'],
            'target' => $target,
            'targetLabel' => $row['targetLabel'],
            'pct' => $pct,
            'pctLabel' => $pct !== null ? number_format($pct, 0).'%' : '—',

            // Sasaran mingguan menjadikannya boleh ditindaklanjuti. "1,035
            // sebulan" ialah nombor untuk dipandang; "259 seminggu" ialah
            // nombor untuk dirancang.
            'perWeekLabel' => $target !== null
                ? $row['valueType']->format(ceil($target / 4)).' / '.__('journey.week')
                : null,

            'gapLabel' => ($target !== null && $actual !== null && $actual < $target)
                ? $row['valueType']->format($target - $actual)
                : null,

            'amountLabel' => $amount['actualLabel'] ?? null,
            'amountTargetLabel' => $amount['targetLabel'] ?? null,
            'amountTitle' => $amount !== null ? __('journey.amount.'.$def['key']) : null,

            'status' => $status,
            'broken' => $status === 'red',
            'blocked' => false,
            'blockedBy' => null,
            'cause' => $status === 'red' ? __('journey.cause.'.$def['key']) : null,
            'causeTitle' => __('journey.cause_title.'.$def['key']),
        ];
    }

    /**
     * Tandakan peringkat hilir sebagai TERSEKAT, bukan sekadar gagal.
     *
     * Perbezaan itu mengubah tindakan. Peringkat yang gagal memerlukan
     * pembetulan sendiri; peringkat yang tersekat akan pulih dengan
     * sendirinya sebaik sahaja halangan di hulu dibuka. Menandakan
     * kesemuanya "merah" tanpa perbezaan menghantar pemilik mengejar empat
     * masalah sedangkan hanya ada satu.
     *
     * @param  array<int, array<string, mixed>>  $stages
     * @param  array<string, mixed>|null  $firstBreak
     * @return array<int, array<string, mixed>>
     */
    private function markBlocked(array $stages, ?array $firstBreak): array
    {
        if ($firstBreak === null) {
            return $stages;
        }

        $lepas = false;

        foreach ($stages as $i => $stage) {
            if ($stage['key'] === $firstBreak['key']) {
                $lepas = true;

                continue;
            }

            if ($lepas && $stage['status'] !== 'green') {
                $stages[$i]['blocked'] = true;
                $stages[$i]['blockedBy'] = $firstBreak['title'];
            }
        }

        return $stages;
    }
}
