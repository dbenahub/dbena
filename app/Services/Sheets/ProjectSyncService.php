<?php

declare(strict_types=1);

namespace App\Services\Sheets;

use App\Contracts\SheetReader;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Service;
use App\Models\SheetIntegration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Menyegerak tab Master Project daripada Google Sheet.
 *
 * Berasingan daripada SheetSyncService kerana bentuk datanya berbeza
 * sepenuhnya. Data Kritikal ialah metrik dalam jalur servis dengan empat
 * lajur mingguan; Master Project ialah satu baris satu projek dengan
 * lajur pelanggan. Cuba mengendalikan kedua-duanya dalam satu kelas
 * bermakna setiap kaedah bermula dengan pemeriksaan mod.
 *
 * SHEET IALAH SATU-SATUNYA PENULIS. Padanan dibuat mengikut kod projek,
 * jadi membetulkan baris dalam sheet mengemas kini rekod yang sama di
 * sini dan bukan mencipta pendua.
 */
class ProjectSyncService
{
    /** Tajuk yang dikenali bagi setiap medan. */
    private const HEADERS = [
        'code' => ['project code', 'kod projek', 'code', 'kod', 'no projek'],
        'date' => ['date', 'tarikh'],
        'client_name' => ['client name', 'nama klien', 'nama pelanggan', 'client', 'pelanggan'],
        'pic_sales' => ['pic sales', 'pic', 'sales person', 'salesperson'],
        'service' => ['type of project', 'jenis projek', 'category', 'kategori', 'servis', 'service'],
        'phone' => ['client phone', 'phone', 'telefon', 'whatsapp', 'no telefon'],
        'address' => ['address', 'alamat'],
        'email' => ['email', 'emel', 'e-mail'],
        'contract_amount' => ['contract amount', 'jumlah kontrak', 'contract', 'amount'],
        'variation_order' => ['variation order', 'vo', 'variation'],
        'status' => ['status'],
    ];

    /** Medan tanpa nilai bermakna baris itu tidak boleh digunakan. */
    private const REQUIRED = ['code', 'client_name'];

    public function __construct(private readonly SheetReader $reader) {}

    /**
     * @return array<string, mixed>
     */
    public function sync(SheetIntegration $integration): array
    {
        try {
            $grid = $this->reader->read($integration);
        } catch (Throwable $e) {
            return $this->failure(__('project.sync.read_failed', ['message' => $e->getMessage()]));
        }

        $headerRow = $this->resolveHeaderRow($integration, $grid);
        $map = $this->resolveMap($integration, $grid[$headerRow - 1] ?? []);

        foreach (self::REQUIRED as $field) {
            if (! isset($map[$field])) {
                return $this->failure(__('project.sync.missing_column', [
                    'field' => __('project.field.'.$field),
                ]));
            }
        }

        $services = $this->serviceIndex();
        $lalai = $integration->service_id;

        $written = 0;
        $skipped = 0;
        $unknown = [];

        DB::transaction(function () use (
            $grid, $headerRow, $map, $services, $lalai, &$written, &$skipped, &$unknown
        ): void {
            foreach (array_slice($grid, $headerRow) as $offset => $row) {
                $baris = $headerRow + $offset + 1;

                $code = trim((string) $this->cell($row, $map['code']));
                $client = trim((string) $this->cell($row, $map['client_name']));

                // Baris tanpa kod ATAU tanpa nama klien bukan projek — ia
                // baris kosong, baris jumlah, atau nota. Melangkaunya
                // secara senyap lebih baik daripada mencipta rekod hantu
                // yang muncul dalam kiraan.
                if ($code === '' || $client === '') {
                    $skipped++;

                    continue;
                }

                $namaServis = trim((string) $this->cell($row, $map['service'] ?? null));
                $serviceId = $services[$this->normalise($namaServis)] ?? $lalai;

                if ($serviceId === null) {
                    // Tanpa servis, projek tidak boleh muncul di bawah
                    // mana-mana kategori. Dilaporkan dan bukan diteka.
                    $unknown[$namaServis !== '' ? $namaServis : '—'] = true;
                    $skipped++;

                    continue;
                }

                Project::updateOrCreate(
                    ['code' => $code],
                    [
                        'service_id' => $serviceId,
                        'project_date' => $this->date($this->cell($row, $map['date'] ?? null)),
                        'client_name' => $client,
                        'pic_sales' => $this->text($row, $map['pic_sales'] ?? null),
                        'phone' => $this->text($row, $map['phone'] ?? null),
                        'email' => $this->text($row, $map['email'] ?? null),
                        'address' => $this->text($row, $map['address'] ?? null),
                        'contract_amount' => $this->money($this->cell($row, $map['contract_amount'] ?? null)),
                        'variation_order' => $this->money($this->cell($row, $map['variation_order'] ?? null)),
                        'status' => ProjectStatus::fromSheet($this->text($row, $map['status'] ?? null)),
                        'source_row' => $baris,
                        'synced_at' => now(),
                    ]
                );

                $written++;
            }
        });

        return [
            'status' => $written > 0 ? 'success' : 'failed',
            'message' => $written > 0
                ? __('project.sync.done', ['written' => $written, 'skipped' => $skipped])
                : __('project.sync.nothing'),
            'written' => $written,
            'skipped' => $skipped,
            'unknownServices' => array_keys($unknown),
        ];
    }

