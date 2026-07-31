<?php

declare(strict_types=1);

use App\Contracts\SheetReader;
use App\Models\SheetIntegration;
use App\Services\Sheets\SheetSyncService;

/** Pembaca palsu yang memulangkan grid yang kita tetapkan. */
function fakeReader(array $grid): SheetReader
{
    return new class($grid) implements SheetReader
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
    };
}

function syncService(array $grid = []): SheetSyncService
{
    return new SheetSyncService(fakeReader($grid));
}

/*
|--------------------------------------------------------------------------
| Penghuraian nombor — sheet sebenar penuh dengan format yang tidak kemas
|--------------------------------------------------------------------------
*/

it('parses plain numbers', function (): void {
    $parse = fn (string $v) => (new ReflectionMethod(SheetSyncService::class, 'toNumber'))
        ->invoke(syncService(), $v);

    expect($parse('1200'))->toBe(1200.0)
        ->and($parse('0'))->toBe(0.0)
        ->and($parse('12.5'))->toBe(12.5);
});

it('strips currency symbols and thousand separators', function (): void {
    $parse = fn (string $v) => (new ReflectionMethod(SheetSyncService::class, 'toNumber'))
        ->invoke(syncService(), $v);

    expect($parse('RM12,500.00'))->toBe(12500.0)
        ->and($parse('RM 1,234,567'))->toBe(1234567.0)
        ->and($parse('  RM45,000  '))->toBe(45000.0);
});

it('understands European decimal formatting', function (): void {
    $parse = fn (string $v) => (new ReflectionMethod(SheetSyncService::class, 'toNumber'))
        ->invoke(syncService(), $v);

    expect($parse('1.234,56'))->toBe(1234.56);
});

it('reads accounting-style negatives in brackets', function (): void {
    $parse = fn (string $v) => (new ReflectionMethod(SheetSyncService::class, 'toNumber'))
        ->invoke(syncService(), $v);

    expect($parse('(1,500)'))->toBe(-1500.0);
});

it('treats blanks and placeholder text as no value', function (): void {
    $parse = fn (string $v) => (new ReflectionMethod(SheetSyncService::class, 'toNumber'))
        ->invoke(syncService(), $v);

    foreach (['', '   ', '-', '—', 'N/A', 'n/a', 'NIL', 'tiada', 'Progress', 'abc'] as $blank) {
        expect($parse($blank))->toBeNull("Nilai '{$blank}' sepatutnya null");
    }
});

/*
|--------------------------------------------------------------------------
| Padanan label — sheet sebenar tidak pernah sepadan tepat
|--------------------------------------------------------------------------
*/

it('normalises labels so punctuation and case do not matter', function (): void {
    $normalise = fn (string $v) => (new ReflectionMethod(SheetSyncService::class, 'normalise'))
        ->invoke(syncService(), $v);

    $expected = 'no of new quotation';

    expect($normalise('No of New Quotation'))->toBe($expected)
        ->and($normalise('NO. OF NEW QUOTATION'))->toBe($expected)
        ->and($normalise('  no   of  new  quotation  '))->toBe($expected)
        ->and($normalise('No. of New Quotation!'))->toBe($expected);
});

/*
|--------------------------------------------------------------------------
| Rujukan lajur
|--------------------------------------------------------------------------
*/

it('converts column letters to zero-based indexes', function (): void {
    $toIndex = fn (string $v) => (new ReflectionMethod(SheetSyncService::class, 'letterToIndex'))
        ->invoke(syncService(), $v);

    expect($toIndex('A'))->toBe(0)
        ->and($toIndex('C'))->toBe(2)
        ->and($toIndex('Z'))->toBe(25)
        ->and($toIndex('AA'))->toBe(26)
        ->and($toIndex('AB'))->toBe(27);
});

it('converts indexes back to column letters', function (): void {
    $toLetter = fn (int $v) => (new ReflectionMethod(SheetSyncService::class, 'indexToLetter'))
        ->invoke(syncService(), $v);

    expect($toLetter(0))->toBe('A')
        ->and($toLetter(25))->toBe('Z')
        ->and($toLetter(26))->toBe('AA')
        ->and($toLetter(27))->toBe('AB');
});

/*
|--------------------------------------------------------------------------
| Cadangan pemetaan automatik
|--------------------------------------------------------------------------
*/

