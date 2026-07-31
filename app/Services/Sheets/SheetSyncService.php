<?php

declare(strict_types=1);

namespace App\Services\Sheets;

use App\Contracts\SheetReader;
use App\Exceptions\SheetReadException;
use App\Models\CriticalMetric;
use App\Models\CriticalMetricMonth;
use App\Models\CriticalMetricTarget;
use App\Models\CriticalWeeklyEntry;
use App\Models\Owner;
use App\Models\Service;
use App\Models\SheetIntegration;
use App\Models\SheetSyncLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Menarik Data Kritikal Mingguan dari Google Sheet ke dalam MySQL.
 *
 * SUSUN ATUR SEBENAR DBENA (mod 'multi') — satu tab memegang semua servis:
 *
 *   baris 1  │ MASUKKAN DATA & REPORT DALAM KOTAK BERWARNA MERAH   ← banner
 *   baris 2  │ DATA CRITICAL │ Week 1 │ … │ Data Owner │ Action Plan ← header
 *   baris 3  │ Renovation                                          ← JALUR SERVIS
 *   baris 4  │ Sales Collection (New) │ … │ ZIKRI │ …              ← metrik
 *      ⋮
 *   baris 14 │ Bina Rumah                                          ← JALUR SERVIS
 *      ⋮
 *
 * Baris jalur dikenal pasti apabila lajur metrik mengandungi nama servis
 * yang dikenali DAN lajur minggu kosong. Dari situ, semua baris metrik
 * berikutnya dimiliki oleh servis tersebut sehingga jalur seterusnya.
 *
 * Aliran data adalah SATU HALA: sheet → dashboard.
 */
class SheetSyncService
{
    /** Corak header yang digunakan untuk auto-kesan baris & lajur. */
    private const HEADER_PATTERNS = [
        'metric' => ['data critical', 'data kritikal', 'critical data', 'metrik', 'metric', 'perkara', 'item', 'kpi'],
        'week1' => ['week 1', 'minggu 1', 'week1', 'minggu1', 'm1', 'w1'],
        'week2' => ['week 2', 'minggu 2', 'week2', 'minggu2', 'm2', 'w2'],
        'week3' => ['week 3', 'minggu 3', 'week3', 'minggu3', 'm3', 'w3'],
        'week4' => ['week 4', 'minggu 4', 'week4', 'minggu4', 'm4', 'w4'],
        'data_type' => ['data type', 'jenis data', 'jenis', 'type'],
        'target' => ['monthly target', 'sasaran bulanan', 'sasaran', 'target'],
        'owner' => ['data owner', 'pemilik data', 'pemilik', 'owner', 'pic'],
        'action_plan' => ['action plan', 'pelan tindakan', 'tindakan', 'plan'],
    ];

    public function __construct(private readonly SheetReader $reader) {}

    // ══ Sync ══════════════════════════════════════════════════════════════

    /**
     * @return array<string, mixed>
     */
    public function sync(
        SheetIntegration $integration,
        int $year,
        int $month,
        string $trigger = 'manual',
        ?int $userId = null,
    ): array {
        $startedAt = microtime(true);

        if (! $integration->isReadyToSync()) {
            return $this->fail($integration, $trigger, $year, $month, $userId, __('sheets.error.not_configured', [
                'fields' => implode(', ', $integration->missingMappings()),
            ]), $startedAt);
        }

        try {
            $grid = $this->reader->read($integration);
        } catch (SheetReadException $e) {
            return $this->fail($integration, $trigger, $year, $month, $userId, $e->getMessage(), $startedAt);
        }

        $result = $this->apply($integration, $grid, $year, $month);

        $status = match (true) {
            $result['rowsMatched'] === 0 => 'failed',
            $result['unmatched'] !== [] => 'partial',
            default => 'success',
        };

        $message = match ($status) {
            'failed' => __('sheets.error.no_rows_matched'),
            'partial' => __('sheets.sync.partial', [
                'matched' => $result['rowsMatched'],
                'skipped' => count($result['unmatched']),
            ]),
            default => __('sheets.sync.success', [
                'rows' => $result['rowsMatched'],
                'values' => $result['valuesUpdated'],
            ]),
        };

        if ($status !== 'failed' && $result['services'] !== []) {
            $message .= ' · '.implode(', ', $result['services']);
        }

        return $this->finish($integration, $trigger, $year, $month, $userId, $status, $message, $result, $startedAt);
    }

