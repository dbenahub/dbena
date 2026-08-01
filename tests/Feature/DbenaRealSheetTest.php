<?php

declare(strict_types=1);

use App\Contracts\SheetReader;
use App\Enums\UserRole;
use App\Models\CriticalWeeklyEntry;
use App\Models\Service;
use App\Models\SheetIntegration;
use App\Models\User;
use App\Services\Sheets\SheetSyncService;

/*
|--------------------------------------------------------------------------
| Sheet DBENA sebenar - disalin dari tangkapan skrin Julai 2026
|--------------------------------------------------------------------------
| Perbezaan penting daripada andaian awal:
|   • Ada bahagian COMPANY PERFORMANCE di atas dengan baris "Total ..."
|     yang merupakan jumlah dikira sheet - mesti DILANGKAU
|   • Sheet mengandungi typo: "Cost Per Appoitment (CPA)" (kurang 'n')
*/

function realGrid(array $overrides = []): array
{
    $grid = [
        ['', 'MASUKKAN DATA & REPORT DALAM KOTAK BERWARNA MERAH', '', '', '', '', '', '', '', '', ''],
        [
            'DATA CRITICAL',
            "Week 1\n[01/07-09/07]", "Week 2\n[10/07-16/07]",
            "Week 3\n[17/07-23/07]", "Week 4\n[24/07-31/07]",
            'Data Type', 'Monthly Actual', 'Monthly Target', 'Data Status', 'Data Owner', 'Action Plan',
        ],

        // Bahagian ringkasan - jumlah dikira sheet, jangan import
        ['COMPANY PERFORMANCE', '', '', '', '', '', '', '', '', '', ''],
        ['Total Sales Collection (New)',        'RM0.00', 'RM0.00', 'RM0.00', 'RM0.00', 'Total', 'RM0.00', 'RM405,001.00', 'Red', 'Info', ''],
        ['Total Revenue / Sales',               'RM0.00', 'RM0.00', 'RM0.00', 'RM0.00', 'Total', 'RM0.00', 'RM1,350,002.00', 'Red', 'Info', ''],
        ['Total Amount Quotation Release',      'RM0.00', 'RM0.00', 'RM0.00', 'RM210,526.85', 'Total', 'RM210,526.85', 'RM6,650,010.00', 'Red', 'Info', ''],
        ['Total No of New Quotation',           '0', '0', '0', '1', 'Total', '1', '141', 'Red', 'Info', ''],
        ['Total No of Lead',                    '0', '0', '0', '0', 'Total', '0', '1,533', 'Red', 'Info', ''],

        // Servis sebenar
        ['Renovation', '', '', '', '', '', '', '', '', '', ''],
        ['Sales Collection (New)',              '', '', '', '', 'Total', 'RM0.00', 'RM150,000.00', 'Red', 'HAFIZAN', ''],
        ['Revenue/Sales',                       '', '', '', '', 'Total', 'RM0.00', 'RM500,000.00', 'Red', 'HAFIZAN', ''],
        ['Sales Collection (Progress Claim)',   '', '', '', '', 'Total', 'RM0.00', 'Progress', 'Red', 'HAFIZAN', ''],
        ['Amount Quotation Release (New)',      'RM0.00', 'RM0.00', 'RM0.00', 'RM210,526.85', 'Total', 'RM210,526.85', 'RM2,400,000.00', 'Red', 'HAFIZAN', ''],
        ['No of New Quotation',                 '0', '0', '0', '1', 'Total', '1', '16', 'Red', 'HAFIZAN', ''],
        ['No of Site Visit',                    '', '', '', '', 'Total', '0', '24', 'Red', 'ZIKRI', ''],
        ['Ads Spend',                           '', '', '', '', 'Total', 'RM0.00', 'RM8,000.00', 'Red', 'ZIKRI', ''],
        ['No of Lead',                          '', '', '', '', 'Total', '0', '533', 'Red', 'ZIKRI', ''],
        ['Cost Per Lead (CPL)',                 '', '', '', '', 'Avg', 'RM0.00', 'RM15.00', 'Red', 'Info', ''],
        // Perhatikan typo dalam sheet sebenar: "Appoitment"
        ['Cost Per Appoitment (CPA)',           '', '', '', '', 'Avg', 'RM0.00', 'RM333.33', 'Red', 'Info', ''],

        ['Bina Rumah', '', '', '', '', '', '', '', '', '', ''],
        ['Sales Collection (New)',              '', '', '', '', 'Total', 'RM0.00', 'RM150,000.00', 'Red', 'HAFIZAN', ''],
        ['Revenue/Sales',                       '', '', '', '', 'Total', 'RM0.00', 'RM500,000.00', 'Red', 'HAFIZAN', ''],
        ['No of Appointment (Offline / Online)', '', '', '', '', 'Total', '0', '30', 'Red', 'ZIKRI', ''],
    ];

    foreach ($overrides as $r => $cells) {
        foreach ($cells as $c => $v) {
            $grid[$r][$c] = $v;
        }
    }

    return $grid;
}

