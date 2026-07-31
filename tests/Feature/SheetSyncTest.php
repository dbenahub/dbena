<?php

declare(strict_types=1);

use App\Contracts\SheetReader;
use App\Enums\UserRole;
use App\Models\CriticalWeeklyEntry;
use App\Models\Service;
use App\Models\SheetIntegration;
use App\Models\SheetSyncLog;
use App\Models\User;
use App\Services\Sheets\SheetSyncService;

beforeEach(function (): void {
    $this->seed();

    $this->admin = User::where('role', UserRole::Admin)->firstOrFail();
    $this->service = Service::where('key', 'renovation')->firstOrFail();

    $this->integration = SheetIntegration::create([
        'service_id' => $this->service->id,
        'url' => 'https://docs.google.com/spreadsheets/d/abc123456789012345678/edit',
        'spreadsheet_id' => 'abc123456789012345678',
        'sync_enabled' => true,
        'layout_mode' => 'single',
        'header_row' => 1,
        'match_mode' => 'label',
        'column_map' => ['metric' => 'A', 'week1' => 'B', 'week2' => 'C', 'week3' => 'D', 'week4' => 'E'],
    ]);
});

/** Ikat pembaca palsu supaya ujian tidak menyentuh rangkaian. */
function bindGrid(array $grid): void
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
            return 'Fake';
        }
    });
}

it('writes weekly values from the sheet into the database', function (): void {
    bindGrid([
        ['DATA KRITIKAL', 'MINGGU 1', 'MINGGU 2', 'MINGGU 3', 'MINGGU 4'],
        ['Revenue/Sales', '100000', '120000', '90000', '110000'],
        ['No of Lead', '150', '160', '140', '155'],
    ]);

    $result = app(SheetSyncService::class)->sync($this->integration, 2026, 7, 'manual', $this->admin->id);

    expect($result['status'])->toBe('success')
        ->and($result['rowsMatched'])->toBe(2);

    $metric = $this->service->metricByKey('revenue_sales');

    $values = CriticalWeeklyEntry::where('critical_metric_id', $metric->id)
        ->where('year', 2026)->where('month', 7)
        ->orderBy('week_number')
        ->pluck('value')
        ->map(fn ($v) => (float) $v)
        ->all();

    expect($values)->toBe([100000.0, 120000.0, 90000.0, 110000.0]);
});

it('matches metric names regardless of case and punctuation', function (): void {
    bindGrid([
        ['DATA KRITIKAL', 'M1', 'M2', 'M3', 'M4'],
        ['  no. of NEW quotation!  ', '4', '5', '3', '4'],
    ]);

    $result = app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    expect($result['rowsMatched'])->toBe(1);

    $metric = $this->service->metricByKey('no_of_new_quotation');

    expect((float) CriticalWeeklyEntry::where('critical_metric_id', $metric->id)
        ->where('year', 2026)->where('month', 7)->where('week_number', 2)->value('value'))
        ->toBe(5.0);
});

it('strips RM formatting when writing currency metrics', function (): void {
    bindGrid([
        ['DATA KRITIKAL', 'M1', 'M2', 'M3', 'M4'],
        ['Ads Spend', 'RM1,500.00', 'RM 2,000', '(500)', ''],
    ]);

    app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    $metric = $this->service->metricByKey('ads_spend');

    $values = CriticalWeeklyEntry::where('critical_metric_id', $metric->id)
        ->where('year', 2026)->where('month', 7)->orderBy('week_number')
        ->pluck('value')->all();

    expect((float) $values[0])->toBe(1500.0)
        ->and((float) $values[1])->toBe(2000.0)
        ->and((float) $values[2])->toBe(-500.0)
        ->and($values[3])->toBeNull();
});

