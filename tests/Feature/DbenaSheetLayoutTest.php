<?php

declare(strict_types=1);

use App\Contracts\SheetReader;
use App\Enums\UserRole;
use App\Models\CriticalMetric;
use App\Models\CriticalMetricMonth;
use App\Models\CriticalWeeklyEntry;
use App\Models\Owner;
use App\Models\Service;
use App\Models\SheetIntegration;
use App\Models\User;
use App\Services\Sheets\SheetSyncService;

/*
|--------------------------------------------------------------------------
| Susun atur sheet DBENA sebenar
|--------------------------------------------------------------------------
| Salinan tepat daripada tangkapan skrin: banner arahan pada baris 1, header
| dua-baris pada baris 2, kemudian jalur servis diikuti baris metriknya.
|
|   A DATA CRITICAL │ B-E Week 1-4 │ F Data Type │ G Monthly Actual
|   H Monthly Target │ I Data Status │ J Data Owner │ K Action Plan
*/

function dbenaGrid(array $overrides = []): array
{
    $grid = [
        // Baris 1 — banner arahan merentasi sheet
        ['', 'MASUKKAN DATA & REPORT DALAM KOTAK BERWARNA MERAH', '', '', '', '', '', '', '', '', ''],

        // Baris 2 — header (label minggu membawa julat tarikh pada baris kedua sel)
        [
            'DATA CRITICAL',
            "Week 1\n[01/07-09/07]", "Week 2\n[10/07-16/07]",
            "Week 3\n[17/07-23/07]", "Week 4\n[24/07-31/07]",
            'Data Type', 'Monthly Actual', 'Monthly Target', 'Data Status', 'Data Owner', 'Action Plan',
        ],

        // Baris 3 — JALUR SERVIS
        ['Renovation', '', '', '', '', '', '', '', '', '', ''],

        // Baris 4-13 — metrik Renovation
        ['Sales Collection (New)', '', '', '', '', 'Total', 'RM0.00', 'RM150,000.00', 'Red', 'ZIKRI', ''],
        ['Revenue/Sales', '', '', '', '', 'Total', 'RM0.00', 'RM500,000.00', 'Red', 'ZIKRI', ''],
        ['Sales Collection (Progress Claim)', '', '', '', '', 'Total', 'RM0.00', 'Progress', 'Red', 'HAFIZAN', ''],
        ['Amount Quotation Release (New)', '', '', '', '', 'Total', 'RM0.00', 'RM2,400,000.00', 'Red', 'HAFIZAN', ''],
        ['No of New Quotation', '', '', '', '', 'Total', '0', '16', 'Red', 'HAFIZAN', ''],
        ['No of Site Visit', '', '', '', '', 'Total', '0', '24', 'Red', 'ZIKRI', ''],
        ['Ads Spend', '', '', '', '', 'Total', 'RM0.00', 'RM6,000.00', 'Red', 'ZIKRI', ''],
        ['No of Lead', '', '', '', '', 'Total', '0', '600', 'Red', 'ZIKRI', ''],
        ['Cost Per Lead (CPL)', '', '', '', '', 'Avg', 'RM0.00', 'RM10.00', 'Red', 'Info', ''],
        ['Cost Per Appointment (CPA)', '', '', '', '', 'Avg', 'RM0.00', 'RM250.00', 'Red', 'Info', ''],

        // Baris 14 — JALUR SERVIS seterusnya
        ['Bina Rumah', '', '', '', '', '', '', '', '', '', ''],
        ['Sales Collection (New)', '', '', '', '', 'Total', 'RM0.00', 'RM150,000.00', 'Red', 'ZIKRI', ''],
        ['Revenue/Sales', '', '', '', '', 'Total', 'RM0.00', 'RM500,000.00', 'Red', 'ZIKRI', ''],
        ['No of New Quotation', '', '', '', '', 'Total', '0', '20', 'Red', 'HAFIZAN', ''],
    ];

    foreach ($overrides as $rowIndex => $cells) {
        foreach ($cells as $colIndex => $value) {
            $grid[$rowIndex][$colIndex] = $value;
        }
    }

    return $grid;
}