function bindRealGrid(array $grid): void
{
    app()->bind(SheetReader::class, fn () => new class($grid) implements SheetReader
    {
        public function __construct(private array $grid) {}

        public function read(SheetIntegration $i): array
        {
            return $this->grid;
        }

        public function label(): string
        {
            return 'Real DBENA';
        }
    });
}

beforeEach(function (): void {
    $this->seed();

    $this->admin = User::where('role', UserRole::Admin)->firstOrFail();

    $this->integration = SheetIntegration::updateOrCreate(['service_id' => null], [
        'spreadsheet_id' => 'abc123456789012345678',
        'sync_enabled' => true,
        'layout_mode' => 'multi',
        'header_row' => 0,
        'match_mode' => 'label',
        'column_map' => [
            'metric' => 'A', 'week1' => 'B', 'week2' => 'C', 'week3' => 'D', 'week4' => 'E',
            'data_type' => 'F', 'target' => 'H', 'owner' => 'J', 'action_plan' => 'K',
        ],
    ]);
});

/*
|--------------------------------------------------------------------------
| Auto-kesan header
|--------------------------------------------------------------------------
*/

it('finds the header on row 2, past the red instruction banner', function (): void {
    expect(app(SheetSyncService::class)->detectHeaderRow(realGrid()))->toBe(2);
});

it('maps every DBENA column from the real header', function (): void {
    expect(app(SheetSyncService::class)->suggestMapping(realGrid()[1]))->toMatchArray([
        'metric' => 'A', 'week1' => 'B', 'week2' => 'C', 'week3' => 'D', 'week4' => 'E',
        'data_type' => 'F', 'target' => 'H', 'owner' => 'J', 'action_plan' => 'K',
    ]);
});

/*
|--------------------------------------------------------------------------
| COMPANY PERFORMANCE mesti dilangkau
|--------------------------------------------------------------------------
*/

it('skips the COMPANY PERFORMANCE summary section entirely', function (): void {
    bindRealGrid(realGrid());

    $result = app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    expect($result['skippedSections'])->toContain('COMPANY PERFORMANCE');
});

it('does not import the sheet-calculated totals', function (): void {
    // "Total Amount Quotation Release" membawa RM210,526.85 dalam Minggu 4.
    // Nilai SAMA muncul di bawah Renovation. Jika kedua-duanya diimport,
    // jumlah dashboard akan berganda.
    bindRealGrid(realGrid());

    app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    $metric = Service::where('key', 'renovation')->firstOrFail()->metricByKey('amount_quotation_release');

    $total = CriticalWeeklyEntry::where('critical_metric_id', $metric->id)
        ->where('year', 2026)->where('month', 7)->sum('value');

    expect((float) $total)->toBe(210526.85);
});

it('does not report summary rows as unmatched errors', function (): void {
    bindRealGrid(realGrid());

    $result = app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    foreach ($result['unmatched'] as $label) {
        expect($label)->not->toContain('Total ');
    }
});

/*
|--------------------------------------------------------------------------
| Toleransi typo
|--------------------------------------------------------------------------
*/

it('still matches a metric the sheet spelled wrong', function (): void {
    // Sheet menulis "Cost Per Appoitment (CPA)" - kurang huruf 'n'
    bindRealGrid(realGrid([18 => [1 => '25']]));

    $result = app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    $metric = Service::where('key', 'renovation')->firstOrFail()->metricByKey('cost_per_appointment');

    expect((float) CriticalWeeklyEntry::where('critical_metric_id', $metric->id)
        ->where('year', 2026)->where('month', 7)->where('week_number', 1)->value('value'))
        ->toBe(25.0)
        ->and($result['unmatched'])->not->toContain('Renovation › Cost Per Appoitment (CPA)');
});