    /**
     * @param  array<int, array<int, string>>  $grid
     * @return array<string, mixed>
     */
    private function apply(SheetIntegration $integration, array $grid, int $year, int $month): array
    {
        $map = $integration->column_map ?? [];
        $headerRow = $this->resolveHeaderRow($integration, $grid);
        $dataRows = array_slice($grid, $headerRow);

        $services = Service::orderBy('sort_order')->get();
        $serviceIndex = $this->buildServiceIndex($services);

        // Dalam mod satu-servis, konteks dikunci pada servis integrasi.
        $multi = $integration->isMultiService();
        $currentService = $multi ? null : $integration->service;

        // Cache carian metrik per servis — dibina sekali, digunakan berulang.
        $lookups = [];
        $owners = Owner::active()->get()->keyBy(fn (Owner $o) => $this->normalise($o->name));

        $rowsRead = 0;
        $rowsMatched = 0;
        $valuesUpdated = 0;
        $targetsUpdated = 0;
        $unmatched = [];
        $touchedServices = [];

        DB::transaction(function () use (
            $dataRows, $map, $multi, $serviceIndex, $services, $owners, $integration,
            $year, $month, &$currentService, &$lookups,
            &$rowsRead, &$rowsMatched, &$valuesUpdated, &$targetsUpdated, &$unmatched, &$touchedServices
        ): void {
            foreach ($dataRows as $row) {
                if ($this->isBlankRow($row)) {
                    continue;
                }

                $label = $this->cell($row, $map['metric'] ?? null);

                if (blank($label)) {
                    continue;
                }

                // ── Baris jalur servis? ──
                if ($multi) {
                    $band = $this->matchServiceBand($label, $row, $map, $serviceIndex);

                    if ($band !== null) {
                        $currentService = $band;
                        $touchedServices[$band->id] = $band->name;

                        continue;
                    }
                }

                if (! $currentService) {
                    // Baris metrik sebelum sebarang jalur — tiada konteks servis.
                    $unmatched[] = $label;

                    continue;
                }

                $rowsRead++;

                $lookups[$currentService->id] ??= $this->buildLookup(
                    $currentService->criticalMetrics()->get(),
                    $integration->match_mode ?? 'label'
                );

                $metric = $lookups[$currentService->id][$this->normalise($label)] ?? null;

                if (! $metric) {
                    $unmatched[] = $currentService->name.' › '.$label;

                    continue;
                }

                $rowsMatched++;
                $touchedServices[$currentService->id] = $currentService->name;

                // ── Nilai mingguan ──
                for ($week = 1; $week <= 4; $week++) {
                    $value = $this->toNumber($this->cell($row, $map["week{$week}"] ?? null));

                    $entry = CriticalWeeklyEntry::updateOrCreate(
                        [
                            'critical_metric_id' => $metric->id,
                            'year' => $year,
                            'month' => $month,
                            'week_number' => $week,
                        ],
                        ['value' => $value]
                    );

                    if ($entry->wasRecentlyCreated || $entry->wasChanged('value')) {
                        $valuesUpdated++;
                    }
                }

                // ── Sasaran bulanan (opt-in) ──
                if ($integration->import_targets && filled($map['target'] ?? null)) {
                    if ($this->importTarget($metric, $row, $map, $year)) {
                        $targetsUpdated++;
                    }
                }

                // ── Metadata bulanan ──
                $meta = [];

                if (filled($map['owner'] ?? null)) {
                    $ownerName = $this->cell($row, $map['owner']);

                    if (filled($ownerName)) {
                        $owner = $owners[$this->normalise($ownerName)] ?? null;

                        if ($owner) {
                            $meta['owner_id'] = $owner->id;
                        }
                    }
                }

                if (filled($map['action_plan'] ?? null)) {
                    $plan = $this->cell($row, $map['action_plan']);
                    $meta['action_plan'] = filled($plan) ? $plan : null;
                }

                if ($meta !== []) {
                    CriticalMetricMonth::updateOrCreate(
                        ['critical_metric_id' => $metric->id, 'year' => $year, 'month' => $month],
                        $meta
                    );
                }
            }
        });

        $integration->forceFill(['detected_services' => array_values($touchedServices)])->save();

        return [
            'rowsRead' => $rowsRead,
            'rowsMatched' => $rowsMatched,
            'valuesUpdated' => $valuesUpdated,
            'targetsUpdated' => $targetsUpdated,
            'unmatched' => array_values(array_unique($unmatched)),
            'services' => array_values($touchedServices),
        ];
    }