    /** @param  array<int, mixed>  $row */
    private function cell(array $row, ?int $index): mixed
    {
        return $index === null ? null : ($row[$index] ?? null);
    }

    /** @param  array<int, mixed>  $row */
    private function text(array $row, ?int $index): ?string
    {
        $value = trim((string) $this->cell($row, $index));

        return $value === '' ? null : $value;
    }

    /**
     * Nombor daripada sel yang mungkin membawa "RM 85,000.00".
     *
     * Sheet diisi oleh manusia, jadi simbol mata wang, koma dan ruang
     * bukan sesuatu yang boleh diandaikan tiada.
     */
    private function money(mixed $raw): float
    {
        $clean = preg_replace('/[^0-9.\-]/', '', (string) $raw);

        return $clean === '' || $clean === '-' ? 0.0 : (float) $clean;
    }

    /**
     * Tarikh daripada sel yang mungkin dalam apa-apa format.
     *
     * Google menghantar "01 May 2024", "2024-05-01" atau nombor siri
     * bergantung pada pemformatan sel. Tarikh yang gagal dihuraikan
     * menjadi null dan bukan hari ini — tarikh palsu yang kelihatan
     * munasabah lebih teruk daripada tiada tarikh.
     */
    private function date(mixed $raw): ?Carbon
    {
        $value = trim((string) $raw);

        if ($value === '') {
            return null;
        }

        // Nombor siri Google Sheets: hari sejak 30 Disember 1899.
        if (preg_match('/^\d{5}$/', $value)) {
            return Carbon::create(1899, 12, 30)->addDays((int) $value);
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array<string, mixed> */
    private function failure(string $message): array
    {
        return ['status' => 'failed', 'message' => $message, 'written' => 0, 'skipped' => 0];
    }

    /** @return array<string, int> nama dinormalkan -> id servis */
    private function serviceIndex(): array
    {
        $index = [];

        foreach (Service::all() as $service) {
            foreach ([$service->name_ms, $service->name_en, $service->key] as $name) {
                $index[$this->normalise((string) $name)] = $service->id;
            }
        }

        return $index;
    }

    private function normalise(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtolower(strip_tags($value))) ?? '');
    }

    /**
     * @param  array<int, mixed>  $headers
     * @return array<string, int>
     */
    private function resolveMap(SheetIntegration $integration, array $headers): array
    {
        $saved = $integration->column_map ?? [];
        $map = [];

        foreach (self::HEADERS as $field => $needles) {
            if (isset($saved[$field]) && $saved[$field] !== '') {
                $map[$field] = $this->columnIndex((string) $saved[$field]);

                continue;
            }

            foreach ($headers as $i => $header) {
                $normalised = $this->normalise((string) $header);

                foreach ($needles as $needle) {
                    if ($normalised !== '' && str_starts_with($normalised, $needle)) {
                        $map[$field] = $i;

                        continue 3;
                    }
                }
            }
        }

        return $map;
    }

    /** "A" -> 0, "AB" -> 27. */
    private function columnIndex(string $letter): int
    {
        $letter = mb_strtoupper(trim($letter));

        if ($letter === '' || ! preg_match('/^[A-Z]+$/', $letter)) {
            return (int) $letter;
        }

        $index = 0;

        foreach (str_split($letter) as $char) {
            $index = $index * 26 + (ord($char) - 64);
        }

        return $index - 1;
    }

    /** @param  array<int, array<int, mixed>>  $grid */
    private function resolveHeaderRow(SheetIntegration $integration, array $grid): int
    {
        $configured = (int) ($integration->header_row ?: 0);

        if ($configured > 0) {
            return $configured;
        }

        $best = 1;
        $bestScore = 0;

        foreach (array_slice($grid, 0, 10) as $i => $row) {
            $score = 0;

            foreach ($row as $cell) {
                $normalised = $this->normalise((string) $cell);

                if ($normalised === '') {
                    continue;
                }

                foreach (self::HEADERS as $needles) {
                    foreach ($needles as $needle) {
                        if (str_starts_with($normalised, $needle)) {
                            $score++;

                            continue 3;
                        }
                    }
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $i + 1;
            }
        }

        return $bestScore >= 3 ? $best : 1;
    }
}