it('refuses to fuzzy-match two genuinely different metrics', function (): void {
    $service = Service::where('key', 'renovation')->firstOrFail();

    $find = new ReflectionMethod(SheetSyncService::class, 'findMetric');
    $build = new ReflectionMethod(SheetSyncService::class, 'buildLookup');

    $sync = app(SheetSyncService::class);
    $lookup = $build->invoke($sync, $service->criticalMetrics()->get(), 'label');

    // "No of Lead" dan "No of New Quotation" jauh berbeza - jangan padan
    expect($find->invoke($sync, $lookup, 'Something Entirely Different'))->toBeNull()
        ->and($find->invoke($sync, $lookup, 'No of Lead'))->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Padanan servis
|--------------------------------------------------------------------------
*/

it('detects both service bands in the real sheet', function (): void {
    bindRealGrid(realGrid());

    $result = app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    expect($result['services'])->toContain('Renovation', 'Bina Rumah');
});

it('files Bina Rumah rows under Bina Rumah, not Renovation', function (): void {
    bindRealGrid(realGrid([20 => [1 => '77777']]));

    app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    $binaRumah = Service::where('key', 'bina-rumah')->firstOrFail()->metricByKey('sales_collection_new');
    $renovation = Service::where('key', 'renovation')->firstOrFail()->metricByKey('sales_collection_new');

    $value = fn ($m) => CriticalWeeklyEntry::where('critical_metric_id', $m->id)
        ->where('year', 2026)->where('month', 7)->where('week_number', 1)->value('value');

    expect((float) $value($binaRumah))->toBe(77777.0)
        ->and($value($renovation))->toBeNull();
});

it('matches the Appointment metric unique to Bina Rumah', function (): void {
    bindRealGrid(realGrid([22 => [1 => '8']]));

    $result = app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    $metric = Service::where('key', 'bina-rumah')->firstOrFail()->metricByKey('no_of_appointment');

    expect((float) CriticalWeeklyEntry::where('critical_metric_id', $metric->id)
        ->where('year', 2026)->where('month', 7)->where('week_number', 1)->value('value'))
        ->toBe(8.0);
});

/*
|--------------------------------------------------------------------------
| Pratonton
|--------------------------------------------------------------------------
*/

it('shows the summary section as skipped in the preview', function (): void {
    bindRealGrid(realGrid());

    $preview = app(SheetSyncService::class)->preview($this->integration);

    expect($preview['skippedSections'])->toContain('COMPANY PERFORMANCE')
        ->and($preview['headerRow'])->toBe(2)
        ->and($preview['detectedServices'])->toContain('Renovation', 'Bina Rumah');

    $skippedRows = collect($preview['rows'])->where('type', 'skipped');
    $ignoredRows = collect($preview['rows'])->where('type', 'ignored');

    expect($skippedRows)->toHaveCount(1)
        ->and($ignoredRows->count())->toBeGreaterThan(0);
});

it('marks every real metric row as matched in the preview', function (): void {
    bindRealGrid(realGrid());

    $preview = app(SheetSyncService::class)->preview($this->integration);

    $unmatched = collect($preview['rows'])->where('type', 'metric')->where('matched', false);

    expect($unmatched)->toBeEmpty();
});

/*
|--------------------------------------------------------------------------
| Import sasaran dari sheet
|--------------------------------------------------------------------------
*/

it('imports the real sheet targets when switched on', function (): void {
    $this->integration->update(['import_targets' => true]);
    bindRealGrid(realGrid());

    app(SheetSyncService::class)->sync($this->integration->fresh(), 2026, 7);

    $service = Service::where('key', 'renovation')->firstOrFail();

    // Sheet mengatakan RM8,000 untuk Ads Spend; seeder prototaip kata RM6,000
    expect((float) $service->metricByKey('ads_spend')->targetForYear(2026)->monthly_target)->toBe(8000.0)
        // Sheet mengatakan 533 untuk No of Lead; seeder kata 600
        ->and((float) $service->metricByKey('no_of_lead')->targetForYear(2026)->monthly_target)->toBe(533.0);
});