it('reports partial status and names the rows it could not match', function (): void {
    bindGrid([
        ['DATA KRITIKAL', 'M1', 'M2', 'M3', 'M4'],
        ['Revenue/Sales', '1000', '', '', ''],
        ['Metrik Yang Tidak Wujud', '500', '', '', ''],
    ]);

    $result = app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    expect($result['status'])->toBe('partial')
        ->and($result['rowsMatched'])->toBe(1)
        ->and($result['unmatched'])->toContain('Metrik Yang Tidak Wujud');
});

it('fails cleanly when nothing at all matches', function (): void {
    bindGrid([
        ['DATA KRITIKAL', 'M1', 'M2', 'M3', 'M4'],
        ['Sesuatu Yang Lain', '1', '2', '3', '4'],
    ]);

    $result = app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    expect($result['status'])->toBe('failed')
        ->and($result['rowsMatched'])->toBe(0);
});

it('refuses to run when the mapping is incomplete', function (): void {
    $this->integration->update(['column_map' => ['metric' => 'A', 'week1' => 'B']]);

    bindGrid([['DATA KRITIKAL', 'M1'], ['Revenue/Sales', '100']]);

    $result = app(SheetSyncService::class)->sync($this->integration->fresh(), 2026, 7);

    expect($result['status'])->toBe('failed')
        ->and($result['message'])->toContain('week2');
});

it('skips blank rows without counting them', function (): void {
    bindGrid([
        ['DATA KRITIKAL', 'M1', 'M2', 'M3', 'M4'],
        ['Revenue/Sales', '1000', '', '', ''],
        ['', '', '', '', ''],
        [],
        ['No of Lead', '50', '', '', ''],
    ]);

    $result = app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    expect($result['rowsRead'])->toBe(2)
        ->and($result['rowsMatched'])->toBe(2);
});

it('honours a header row further down the sheet', function (): void {
    $this->integration->update(['header_row' => 3, 'layout_mode' => 'single']);

    bindGrid([
        ['DBENA SDN BHD'],
        ['Laporan Julai 2026'],
        ['DATA KRITIKAL', 'M1', 'M2', 'M3', 'M4'],
        ['Revenue/Sales', '7777', '', '', ''],
    ]);

    $result = app(SheetSyncService::class)->sync($this->integration->fresh(), 2026, 7);

    expect($result['rowsMatched'])->toBe(1);

    $metric = $this->service->metricByKey('revenue_sales');

    expect((float) CriticalWeeklyEntry::where('critical_metric_id', $metric->id)
        ->where('year', 2026)->where('month', 7)->where('week_number', 1)->value('value'))
        ->toBe(7777.0);
});

it('imports the owner column when it is mapped', function (): void {
    $this->integration->update([
        'column_map' => ['metric' => 'A', 'week1' => 'B', 'week2' => 'C', 'week3' => 'D', 'week4' => 'E', 'owner' => 'F'],
    ]);

    bindGrid([
        ['DATA KRITIKAL', 'M1', 'M2', 'M3', 'M4', 'PEMILIK'],
        ['Revenue/Sales', '1000', '', '', '', 'HAFIZAN'],
    ]);

    app(SheetSyncService::class)->sync($this->integration->fresh(), 2026, 7);

    $metric = $this->service->metricByKey('revenue_sales');

    $this->assertDatabaseHas('critical_metric_months', [
        'critical_metric_id' => $metric->id,
        'year' => 2026,
        'month' => 7,
        'owner_id' => App\Models\Owner::where('name', 'HAFIZAN')->value('id'),
    ]);
});

it('records every run in the sync log', function (): void {
    bindGrid([
        ['DATA KRITIKAL', 'M1', 'M2', 'M3', 'M4'],
        ['Revenue/Sales', '1000', '', '', ''],
    ]);

    app(SheetSyncService::class)->sync($this->integration, 2026, 7, 'webhook', $this->admin->id);

    $log = SheetSyncLog::latest('id')->firstOrFail();

    expect($log->trigger)->toBe('webhook')
        ->and($log->status)->toBe('success')
        ->and($log->triggered_by)->toBe($this->admin->id)
        ->and($log->year)->toBe(2026)
        ->and($log->month)->toBe(7);
});

