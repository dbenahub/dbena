<?php

declare(strict_types=1);

namespace App\Services\Sheets;

use App\Contracts\SheetReader;
use App\Models\SheetIntegration;
use App\Models\StrategyPlan;
use App\Models\StrategyRow;
use App\Models\StrategyTile;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Menyegerak tab Strategic Planning & KPI Alignment daripada Google Sheet.
 *
 * Tab ini BUKAN grid biasa. Ia satu halaman perancangan: tajuk di atas,
 * satu blok visi, satu jalur petak ringkasan, kemudian jadual utama.
 * Membacanya seperti CSV bermakna mengandaikan baris pertama ialah tajuk
 * lajur, dan ia tidak pernah begitu.
 *
 * Jadi pembaca ini MENCARI bahagian, bukan mengira baris. Ia mengimbas
 * seluruh grid untuk penanda yang dikenali — sel "VISI", baris tajuk yang
 * mengandungi KRA dan KPI — dan bekerja dari situ. Perancang boleh
 * menambah baris kosong, menukar tinggi tajuk atau menyisipkan nota tanpa
 * memecahkan sync, kerana tiada satu pun kedudukan dikodkan keras.
 *
 * SHEET IALAH SATU-SATUNYA PENULIS. Sync menulis ganti keseluruhan pelan
 * servis itu, jadi baris yang dipadam dalam sheet turut hilang di sini.
 */
class StrategySyncService
{
    /** Tajuk lajur jadual utama yang dikenali. */
    private const HEADERS = [
        // Visi ialah LAJUR dalam sheet DBENA, bukan sel bertanda sendiri.
        // Satu sel bergabung menegak merentasi setiap baris KRA, jadi
        // Google memulangkan teksnya pada baris data PERTAMA sahaja.
        'vision' => ['vision', 'visi'],
        'kra' => ['kra', 'key result area', 'bidang'],
        'kpi' => ['kpi', 'petunjuk', 'indicator'],
        'target' => ['target', 'sasaran'],
        'tactics' => ['tactics', 'tactic', 'taktik', 'strategi'],
        'initiatives' => ['initiatives', 'initiative', 'inisiatif', 'tindakan'],
        'timeline' => ['timeline', 'tempoh', 'masa', 'jadual'],

        // 'ci' sendiri dibuang dengan sengaja: sebagai subrentetan ia
        // memadankan perkataan biasa dan boleh merampas lajur yang salah.
        'pic' => ['pic/ci', 'pic / ci', 'pic', 'pemilik', 'owner', 'hod'],
    ];

    /** Tajuk jalur petak ringkasan yang dikenali. */
    private const TILE_HEADERS = [
        'position' => ['no', 'bil', '#'],
        'label' => ['label', 'item', 'perkara', 'kpi', 'ringkasan'],
        'value' => ['value', 'nilai', 'target', 'sasaran'],
        'unit' => ['unit', 'nota', 'catatan', 'note', 'per'],
    ];

    /**
     * Ikon dipilih daripada label, bukan dibaca daripada sheet.
     *
     * Meminta perancang menaip nama ikon Phosphor dalam lajur sheet
     * menjemput salah taip yang menghasilkan kotak kosong, dan tiada
     * siapa dalam DBENA sepatutnya perlu tahu nama ikon.
     */
    private const ICONS = [
        /*
         * URUTAN PENTING — padanan pertama menang.
         *
         * "Sales Collection" mengandungi kedua-dua 'collection' dan
         * 'sales'. Letakkan 'sales' dahulu dan petak kutipan mendapat
         * ikon carta jualan, yang menjadikan dua petak berbeza kelihatan
         * seperti mengukur perkara yang sama.
         *
         * Begitu juga 'hari' mesti mendahului 'lead': sasaran harian dan
         * mingguan berkongsi KPI yang sama, jadi hanya perkataan tempoh
         * itu yang membezakannya.
         */
        'collection' => 'ph-hand-coins',
        'kutipan' => 'ph-hand-coins',
        'claim' => 'ph-clipboard-text',
        'milestone' => 'ph-clipboard-text',
        'testimoni' => 'ph-star',
        'testimonial' => 'ph-star',
        'review' => 'ph-star',
        'quotation' => 'ph-file-text',
        'sebut harga' => 'ph-file-text',
        'site visit' => 'ph-map-pin',
        'visit' => 'ph-map-pin',
        'appointment' => 'ph-map-pin',
        'harian' => 'ph-calendar-blank',
        'hari' => 'ph-calendar-blank',
        'daily' => 'ph-calendar-blank',
        'lead' => 'ph-users-three',
        'jualan' => 'ph-chart-line-up',
        'sales' => 'ph-chart-line-up',
        'revenue' => 'ph-chart-line-up',
    ];