function bindDbenaGrid(array $grid): void
{
    app()->bind(SheetReader::class, fn () => new class($grid) implements SheetReader
    {
        public function __construct(private array $grid) {}

        public function read(SheetIntegration $integration): array
        {
            return $this->grid;
        }

        public function label(): string
        {
            return 'DBENA Fake';
        }
    });
}

beforeEach(function (): void {
    $this->seed();

    $this->admin = User::where('role', UserRole::Admin)->firstOrFail();

    // Sheet GLOBAL — satu tab, semua servis (service_id NULL).
    $this->integration = SheetIntegration::updateOrCreate(['service_id' => null], [
        'url' => 'https://docs.google.com/spreadsheets/d/abc123456789012345678/edit',
        'spreadsheet_id' => 'abc123456789012345678',
        'sync_enabled' => true,
        'layout_mode' => 'multi',
        'header_row' => 0, // 0 = auto-kesan
        'match_mode' => 'label',
        'column_map' => [
            'metric' => 'A', 'week1' => 'B', 'week2' => 'C', 'week3' => 'D', 'week4' => 'E',
            'target' => 'H', 'owner' => 'J', 'action_plan' => 'K',
        ],
    ]);
});

/*
|--------------------------------------------------------------------------
| Auto-kesan header
|--------------------------------------------------------------------------
*/

it('looks past the instruction banner and finds the real header row', function (): void {
    $detected = app(SheetSyncService::class)->detectHeaderRow(dbenaGrid());

    expect($detected)->toBe(2);
});

it('suggests the full DBENA column mapping from the header row', function (): void {
    $suggestions = app(SheetSyncService::class)->suggestMapping(dbenaGrid()[1]);

    expect($suggestions)->toMatchArray([
        'metric' => 'A',
        'week1' => 'B',
        'week2' => 'C',
        'week3' => 'D',
        'week4' => 'E',
        'data_type' => 'F',
        'target' => 'H',
        'owner' => 'J',
        'action_plan' => 'K',
    ]);
});

/*
|--------------------------------------------------------------------------
| Jalur servis
|--------------------------------------------------------------------------
*/

it('splits one tab into multiple services using the band rows', function (): void {
    bindDbenaGrid(dbenaGrid([
        3 => [1 => '50000', 2 => '60000'],   // Renovation › Sales Collection (New)
        14 => [1 => '90000'],                 // Bina Rumah › Sales Collection (New)
    ]));

    $result = app(SheetSyncService::class)->sync($this->integration, 2026, 7, 'manual', $this->admin->id);

    expect($result['status'])->toBe('success')
        ->and($result['services'])->toContain('Renovation', 'Bina Rumah');
});

it('files each row under the service band above it', function (): void {
    bindDbenaGrid(dbenaGrid([
        3 => [1 => '50000'],    // Renovation
        14 => [1 => '90000'],   // Bina Rumah — metrik bernama SAMA
    ]));

    app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    $renovation = Service::where('key', 'renovation')->firstOrFail()->metricByKey('sales_collection_new');
    $binaRumah = Service::where('key', 'bina-rumah')->firstOrFail()->metricByKey('sales_collection_new');

    $value = fn (CriticalMetric $m) => (float) CriticalWeeklyEntry::where('critical_metric_id', $m->id)
        ->where('year', 2026)->where('month', 7)->where('week_number', 1)->value('value');

    expect($value($renovation))->toBe(50000.0)
        ->and($value($binaRumah))->toBe(90000.0);
});

it('does not treat a band row as a metric', function (): void {
    bindDbenaGrid(dbenaGrid([3 => [1 => '1000']]));

    $result = app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    // 13 baris metrik dalam grid; jalur "Renovation"/"Bina Rumah" tidak dikira.
    expect($result['rowsRead'])->toBe(13)
        ->and($result['unmatched'])->not->toContain('Renovation');
});

it('records which services it found', function (): void {
    bindDbenaGrid(dbenaGrid([3 => [1 => '100']]));

    app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    expect($this->integration->fresh()->detected_services)
        ->toContain('Renovation', 'Bina Rumah');
});

/*
|--------------------------------------------------------------------------
| Nilai dan format sebenar dalam sheet
|--------------------------------------------------------------------------
*/