it('overwrites an earlier value on re-sync rather than duplicating it', function (): void {
    bindGrid([
        ['DATA KRITIKAL', 'M1', 'M2', 'M3', 'M4'],
        ['Revenue/Sales', '1000', '', '', ''],
    ]);
    app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    bindGrid([
        ['DATA KRITIKAL', 'M1', 'M2', 'M3', 'M4'],
        ['Revenue/Sales', '2500', '', '', ''],
    ]);
    app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    $metric = $this->service->metricByKey('revenue_sales');

    $rows = CriticalWeeklyEntry::where('critical_metric_id', $metric->id)
        ->where('year', 2026)->where('month', 7)->where('week_number', 1)->get();

    expect($rows)->toHaveCount(1)
        ->and((float) $rows->first()->value)->toBe(2500.0);
});

it('keeps each month separate', function (): void {
    bindGrid([['DATA KRITIKAL', 'M1', 'M2', 'M3', 'M4'], ['Revenue/Sales', '111', '', '', '']]);
    app(SheetSyncService::class)->sync($this->integration, 2026, 7);

    bindGrid([['DATA KRITIKAL', 'M1', 'M2', 'M3', 'M4'], ['Revenue/Sales', '222', '', '', '']]);
    app(SheetSyncService::class)->sync($this->integration, 2026, 8);

    $metric = $this->service->metricByKey('revenue_sales');

    expect((float) CriticalWeeklyEntry::where('critical_metric_id', $metric->id)
        ->where('year', 2026)->where('month', 7)->where('week_number', 1)->value('value'))->toBe(111.0)
        ->and((float) CriticalWeeklyEntry::where('critical_metric_id', $metric->id)
            ->where('year', 2026)->where('month', 8)->where('week_number', 1)->value('value'))->toBe(222.0);
});

/*
|--------------------------------------------------------------------------
| Webhook
|--------------------------------------------------------------------------
*/

it('queues a sync when Apps Script calls with a valid token', function (): void {
    Illuminate\Support\Facades\Queue::fake();

    $token = $this->integration->ensureWebhookSecret();

    $this->postJson("/sheets/webhook/{$this->integration->id}/{$token}", ['year' => 2026, 'month' => 7])
        ->assertOk()
        ->assertJson(['ok' => true, 'queued' => true]);

    Illuminate\Support\Facades\Queue::assertPushed(App\Jobs\SyncSheetJob::class);
});

it('rejects a webhook call with the wrong token', function (): void {
    $this->integration->ensureWebhookSecret();

    $this->postJson("/sheets/webhook/{$this->integration->id}/tokenpalsu")
        ->assertForbidden()
        ->assertJson(['ok' => false, 'error' => 'invalid_token']);
});

it('rejects a webhook call while sync is disabled', function (): void {
    $token = $this->integration->ensureWebhookSecret();
    $this->integration->update(['sync_enabled' => false]);

    $this->postJson("/sheets/webhook/{$this->integration->id}/{$token}")
        ->assertStatus(409)
        ->assertJson(['error' => 'sync_disabled']);
});

it('clamps an out-of-range period sent by the webhook', function (): void {
    Illuminate\Support\Facades\Queue::fake();

    $token = $this->integration->ensureWebhookSecret();

    $this->postJson("/sheets/webhook/{$this->integration->id}/{$token}", ['year' => 9999, 'month' => 99])
        ->assertOk()
        ->assertJson(['period' => '2100-12']);
});

/*
|--------------------------------------------------------------------------
| Kawalan akses
|--------------------------------------------------------------------------
*/

it('keeps the sheet manager away from plain users', function (): void {
    $user = User::where('role', UserRole::User)->firstOrFail();

    $this->actingAs($user)->get('/admin/sheets')->assertForbidden();
});

it('lets an admin open the sheet manager', function (): void {
    $this->actingAs($this->admin)->get('/admin/sheets')->assertOk();
});
