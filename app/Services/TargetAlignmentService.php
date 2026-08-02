<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MetricValueType;
use App\Models\StrategyRow;
use Illuminate\Support\Collection;

/**
 * Membandingkan sasaran Data Kritikal dengan sasaran Strategic Planning.
 *
 * Kedua-duanya datang daripada sheet yang berbeza dan diisi oleh orang
 * yang berbeza. Apabila pengurusan meluluskan RM500,000 sebulan dalam
 * pelan strategik tetapi jadual mingguan mengejar RM450,000, tiada siapa
 * perasan — kedua-dua nombor kelihatan rasmi pada skrinnya sendiri, dan
 * pemilik yang mencapai satu daripadanya percaya dia sudah selamat.
 *
 * Kelas ini tidak membetulkan apa-apa. Ia hanya menamakan percanggahan
 * dan memberitahu sheet mana yang perlu disunting, kerana kedua-dua
 * nombor itu dimiliki oleh Google Sheet dan bukan oleh dashboard.
 */
class TargetAlignmentService
{
    /**
     * Metrik yang mempunyai pasangan dalam pelan strategik.
     *
     * Sengaja pendek. Ads Spend, CPL dan CPA tiada baris dalam pelan, dan
     * mereka-reka padanan untuk metrik itu akan menghasilkan amaran palsu
     * yang mengajar orang mengabaikan penunjuk ini sepenuhnya.
     *
     * @var array<string, array<int, string>>
     */
    private const PAIRS = [
        'revenue_sales' => ['jualan bulanan', 'peningkatan jualan', 'revenue'],
        'sales_collection_new' => ['sales collection', 'closing sales'],
        'amount_quotation_release' => ['nilai quotation', 'quotation performance'],
        'no_of_site_visit' => ['site visit berjaya', 'site visit conversion'],
        'no_of_appointment' => ['appointment'],
        'no_of_lead' => ['lead dilayan', 'lead management'],
    ];

    /**
     * Berapa minggu dalam sebulan bagi tujuan perbandingan.
     *
     * Empat, bukan 4.345. Pelan ditulis oleh manusia yang bermaksud
     * "empat minggu" apabila mereka menulis sebulan, dan sasaran Data
     * Kritikal ditetapkan mengikut logik yang sama. Menggunakan purata
     * kalendar yang lebih tepat akan menandakan setiap pasangan sebagai
     * tidak sepadan sebanyak 8%.
     */
    private const WEEKS_PER_MONTH = 4;

    /**
     * @param  Collection<int, array<string, mixed>>  $rows  Baris Data Kritikal
     * @param  Collection<int, StrategyRow>  $strategyRows
     * @return array<string, array<string, mixed>> metricKey => percanggahan
     */
    public function compare(Collection $rows, Collection $strategyRows): array
    {
        if ($strategyRows->isEmpty()) {
            // Tanpa pelan tiada apa-apa untuk dibandingkan. Menandakan
            // setiap sasaran sebagai "tidak disahkan" akan mengecat
            // seluruh jadual merah pada hari pertama.
            return [];
        }

        $mismatch = [];

        foreach ($rows as $row) {
            $key = $row['metricKey'] ?? null;
            $target = $row['target'] ?? null;

            if ($key === null || $target === null || ! isset(self::PAIRS[$key])) {
                continue;
            }

            $plan = $this->findPlanRow($strategyRows, self::PAIRS[$key]);

            if ($plan === null) {
                continue;
            }

            $planned = $this->monthlyValue($plan->target);

            // Sasaran yang tidak boleh diukur — "Project siap awal dari
            // jadual" — bukan percanggahan, ia komitmen. Membandingkannya
            // dengan nombor tidak bermakna.
            if ($planned === null) {
                continue;
            }

            /*
             * Toleransi satu ringgit, bukan padanan tepat.
             *
             * Kedua-dua nilai melalui pembundaran perpuluhan dalam
             * perjalanan dari sheet ke pangkalan data. Menuntut kesamaan
             * tepat akan menandakan RM500,000.00 berbeza daripada
             * RM499,999.9999 dan amaran itu tidak boleh diselesaikan
             * oleh sesiapa.
             */
            if (abs($planned - (float) $target) < 1.0) {
                continue;
            }

            $type = $row['valueType'] ?? MetricValueType::Number;

            $mismatch[$key] = [
                'label' => $row['label'],
                'criticalLabel' => $type->format((float) $target),
                'plannedLabel' => $type->format($planned),
                'planTargetText' => $plan->target,
                'planKra' => $plan->kra,
                'planPic' => $plan->pic,
                'higher' => $planned > (float) $target ? 'plan' : 'critical',
            ];
        }

        return $mismatch;
    }

    /**
     * @param  Collection<int, StrategyRow>  $strategyRows
     * @param  array<int, string>  $needles
     */
    private function findPlanRow(Collection $strategyRows, array $needles): ?StrategyRow
    {
        foreach ($strategyRows as $plan) {
            $haystack = mb_strtolower(trim($plan->kra.' '.$plan->kpi));

            foreach ($needles as $needle) {
                if (str_contains($haystack, $needle)) {
                    return $plan;
                }
            }
        }

        return null;
    }

    /**
     * Nilai bulanan daripada sasaran pelan yang ditulis manusia.
     *
     * Pelan menulis "RM500,000 / bulan", "150 lead / minggu" dan
     * "> RM600,000 / minggu". Data Kritikal sentiasa bulanan, jadi
     * sasaran mingguan mesti didarab sebelum kedua-duanya boleh
     * dibandingkan — kalau tidak setiap pasangan mingguan akan
     * kelihatan berbeza empat kali ganda dan amaran menjadi bising
     * sehingga tiada gunanya.
     *
     * Sasaran HARIAN diabaikan. "25 lead / hari" ialah pecahan mingguan
     * yang ditulis untuk kegunaan lapangan, bukan komitmen bulanan yang
     * berasingan, dan bilangan hari bekerja sebulan tidak pernah
     * dinyatakan di mana-mana.
     */
    private function monthlyValue(?string $raw): ?float
    {
        if ($raw === null) {
            return null;
        }

        // Sel bergabung boleh membawa beberapa sasaran. Yang pertama
        // ialah komitmen utama; selebihnya pecahannya.
        $text = trim((string) preg_split('/\r?\n/', $raw)[0]);
        $lower = mb_strtolower($text);

        if (str_contains($lower, 'hari') || str_contains($lower, 'daily')) {
            return null;
        }

        if (! preg_match('/(\d[\d,.]*)/', $text, $m)) {
            return null;
        }

        $value = (float) str_replace(',', '', $m[1]);

        if ($value <= 0.0) {
            return null;
        }

        $mingguan = str_contains($lower, 'minggu')
            || str_contains($lower, 'week')
            || str_contains($lower, 'wkly');

        return $mingguan ? $value * self::WEEKS_PER_MONTH : $value;
    }
}