    /**
     * Lajur "Monthly Target" — nombor disimpan sebagai sasaran, teks seperti
     * "Progress" disimpan sebagai target_text (tidak boleh dinilai peratus).
     */
    private function importTarget(CriticalMetric $metric, array $row, array $map, int $year): bool
    {
        $raw = $this->cell($row, $map['target']);

        if (blank($raw)) {
            return false;
        }

        $numeric = $this->toNumber($raw);

        $target = CriticalMetricTarget::firstOrNew([
            'critical_metric_id' => $metric->id,
            'year' => $year,
        ]);

        $before = [$target->monthly_target, $target->target_text];

        $target->fill($numeric !== null
            ? ['monthly_target' => $numeric, 'target_text' => null]
            : ['monthly_target' => null, 'target_text' => $raw]);

        if ([$target->monthly_target, $target->target_text] === $before && $target->exists) {
            return false;
        }

        $target->save();

        return true;
    }

    // ══ Pengesanan jalur servis ═══════════════════════════════════════════

    /**
     * Baris ialah jalur servis apabila teks lajur metrik memadani nama servis
     * DAN baris itu tiada nilai mingguan (jalur bersifat tajuk sahaja).
     *
     * @param  array<string, Service>  $serviceIndex
     */
    private function matchServiceBand(string $label, array $row, array $map, array $serviceIndex): ?Service
    {
        $service = $serviceIndex[$this->normalise($label)] ?? null;

        if (! $service) {
            return null;
        }

        // Jika baris ini turut membawa data mingguan, ia metrik — bukan jalur.
        for ($week = 1; $week <= 4; $week++) {
            if (filled($this->cell($row, $map["week{$week}"] ?? null))) {
                return null;
            }
        }

        return $service;
    }

    /**
     * @param  Collection<int, Service>  $services
     * @return array<string, Service>
     */
    private function buildServiceIndex(Collection $services): array
    {
        $index = [];

        foreach ($services as $service) {
            foreach ([$service->name_ms, $service->name_en, $service->key, str_replace('-', ' ', $service->key)] as $alias) {
                if (filled($alias)) {
                    $index[$this->normalise($alias)] ??= $service;
                }
            }
        }

        return $index;
    }

    // ══ Auto-kesan baris header ═══════════════════════════════════════════