it('reads RM-formatted weekly values', function (): void {
    bindDbenaGrid(dbenaGrid([
        4 => [1 => 'RM125,400.00', 2 => 'RM98,000', 3 => '', 4 => 'RM12,500.50'],
    ]));

    app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    $metric = Service::where('key', 'renovation')->firstOrFail()->metricByKey('revenue_sales');

    $values = CriticalWeeklyEntry::where('critical_metric_id', $metric->id)
        ->where('year', 2026)->where('month', 7)->orderBy('week_number')->pluck('value')->all();

    expect((float) $values[0])->toBe(125400.0)
        ->and((float) $values[1])->toBe(98000.0)
        ->and($values[2])->toBeNull()
        ->and((float) $values[3])->toBe(12500.5);
});

it('matches all ten Renovation metrics exactly as they are written in the sheet', function (): void {
    bindDbenaGrid(dbenaGrid(array_fill_keys(range(3, 12), [1 => '1'])));

    $result = app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    $renovationUnmatched = collect($result['unmatched'])->filter(fn (string $l) => str_starts_with($l, 'Renovation'));

    expect($renovationUnmatched)->toBeEmpty()
        ->and($result['rowsMatched'])->toBe(13);
});

it('imports the Data Owner column including the Info label', function (): void {
    bindDbenaGrid(dbenaGrid([3 => [1 => '100'], 11 => [1 => '5']]));

    app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    $service = Service::where('key', 'renovation')->firstOrFail();

    $ownerOf = fn (string $key) => CriticalMetricMonth::where('critical_metric_id', $service->metricByKey($key)->id)
        ->where('year', 2026)->where('month', 7)->value('owner_id');

    expect($ownerOf('sales_collection_new'))->toBe(Owner::where('name', 'ZIKRI')->value('id'))
        ->and($ownerOf('cost_per_lead'))->toBe(Owner::where('name', 'INFO')->value('id'));
});

it('imports the action plan when the PIC fills column K', function (): void {
    bindDbenaGrid(dbenaGrid([
        3 => [1 => '100', 10 => 'Tambah 2 site visit setiap minggu'],
    ]));

    app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    $metric = Service::where('key', 'renovation')->firstOrFail()->metricByKey('sales_collection_new');

    expect(CriticalMetricMonth::where('critical_metric_id', $metric->id)
        ->where('year', 2026)->where('month', 7)->value('action_plan'))
        ->toBe('Tambah 2 site visit setiap minggu');
});

/*
|--------------------------------------------------------------------------
| Import Monthly Target (opt-in)
|--------------------------------------------------------------------------
*/

it('leaves targets alone unless target import is switched on', function (): void {
    bindDbenaGrid(dbenaGrid([4 => [1 => '100', 7 => 'RM999,999.00']]));

    app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    $metric = Service::where('key', 'renovation')->firstOrFail()->metricByKey('revenue_sales');

    expect((float) $metric->targetForYear(2026)->monthly_target)->toBe(500000.0);
});

it('imports numeric targets when switched on', function (): void {
    $this->integration->update(['import_targets' => true]);
    bindDbenaGrid(dbenaGrid([4 => [1 => '100', 7 => 'RM750,000.00']]));

    app(SheetSyncService::class)->sync($this->integration->fresh(), 2026, 7);

    $metric = Service::where('key', 'renovation')->firstOrFail()->metricByKey('revenue_sales');

    expect((float) $metric->fresh()->targetForYear(2026)->monthly_target)->toBe(750000.0);
});