it('auto-detects a Malay header row', function (): void {
    $suggestions = syncService()->suggestMapping([
        'DATA KRITIKAL', 'MINGGU 1', 'MINGGU 2', 'MINGGU 3', 'MINGGU 4', 'JENIS', 'ACTUAL', 'PEMILIK',
    ]);

    expect($suggestions['metric'])->toBe('A')
        ->and($suggestions['week1'])->toBe('B')
        ->and($suggestions['week4'])->toBe('E')
        ->and($suggestions['data_type'])->toBe('F')
        ->and($suggestions['owner'])->toBe('H');
});

it('auto-detects an English header row', function (): void {
    $suggestions = syncService()->suggestMapping([
        'Critical Data', 'Week 1', 'Week 2', 'Week 3', 'Week 4', 'Owner', 'Action Plan',
    ]);

    expect($suggestions['metric'])->toBe('A')
        ->and($suggestions['week1'])->toBe('B')
        ->and($suggestions['action_plan'])->toBe('G');
});

it('handles headers that carry a date range under the week label', function (): void {
    // Mengikut tangkapan skrin dashboard: "MINGGU 1 01/07-09/07"
    $suggestions = syncService()->suggestMapping([
        'DATA KRITIKAL', "MINGGU 1\n01/07-09/07", "MINGGU 2\n10/07-16/07",
        "MINGGU 3\n17/07-23/07", "MINGGU 4\n24/07-31/07",
    ]);

    expect($suggestions['week1'])->toBe('B')
        ->and($suggestions['week3'])->toBe('D');
});

it('leaves unrecognised headers unmapped rather than guessing wrong', function (): void {
    $suggestions = syncService()->suggestMapping(['Foo', 'Bar', 'Baz']);

    expect($suggestions)->toBeEmpty();
});

/*
|--------------------------------------------------------------------------
| Pengekstrakan ID daripada URL
|--------------------------------------------------------------------------
*/

it('extracts the spreadsheet id from every common URL shape', function (): void {
    $id = '1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms';

    expect(SheetIntegration::extractSpreadsheetId("https://docs.google.com/spreadsheets/d/{$id}/edit#gid=0"))->toBe($id)
        ->and(SheetIntegration::extractSpreadsheetId("https://docs.google.com/spreadsheets/d/{$id}/edit?usp=sharing"))->toBe($id)
        ->and(SheetIntegration::extractSpreadsheetId("https://docs.google.com/spreadsheets/d/{$id}"))->toBe($id)
        ->and(SheetIntegration::extractSpreadsheetId($id))->toBe($id);
});

it('extracts the gid when the URL points at a specific tab', function (): void {
    expect(SheetIntegration::extractGid('https://docs.google.com/spreadsheets/d/abc123456789012345678/edit#gid=847362'))
        ->toBe('847362')
        ->and(SheetIntegration::extractGid('https://docs.google.com/spreadsheets/d/abc/edit'))->toBeNull();
});

it('returns null for anything that is not a sheet link', function (): void {
    expect(SheetIntegration::extractSpreadsheetId('https://example.com'))->toBeNull()
        ->and(SheetIntegration::extractSpreadsheetId(''))->toBeNull()
        ->and(SheetIntegration::extractSpreadsheetId(null))->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Kesediaan untuk sync
|--------------------------------------------------------------------------
*/

it('refuses to sync until every required column is mapped', function (): void {
    $integration = new SheetIntegration([
        'spreadsheet_id' => 'abc123',
        'sync_enabled' => true,
        'layout_mode' => 'multi',
        'column_map' => ['metric' => 'A', 'week1' => 'B'],
    ]);

    expect($integration->isReadyToSync())->toBeFalse()
        ->and($integration->missingMappings())->toBe(['week2', 'week3', 'week4']);
});

it('is ready once all required columns are mapped', function (): void {
    $integration = new SheetIntegration([
        'spreadsheet_id' => 'abc123',
        'sync_enabled' => true,
        'layout_mode' => 'multi',
        'column_map' => ['metric' => 'A', 'week1' => 'B', 'week2' => 'C', 'week3' => 'D', 'week4' => 'E'],
    ]);

    expect($integration->isReadyToSync())->toBeTrue()
        ->and($integration->missingMappings())->toBeEmpty();
});

it('will not sync while sync is switched off', function (): void {
    $integration = new SheetIntegration([
        'spreadsheet_id' => 'abc123',
        'layout_mode' => 'multi',
        'sync_enabled' => false,
        'column_map' => ['metric' => 'A', 'week1' => 'B', 'week2' => 'C', 'week3' => 'D', 'week4' => 'E'],
    ]);

    expect($integration->isReadyToSync())->toBeFalse();
});