    /**
     * Cari baris yang paling menyerupai header.
     *
     * Sheet DBENA bermula dengan banner arahan pada baris 1, jadi mengandaikan
     * baris 1 = header akan gagal. Kami menjaringkan 10 baris pertama mengikut
     * bilangan corak header yang dipadankan.
     *
     * @param  array<int, array<int, string>>  $grid
     */
    public function detectHeaderRow(array $grid, int $scanLimit = 10): int
    {
        $bestRow = 1;
        $bestScore = 0;

        foreach (array_slice($grid, 0, $scanLimit) as $index => $row) {
            $score = 0;

            foreach ($row as $cell) {
                $normalised = $this->normalise($cell);

                if ($normalised === '') {
                    continue;
                }

                foreach (self::HEADER_PATTERNS as $needles) {
                    foreach ($needles as $needle) {
                        if (str_starts_with($normalised, $this->normalise($needle))) {
                            $score++;

                            break 2;
                        }
                    }
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRow = $index + 1;
            }
        }

        // Perlukan sekurang-kurangnya 3 padanan sebelum mempercayai tekaan.
        return $bestScore >= 3 ? $bestRow : 1;
    }

    private function resolveHeaderRow(SheetIntegration $integration, array $grid): int
    {
        $configured = (int) ($integration->header_row ?: 0);

        return $configured > 0 ? $configured : $this->detectHeaderRow($grid);
    }

    // ══ Pratonton ═════════════════════════════════════════════════════════

    /**
     * Papar apa yang sync AKAN lakukan, tanpa menulis apa-apa.
     *
     * @return array<string, mixed>
     */
    public function preview(SheetIntegration $integration, int $limit = 40): array
    {
        $grid = $this->reader->read($integration);

        $headerRow = $this->resolveHeaderRow($integration, $grid);
        $headers = $grid[$headerRow - 1] ?? [];
        $map = $integration->column_map ?? [];
        $multi = $integration->isMultiService();

        $services = Service::orderBy('sort_order')->get();
        $serviceIndex = $this->buildServiceIndex($services);
        $lookups = [];

        $currentService = $multi ? null : $integration->service;
        $rows = [];
        $detected = [];

        foreach (array_slice($grid, $headerRow) as $raw) {
            if (count($rows) >= $limit) {
                break;
            }

            if ($this->isBlankRow($raw)) {
                continue;
            }

            $label = $this->cell($raw, $map['metric'] ?? null);

            if (blank($label)) {
                continue;
            }

            if ($multi) {
                $band = $this->matchServiceBand($label, $raw, $map, $serviceIndex);

                if ($band !== null) {
                    $currentService = $band;
                    $detected[$band->id] = $band->name;
                    $rows[] = ['type' => 'band', 'label' => $label, 'service' => $band->name];

                    continue;
                }
            }

            $metric = null;

            if ($currentService) {
                $lookups[$currentService->id] ??= $this->buildLookup(
                    $currentService->criticalMetrics()->get(),
                    $integration->match_mode ?? 'label'
                );
                $metric = $lookups[$currentService->id][$this->normalise($label)] ?? null;
            }

            $rows[] = [
                'type' => 'metric',
                'label' => $label,
                'service' => $currentService?->name,
                'matched' => $metric !== null,
                'matchedTo' => $metric?->label,
                'weeks' => collect(range(1, 4))
                    ->mapWithKeys(fn (int $w) => [$w => $this->toNumber($this->cell($raw, $map["week{$w}"] ?? null))])
                    ->all(),
                'target' => filled($map['target'] ?? null) ? $this->cell($raw, $map['target']) : null,
                'owner' => filled($map['owner'] ?? null) ? $this->cell($raw, $map['owner']) : null,
            ];
        }

        return [
            'headers' => $headers,
            'headerRow' => $headerRow,
            'columnLetters' => $this->columnLetters(max(count($headers), 12)),
            'rows' => $rows,
            'totalRows' => count($grid),
            'detectedServices' => array_values($detected),
            'suggestions' => $this->suggestMapping($headers),
        ];
    }

    /**
     * Teka pemetaan lajur daripada teks header.
     *
     * @param  array<int, string>  $headers
     * @return array<string, string>
     */
    public function suggestMapping(array $headers): array
    {
        $suggestions = [];

        foreach ($headers as $index => $header) {
            $normalised = $this->normalise($header);

            if ($normalised === '') {
                continue;
            }

            foreach (self::HEADER_PATTERNS as $field => $needles) {
                if (isset($suggestions[$field])) {
                    continue;
                }

                foreach ($needles as $needle) {
                    if (str_starts_with($normalised, $this->normalise($needle))) {
                        $suggestions[$field] = $this->indexToLetter($index);

                        break 2;
                    }
                }
            }
        }

        return $suggestions;
    }

    // ══ Pembantu ══════════════════════════════════════════════════════════

    /**
     * @param  Collection<int, CriticalMetric>  $metrics
     * @return array<string, CriticalMetric>
     */
    private function buildLookup(Collection $metrics, string $mode): array
    {
        $lookup = [];

        foreach ($metrics as $metric) {
            if ($mode === 'key') {
                $lookup[$this->normalise($metric->metric_key)] = $metric;

                continue;
            }

            foreach ([$metric->label_ms, $metric->label_en, $metric->metric_key] as $candidate) {
                if (filled($candidate)) {
                    $lookup[$this->normalise($candidate)] ??= $metric;
                }
            }
        }

        return $lookup;
    }

    /**
     * Normalkan untuk padanan longgar: huruf kecil, buang tanda baca,
     * runtuhkan ruang. "No. of New Quotation " → "no of new quotation"
     */
    private function normalise(?string $value): string
    {
        $value = Str::lower(trim((string) $value));
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    /** Baca sel mengikut huruf lajur ('A', 'C', 'AA') atau indeks berangka. */
    private function cell(array $row, string|int|null $column): string
    {
        if ($column === null || $column === '') {
            return '';
        }

        $index = is_numeric($column) ? (int) $column : $this->letterToIndex((string) $column);

        return trim((string) ($row[$index] ?? ''));
    }

    /**
     * Tukar teks sel kepada nombor.
     * Mengendalikan "RM12,500.00", "12 500", "1.234,56" (EU), "(500)", "-", "N/A".
     */
    private function toNumber(string $raw): ?float
    {
        $raw = trim($raw);

        if ($raw === '' || in_array(Str::lower($raw), ['-', '—', 'n/a', 'na', 'nil', 'tiada', 'progress'], true)) {
            return null;
        }

        $negative = str_starts_with($raw, '(') && str_ends_with($raw, ')');
        $clean = preg_replace('/[^\d.,\-]/', '', $raw) ?? '';

        if ($clean === '' || $clean === '-') {
            return null;
        }

        if (preg_match('/,\d{1,2}$/', $clean) && str_contains($clean, '.')) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } else {
            $clean = str_replace(',', '', $clean);
        }

        if (! is_numeric($clean)) {
            return null;
        }

        $value = (float) $clean;

        return $negative ? -abs($value) : $value;
    }

    private function isBlankRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function letterToIndex(string $letter): int
    {
        $letter = strtoupper(preg_replace('/[^A-Za-z]/', '', $letter) ?? '');
        $index = 0;

        foreach (str_split($letter) as $char) {
            $index = $index * 26 + (ord($char) - 64);
        }

        return max(0, $index - 1);
    }

    private function indexToLetter(int $index): string
    {
        $letter = '';
        $index++;

        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod).$letter;
            $index = (int) (($index - $mod) / 26);
        }