it('keeps a non-numeric "Progress" target as text rather than zero', function (): void {
    $this->integration->update(['import_targets' => true]);
    bindDbenaGrid(dbenaGrid([5 => [1 => '4890']]));

    app(SheetSyncService::class)->sync($this->integration->fresh(), 2026, 7);

    $metric = Service::where('key', 'renovation')->firstOrFail()->metricByKey('sales_collection_progress');
    $target = $metric->fresh()->targetForYear(2026);

    expect($target->monthly_target)->toBeNull()
        ->and($target->target_text)->toBe('Progress')
        ->and($target->isNumeric())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Aliran mingguan sepanjang bulan
|--------------------------------------------------------------------------
*/

it('accumulates as the PIC fills one week at a time', function (): void {
    $service = Service::where('key', 'renovation')->firstOrFail();
    $metric = $service->metricByKey('no_of_lead');

    $actual = fn () => CriticalWeeklyEntry::where('critical_metric_id', $metric->id)
        ->where('year', 2026)->where('month', 7)->sum('value');

    // Minggu 1
    bindDbenaGrid(dbenaGrid([10 => [1 => '150']]));
    app(SheetSyncService::class)->sync($this->integration, 2026, 7);
    expect((float) $actual())->toBe(150.0);

    // Minggu 2 ditambah
    bindDbenaGrid(dbenaGrid([10 => [1 => '150', 2 => '160']]));
    app(SheetSyncService::class)->sync($this->integration, 2026, 7);
    expect((float) $actual())->toBe(310.0);

    // Minggu 1 diperbetulkan, minggu 3 ditambah
    bindDbenaGrid(dbenaGrid([10 => [1 => '155', 2 => '160', 3 => '140']]));
    app(SheetSyncService::class)->sync($this->integration, 2026, 7);
    expect((float) $actual())->toBe(455.0);
});

it('turns Green once the four weeks add up past the target', function (): void {
    $service = Service::where('key', 'renovation')->firstOrFail();

    // Sasaran No of Site Visit = 24
    bindDbenaGrid(dbenaGrid([8 => [1 => '6', 2 => '6', 3 => '6', 4 => '7']]));
    app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    $rows = app(App\Services\CriticalDataService::class)->rowsFor($service, 2026, 7);
    $row = $rows->firstWhere('metricKey', 'no_of_site_visit');

    expect((float) $row['actual'])->toBe(25.0)
        ->and($row['status'])->toBe(App\Enums\MetricStatus::Green);
});

/*
|--------------------------------------------------------------------------
| Pratonton
|--------------------------------------------------------------------------
*/

it('shows band rows and metric rows separately in the preview', function (): void {
    bindDbenaGrid(dbenaGrid([3 => [1 => '100']]));

    $preview = app(SheetSyncService::class)->preview($this->integration);

    $bands = collect($preview['rows'])->where('type', 'band');
    $metrics = collect($preview['rows'])->where('type', 'metric');

    expect($bands)->toHaveCount(2)
        ->and($bands->pluck('service'))->toContain('Renovation', 'Bina Rumah')
        ->and($metrics->every(fn (array $r) => $r['matched']))->toBeTrue()
        ->and($preview['headerRow'])->toBe(2)
        ->and($preview['detectedServices'])->toContain('Renovation', 'Bina Rumah');
});

it('carries the service context into each previewed metric row', function (): void {
    bindDbenaGrid(dbenaGrid());

    $preview = app(SheetSyncService::class)->preview($this->integration);
    $metrics = collect($preview['rows'])->where('type', 'metric')->values();

    expect($metrics->first()['service'])->toBe('Renovation')
        ->and($metrics->last()['service'])->toBe('Bina Rumah');
});

/*
|--------------------------------------------------------------------------
| Kes tepi
|--------------------------------------------------------------------------
*/

it('flags an unrecognised metric under the service it sat below', function (): void {
    $grid = dbenaGrid([3 => [1 => '100']]);
    $grid[] = ['Metrik Rekaan', '5', '', '', '', 'Total', '0', '10', 'Red', 'ZIKRI', ''];

    bindDbenaGrid($grid);

    $result = app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    expect($result['status'])->toBe('partial')
        ->and($result['unmatched'])->toContain('Bina Rumah › Metrik Rekaan');
});

it('still works when the sheet has no instruction banner', function (): void {
    $grid = dbenaGrid();
    array_shift($grid); // buang banner — header kini baris 1

    bindDbenaGrid($grid);

    expect(app(SheetSyncService::class)->detectHeaderRow($grid))->toBe(1);
});

it('refuses a single-service layout that has no service attached', function (): void {
    $this->integration->update(['layout_mode' => 'single']);

    expect($this->integration->fresh()->isReadyToSync())->toBeFalse();
});