    public function __construct(private readonly SheetReader $reader) {}

    /**
     * @return array<string, mixed>
     */
    public function sync(SheetIntegration $integration): array
    {
        if ($integration->service_id === null) {
            return $this->failure(__('strategy.sync.no_service'));
        }

        try {
            $grid = $this->reader->read($integration);
        } catch (Throwable $e) {
            return $this->failure(__('strategy.sync.read_failed', ['message' => $e->getMessage()]));
        }

        $headerRow = $this->findTableHeader($grid);

        if ($headerRow === null) {
            // Ini kegagalan yang paling mungkin berlaku, jadi ia mesti
            // memberitahu apa yang dicari. "Sync gagal" menghantar admin
            // meneka; menamakan lajur menghantar mereka terus ke sheet.
            return $this->failure(__('strategy.sync.no_table'));
        }

        $map = $this->mapColumns($grid[$headerRow], self::HEADERS);
        $rows = $this->readRows($grid, $headerRow, $map);

        if ($rows === []) {
            return $this->failure(__('strategy.sync.no_rows'));
        }

        // Jalur ringkasan eksplisit menang jika ada. Sheet DBENA tiada
        // satu pun, jadi petak diterbitkan daripada lajur Target — sumber
        // yang sama yang digunakan oleh reka bentuk asal.
        $tiles = $this->readTiles($grid, $headerRow) ?: $this->tilesFromTargets($rows);
        $serviceId = $integration->service_id;

        DB::transaction(function () use ($serviceId, $grid, $headerRow, $map, $rows, $tiles): void {
            /*
             * Padam dahulu, tulis kemudian.
             *
             * Pelan strategik dipangkas dan disusun semula dari suku ke
             * suku. Padanan mengikut kedudukan akan meninggalkan baris
             * lama yang telah dibuang daripada sheet, dan baris zombi
             * dalam dokumen tadbir urus lebih teruk daripada tiada
             * dokumen — orang merancang mengikutnya.
             */
            StrategyRow::where('service_id', $serviceId)->delete();
            StrategyTile::where('service_id', $serviceId)->delete();

            foreach ($rows as $i => $row) {
                StrategyRow::create($row + ['service_id' => $serviceId, 'position' => $i + 1]);
            }

            foreach ($tiles as $i => $tile) {
                StrategyTile::create($tile + ['service_id' => $serviceId, 'position' => $i + 1]);
            }

            StrategyPlan::updateOrCreate(
                ['service_id' => $serviceId],
                [
                    'heading' => $this->findHeading($grid, $headerRow),
                    'vision' => $this->columnVision($grid, $headerRow, $map)
                        ?? $this->findVision($grid, $headerRow),
                    'synced_at' => now(),
                ]
            );
        });

        return [
            'status' => 'success',
            'message' => __('strategy.sync.done', [
                'rows' => count($rows),
                'tiles' => count($tiles),
            ]),
            'rows' => count($rows),
            'tiles' => count($tiles),
        ];
    }