        return $letter;
    }

    /** @return array<int, string> */
    private function columnLetters(int $count): array
    {
        return collect(range(0, max(0, $count - 1)))
            ->map(fn (int $i) => $this->indexToLetter($i))
            ->all();
    }

    // ══ Pencatatan ════════════════════════════════════════════════════════

    private function fail(
        SheetIntegration $integration, string $trigger, int $year, int $month,
        ?int $userId, string $message, float $startedAt,
    ): array {
        return $this->finish($integration, $trigger, $year, $month, $userId, 'failed', $message, [
            'rowsRead' => 0, 'rowsMatched' => 0, 'valuesUpdated' => 0,
            'targetsUpdated' => 0, 'unmatched' => [], 'services' => [],
        ], $startedAt);
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function finish(
        SheetIntegration $integration, string $trigger, int $year, int $month,
        ?int $userId, string $status, string $message, array $result, float $startedAt,
    ): array {
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        SheetSyncLog::create([
            'sheet_integration_id' => $integration->id,
            'service_id' => $integration->service_id,
            'trigger' => $trigger,
            'status' => $status,
            'year' => $year,
            'month' => $month,
            'rows_read' => $result['rowsRead'],
            'rows_matched' => $result['rowsMatched'],
            'values_updated' => $result['valuesUpdated'],
            'rows_skipped' => count($result['unmatched']),
            'unmatched_labels' => $result['unmatched'] ?: null,
            'message' => $message,
            'duration_ms' => $durationMs,
            'triggered_by' => $userId,
            'created_at' => now(),
        ]);

        $integration->forceFill([
            'last_synced_at' => now(),
            'last_sync_status' => $status,
            'last_sync_message' => $message,
            'last_sync_rows' => $result['rowsMatched'],
            'connected' => $status !== 'failed' ? true : $integration->connected,
        ])->save();

        return ['status' => $status, 'message' => $message, 'durationMs' => $durationMs] + $result;
    }
}