    /**
     * Cari baris tajuk jadual utama.
     *
     * Dikenali dengan mempunyai KEDUA-DUA kra dan kpi. Salah satu sahaja
     * tidak mencukupi: "KPI" muncul juga dalam tajuk halaman dan dalam
     * jalur ringkasan, dan memadankannya di sana akan menghalakan
     * pembaca ke bahagian yang salah.
     *
     * @param  array<int, array<int, mixed>>  $grid
     */
    private function findTableHeader(array $grid): ?int
    {
        foreach ($grid as $i => $row) {
            $map = $this->mapColumns($row, self::HEADERS);

            if (isset($map['kra'], $map['kpi'])) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, array<int, string>>  $patterns
     * @return array<string, int>
     */
    private function mapColumns(array $row, array $patterns): array
    {
        $map = [];

        foreach ($row as $col => $cell) {
            $text = $this->norm($cell);

            if ($text === '') {
                continue;
            }

            foreach ($patterns as $field => $names) {
                if (isset($map[$field])) {
                    continue;
                }

                foreach ($names as $name) {
                    // Padanan tepat dahulu, kemudian mengandungi. "PIC/CI"
                    // dan "PIC / CI" ialah sel yang sama kepada manusia.
                    if ($text === $name || str_contains($text, $name)) {
                        $map[$field] = $col;

                        break 2;
                    }
                }
            }
        }

        return $map;
    }

    /**
     * @param  array<int, array<int, mixed>>  $grid
     * @param  array<string, int>  $map
     * @return array<int, array<string, mixed>>
     */
    private function readRows(array $grid, int $headerRow, array $map): array
    {
        $rows = [];
        $kosongBerturut = 0;

        $medan = ['kpi', 'target', 'tactics', 'initiatives', 'timeline', 'pic'];

        foreach (array_slice($grid, $headerRow + 1, null, true) as $i => $row) {
            $kra = $this->text($row, $map['kra'] ?? null);

            if ($kra === null) {
                /*
                 * SEL BERGABUNG.
                 *
                 * Lead Management memegang dua sasaran — 150 seminggu dan
                 * 25 sehari — dengan KRA, KPI dan PIC digabung menegak
                 * merentasi kedua-dua baris. Google memulangkan sel
                 * bergabung hanya pada baris pertamanya, jadi baris kedua
                 * tiba dengan KRA kosong dan hanya sasaran diisi.
                 *
                 * Melangkaunya sebagai baris kosong akan membuang sasaran
                 * itu secara senyap, dan "25 lead / hari" ialah antara
                 * nombor yang paling kerap disebut dalam pelan ini.
                 */
                $sambungan = [];

                foreach ($medan as $field) {
                    $nilai = $this->text($row, $map[$field] ?? null);

                    if ($nilai !== null) {
                        $sambungan[$field] = $nilai;
                    }
                }

                if ($sambungan !== [] && $rows !== []) {
                    $akhir = array_key_last($rows);

                    foreach ($sambungan as $field => $nilai) {
                        $rows[$akhir][$field] = trim(($rows[$akhir][$field] ?? '')."\n".$nilai);
                    }

                    $kosongBerturut = 0;

                    continue;
                }

                /*
                 * Satu baris kosong di tengah jadual biasanya jarak visual,
                 * bukan penghujung. Dua berturut-turut bermakna jadual
                 * sudah tamat dan apa yang berikutnya ialah nota kaki.
                 */
                if (++$kosongBerturut >= 2) {
                    break;
                }

                continue;
            }

            $kosongBerturut = 0;

            $rows[] = [
                'kra' => $kra,
                'kpi' => $this->text($row, $map['kpi'] ?? null),
                'target' => $this->text($row, $map['target'] ?? null),
                'tactics' => $this->text($row, $map['tactics'] ?? null),
                'initiatives' => $this->text($row, $map['initiatives'] ?? null),
                'timeline' => $this->text($row, $map['timeline'] ?? null),
                'pic' => $this->pic($this->text($row, $map['pic'] ?? null)),
                'source_row' => $i + 1,
            ];
        }

        return $rows;
    }

    /**
     * Petak ringkasan, dibaca daripada baris DI ATAS jadual utama.
     *
     * @param  array<int, array<int, mixed>>  $grid
     * @return array<int, array<string, mixed>>
     */
    private function readTiles(array $grid, int $headerRow): array
    {
        $atas = array_slice($grid, 0, $headerRow, true);

        foreach ($atas as $i => $row) {
            $map = $this->mapColumns($row, self::TILE_HEADERS);

            if (! isset($map['label'], $map['value'])) {
                continue;
            }

            $tiles = [];

            foreach (array_slice($grid, $i + 1, $headerRow - $i - 1, true) as $r) {
                $label = $this->text($r, $map['label']);
                $value = $this->text($r, $map['value']);

                if ($label === null || $value === null) {
                    continue;
                }

                $tiles[] = [
                    'label' => $label,
                    'value' => $value,
                    'unit' => $this->text($r, $map['unit'] ?? null),
                    'icon' => $this->icon($label),
                ];
            }

            if ($tiles !== []) {
                return $tiles;
            }
        }

        return [];
    }

    /**
     * Visi daripada lajur Vision.
     *
     * Sel itu bergabung menegak merentasi setiap baris KRA, jadi Google
     * memulangkan teksnya pada baris data PERTAMA sahaja dan setiap baris
     * selepasnya tiba kosong.
     *
     * @param  array<int, array<int, mixed>>  $grid
     * @param  array<string, int>  $map
     */
    private function columnVision(array $grid, int $headerRow, array $map): ?string
    {
        if (! isset($map['vision'])) {
            return null;
        }

        foreach (array_slice($grid, $headerRow + 1, 15) as $row) {
            $nilai = $this->text($row, $map['vision']);

            if ($nilai !== null) {
                return $nilai;
            }
        }

        return null;
    }

    /**
     * Petak ringkasan diterbitkan daripada lajur Target.
     *
     * Reka bentuk asal membina lapan petaknya daripada sasaran yang sama
     * yang tersenarai dalam jadual. Menyalin nombor itu ke jalur berasingan
     * dalam sheet akan mencipta sumber kedua yang mesti dikemas kini dua
     * kali — dan yang akan menyimpang pada kali pertama seseorang lupa.
     *
     * Hanya sasaran dengan nilai boleh ukur menjadi petak. "Project siap
     * awal dari jadual" ialah komitmen, bukan nombor untuk dipaparkan
     * besar-besar, dan reka bentuk asal turut meninggalkannya.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function tilesFromTargets(array $rows): array
    {
        $tiles = [];

        foreach ($rows as $row) {
            $label = $row['kpi'] ?? $row['kra'];

            // Sel bergabung menghasilkan beberapa sasaran dalam satu sel.
            // Lead Management membawa 150 seminggu DAN 25 sehari.
            foreach (preg_split('/\r?\n/', (string) ($row['target'] ?? '')) as $baris) {
                $baris = trim($baris);

                if ($baris === '') {
                    continue;
                }

                $pecah = $this->splitTarget($baris);

                if ($pecah === null) {
                    continue;
                }

                $tiles[] = [
                    'label' => $label,
                    'value' => $pecah['value'],
                    'unit' => $pecah['unit'],
                    'icon' => $this->icon($label.' '.$baris),
                ];
            }
        }

        return $tiles;
    }

    /**
     * Pisahkan "RM500,000 / bulan" kepada nilai dan unit.
     *
     * Nilai kekal sebagai teks. "> RM600,000" kehilangan maksudnya sebaik
     * sahaja ia menjadi 600000, dan tiada satu pun nombor ini pernah
     * dikira — ia dipaparkan.
     *
     * @return array{value: string, unit: ?string}|null
     */
    private function splitTarget(string $text): ?array
    {
        $pola = '/^\s*([<>≥≤]?\s*(?:RM\s*)?\d[\d,.]*\s*%?)\s*(.*)$/iu';

        if (! preg_match($pola, $text, $m)) {
            return null;
        }

        $value = trim(preg_replace('/\s+/', ' ', $m[1]));
        $unit = trim($m[2]);

        return ['value' => $value, 'unit' => $unit === '' ? null : $unit];
    }

    /**
     * @param  array<int, array<int, mixed>>  $grid
     */
    private function findHeading(array $grid, int $headerRow): ?string
    {
        foreach (array_slice($grid, 0, $headerRow) as $row) {
            foreach ($row as $cell) {
                $text = trim((string) $cell);

                if ($text !== '' && str_contains($this->norm($cell), 'strategic planning')) {
                    return $text;
                }
            }
        }

        return null;
    }

    /**
     * Teks visi.
     *
     * Diambil daripada sel di SEBELAH KANAN atau DI BAWAH penanda "VISI",
     * kerana kedua-dua susunan itu kelihatan semula jadi dalam sheet dan
     * memaksa satu bermakna sync gagal atas sebab reka letak.
     *
     * @param  array<int, array<int, mixed>>  $grid
     */
    private function findVision(array $grid, int $headerRow): ?string
    {
        foreach (array_slice($grid, 0, $headerRow, true) as $i => $row) {
            foreach ($row as $col => $cell) {
                $text = $this->norm($cell);

                if ($text !== 'visi' && $text !== 'vision' && $text !== 'visi / vision') {
                    continue;
                }

                foreach (array_slice($row, $col + 1) as $kanan) {
                    if (trim((string) $kanan) !== '') {
                        return trim((string) $kanan);
                    }
                }

                foreach (array_slice($grid, $i + 1, 3) as $bawah) {
                    $nilai = trim((string) ($bawah[$col] ?? ''));

                    if ($nilai !== '') {
                        return $nilai;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Nama PIC dibersihkan daripada awalan "HOD :".
     *
     * Sheet menulis "HOD : Zikri" kerana itu rupanya dalam cetakan. Papan
     * memaparkan awalan itu sendiri, jadi menyimpannya dalam nilai
     * menghasilkan "HOD : HOD : Zikri".
     */
    private function pic(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $clean = preg_replace('/^\s*(hod|pic|ci)\s*[:\-]\s*/i', '', $raw);

        return trim((string) $clean) ?: null;
    }

    private function icon(string $label): string
    {
        $text = $this->norm($label);

        foreach (self::ICONS as $kata => $ikon) {
            if (str_contains($text, $kata)) {
                return $ikon;
            }
        }

        return 'ph-target';
    }

    /** @param  array<int, mixed>  $row */
    private function text(array $row, ?int $index): ?string
    {
        if ($index === null) {
            return null;
        }

        $value = trim((string) ($row[$index] ?? ''));

        return $value === '' ? null : $value;
    }

    private function norm(mixed $cell): string
    {
        // Nombor awalan seperti "1." dalam "1. JUALAN BULANAN" dibuang
        // supaya padanan tajuk tidak bergantung pada penomboran.
        $text = mb_strtolower(trim((string) $cell));
        $text = preg_replace('/^\d+\s*[.)]\s*/', '', $text);

        return trim(preg_replace('/\s+/', ' ', (string) $text));
    }

    /** @return array<string, mixed> */
    private function failure(string $message): array
    {
        return ['status' => 'failed', 'message' => $message, 'rows' => 0, 'tiles' => 0];
    }
}
