<?php

declare(strict_types=1);

use App\Contracts\SheetReader;
use App\Models\Service;
use App\Models\SheetIntegration;
use App\Models\StrategyPlan;
use App\Models\StrategyRow;
use App\Models\StrategyTile;
use App\Services\Sheets\StrategySyncService;

function fakeStrategyReader(array $grid): SheetReader
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

function syncStrategy(array $grid, ?Service $service = null): array
{
    $service ??= Service::where('key', 'renovation')->firstOrFail();

    $integration = SheetIntegration::firstOrCreate(
        ['kind' => 'strategy', 'service_id' => $service->id],
        ['connected' => true, 'tab_name' => 'STRATEGIC']
    );

    return (new StrategySyncService(fakeStrategyReader($grid)))->sync($integration);
}

/** Tab seperti yang dirancang: tajuk, visi, petak, kemudian jadual. */
function strategyGrid(): array
{
    return [
        ['Strategic Planning & KPI Alignment - RENOVATION'],
        ['Dashboard Summary'],
        [],
        ['VISI', 'Menjadi pilihan utama pelanggan untuk kerja Major Renovation rumah di Klang Valley'],
        [],
        ['NO', 'LABEL', 'VALUE', 'UNIT'],
        ['1', 'JUALAN BULANAN', 'RM500,000', '/ bulan'],
        ['2', 'LEAD', '150', 'lead / minggu'],
        ['3', 'SITE VISIT', '6', 'site visit / minggu'],
        ['4', 'QUOTATION', '> RM600,000', '/ minggu'],
        [],
        ['KRA', 'KPI', 'TARGET', 'TACTICS', 'INITIATIVES', 'TIMELINE', 'PIC/CI'],
        ['Peningkatan Jualan Renovation', 'Jualan bulanan', 'RM500,000 / bulan',
            'Fokus kepada project bernilai RM100k dan ke atas', 'Give The Best Consultation', 'Jan–Dis', 'HOD : Zikri'],
        ['Lead Management', 'Lead dilayan & direkod', '150 lead / minggu',
            'Semua lead mesti dijawab < 15 minit', 'FB Ads, TikTok Ads', 'Mingguan', 'HOD : Zikri'],
        ['Quotation Performance', 'Nilai quotation dikeluarkan', '> RM600,000 / minggu',
            'Quotation keluar cepat selepas site visit', 'Template costing', 'Mingguan', 'HOD : Hafizan'],
    ];
}

beforeEach(function (): void {
    $this->seed();
});

/*
|--------------------------------------------------------------------------
| Membaca halaman perancangan
|--------------------------------------------------------------------------
*/

it('imports the planning rows in sheet order', function (): void {
    syncStrategy(strategyGrid());

    $rows = StrategyRow::orderBy('position')->get();

    expect($rows)->toHaveCount(3)
        ->and($rows[0]->kra)->toBe('Peningkatan Jualan Renovation')
        ->and($rows[0]->timeline)->toBe('Jan–Dis')
        ->and($rows[2]->kra)->toBe('Quotation Performance');
});

it('finds the table wherever it sits on the page', function (): void {
    // Tab ini bukan CSV. Mengandaikan baris pertama ialah tajuk lajur
    // bermakna sync gagal setiap kali perancang menambah satu baris
    // kosong di atas.
    $grid = array_merge([[], [], ['Nota mesyuarat'], []], strategyGrid());

    syncStrategy($grid);

    expect(StrategyRow::count())->toBe(3);
});

it('strips the HOD prefix from the owner name', function (): void {
    // Papan memaparkan awalan itu sendiri. Menyimpannya menghasilkan
    // "HOD : HOD : Zikri".
    syncStrategy(strategyGrid());

    expect(StrategyRow::orderBy('position')->first()->pic)->toBe('Zikri');
});

it('reads the vision from beside its marker', function (): void {
    syncStrategy(strategyGrid());

    expect(StrategyPlan::firstOrFail()->vision)->toContain('Klang Valley');
});

it('reads the vision from beneath its marker too', function (): void {
    // Kedua-dua susunan kelihatan semula jadi dalam sheet. Memaksa satu
    // bermakna sync gagal atas sebab reka letak.
    $grid = strategyGrid();
    $grid[3] = ['VISI'];
    array_splice($grid, 4, 0, [['Visi yang ditulis di bawah']]);

    syncStrategy($grid);

    expect(StrategyPlan::firstOrFail()->vision)->toBe('Visi yang ditulis di bawah');
});

it('imports the summary tiles with their units', function (): void {
    syncStrategy(strategyGrid());

    $tiles = StrategyTile::orderBy('position')->get();

    expect($tiles)->toHaveCount(4)
        ->and($tiles[0]->label)->toBe('JUALAN BULANAN')
        ->and($tiles[0]->value)->toBe('RM500,000')
        ->and($tiles[0]->unit)->toBe('/ bulan');
});

it('keeps the value as written instead of parsing it to a number', function (): void {
    // "> RM600,000" kehilangan maksudnya sebaik sahaja ia menjadi 600000.
    // Tiada satu pun nombor ini pernah dikira — ia dipaparkan.
    syncStrategy(strategyGrid());

    expect(StrategyTile::where('label', 'QUOTATION')->firstOrFail()->value)
        ->toBe('> RM600,000');
});

it('picks an icon from the label so nobody types icon names', function (): void {
    syncStrategy(strategyGrid());

    expect(StrategyTile::where('label', 'LEAD')->firstOrFail()->icon)->toBe('ph-users-three')
        ->and(StrategyTile::where('label', 'SITE VISIT')->firstOrFail()->icon)->toBe('ph-map-pin');
});

/*
|--------------------------------------------------------------------------
| Sheet ialah satu-satunya penulis
|--------------------------------------------------------------------------
*/

it('removes rows that were deleted from the sheet', function (): void {
    // Baris zombi dalam dokumen tadbir urus lebih teruk daripada tiada
    // dokumen — orang merancang mengikutnya.
    syncStrategy(strategyGrid());

    $dipangkas = strategyGrid();
    array_pop($dipangkas);

    syncStrategy($dipangkas);

    expect(StrategyRow::count())->toBe(2)
        ->and(StrategyRow::where('kra', 'Quotation Performance')->exists())->toBeFalse();
});

it('does not duplicate the plan when synced twice', function (): void {
    syncStrategy(strategyGrid());
    syncStrategy(strategyGrid());

    expect(StrategyPlan::count())->toBe(1)
        ->and(StrategyRow::count())->toBe(3);
});

it('keeps each service plan separate', function (): void {
    syncStrategy(strategyGrid());
    syncStrategy(strategyGrid(), Service::where('key', 'kabinet')->firstOrFail());

    $renovation = Service::where('key', 'renovation')->firstOrFail();

    expect(StrategyRow::where('service_id', $renovation->id)->count())->toBe(3)
        ->and(StrategyPlan::count())->toBe(2);
});

/*
|--------------------------------------------------------------------------
| Kegagalan mesti bercakap
|--------------------------------------------------------------------------
*/

it('names the columns it was looking for when the table is missing', function (): void {
    // "Sync gagal" menghantar admin meneka; menamakan lajur menghantar
    // mereka terus ke sheet.
    $result = syncStrategy([['Tiada apa-apa di sini'], ['Baris kosong']]);

    expect($result['status'])->toBe('failed')
        ->and($result['message'])->toContain('KRA')
        ->and($result['message'])->toContain('KPI');
});

it('reports a header with no rows beneath it', function (): void {
    $result = syncStrategy([
        ['KRA', 'KPI', 'TARGET', 'TACTICS', 'INITIATIVES', 'TIMELINE', 'PIC/CI'],
    ]);

    expect($result['status'])->toBe('failed');
});

it('does not mistake the summary strip for the main table', function (): void {
    // "KPI" muncul juga dalam tajuk halaman dan dalam jalur ringkasan.
    // Memadankannya di sana menghalakan pembaca ke bahagian yang salah.
    syncStrategy(strategyGrid());

    expect(StrategyRow::orderBy('position')->first()->kra)
        ->not->toBe('1')
        ->and(StrategyRow::count())->toBe(3);
});

it('survives one blank row inside the table', function (): void {
    // Satu baris kosong di tengah biasanya jarak visual, bukan penghujung.
    $grid = strategyGrid();
    array_splice($grid, 13, 0, [[]]);

    syncStrategy($grid);

    expect(StrategyRow::count())->toBe(3);
});

it('stops at a footnote separated by two blank rows', function (): void {
    $grid = array_merge(strategyGrid(), [[], [], ['Disediakan oleh Bahagian Strategi']]);

    syncStrategy($grid);

    expect(StrategyRow::where('kra', 'Disediakan oleh Bahagian Strategi')->exists())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Tab DBENA yang sebenar
|--------------------------------------------------------------------------
*/

/**
 * Salinan tepat tab RENOVATION.
 *
 * Vision ialah satu LAJUR dengan sel bergabung menegak, dan Lead
 * Management memegang dua sasaran dengan setiap lajur lain digabung
 * merentasi kedua-dua baris. Google memulangkan sel bergabung pada baris
 * pertamanya sahaja, jadi baris kedua tiba hampir kosong.
 */
function dbenaStrategyGrid(): array
{
    $visi = 'Menjadi pilihan utama pelanggan untuk kerja Major Renovation '
        .'rumah yang dipercayai, tersusun dan berkualiti di Klang Valley';

    return [
        ['Strategic Planning & KPI Alignment - RENOVATION'],
        [],
        ['', 'RENOVATION'],
        ['Vision', 'KRA', 'KPI', 'Target', 'Tactics', 'Initiatives', 'Timeline', 'PIC/CI'],
        [$visi, 'Peningkatan Jualan Renovation', 'Jualan bulanan', 'RM500,000 / bulan',
            'Fokus kepada project renovation bernilai RM100k dan ke atas',
            'Give The Best Consultation, Build Rapport', 'Jan–Dis', 'HOD : Zikri'],
        ['', 'Lead Management', 'Lead dilayan & direkod', '150 lead / minggu',
            'Semua lead mesti dijawab cepat < 15 minit', 'FB Ads, Tiktok Ads', 'Mingguan', 'HOD : Zikri'],
        ['', '', '', '25 lead / hari', '', '', '', ''],
        ['', 'Site Visit Conversion', 'Site visit berjaya dibuat', '6 site visit / minggu',
            'Qualify client sebelum site visit', 'Script qualification', 'Mingguan', 'HOD : Zikri'],
        ['', 'Quotation Performance', 'Nilai quotation dikeluarkan', '> RM600,000 / minggu',
            'Quotation keluar cepat selepas site visit', 'Template costing', 'Mingguan', 'HOD : Hafizan'],
        ['', 'Closing Sales', 'Sales Collection', 'RM150,000 / bulan',
            'Fokus kepada quotation hot prospect', 'Follow up 3-7-14 hari', 'Bulanan', 'HOD : Zikri'],
        ['', 'Project Delivery', 'Project Ahead dari timeline', 'Project siap awal dari jadual',
            'Kawal site, worker, material', 'Work schedule, progress photo', 'Mingguan', 'HOD : Azhari'],
        ['', 'Claim & Collection', 'Invoice/claim dihantar ikut progress', '100% claim ikut milestone',
            'Pastikan cashflow masuk ikut kerja siap', 'Claim tracker', 'Mingguan', 'HOD : Hafizan'],
        ['', 'Customer Satisfaction', 'Testimoni / review client', '1 testimoni setiap completed project',
            'Jadikan completed project sebagai marketing asset', 'Before-after photo', 'Bulanan', 'HOD : Azhari'],
    ];
}

it('imports every KRA from the real tab', function (): void {
    syncStrategy(dbenaStrategyGrid());

    expect(StrategyRow::count())->toBe(8)
        ->and(StrategyRow::orderBy('position')->pluck('kra')->first())
        ->toBe('Peningkatan Jualan Renovation');
});

it('reads the vision from its merged column', function (): void {
    // Sel bergabung menegak merentasi setiap baris, jadi Google
    // memulangkan teksnya pada baris data pertama sahaja.
    syncStrategy(dbenaStrategyGrid());

    expect(StrategyPlan::firstOrFail()->vision)
        ->toContain('Major Renovation')
        ->and(StrategyPlan::firstOrFail()->vision)->toContain('Klang Valley');
});

it('does not lose the second target of a merged row', function (): void {
    // "25 lead / hari" tiba pada baris dengan KRA kosong kerana KRA
    // digabung. Melangkaunya sebagai baris kosong membuang salah satu
    // nombor yang paling kerap disebut dalam pelan ini.
    syncStrategy(dbenaStrategyGrid());

    $lead = StrategyRow::where('kra', 'Lead Management')->firstOrFail();

    expect($lead->target)->toContain('150 lead / minggu')
        ->and($lead->target)->toContain('25 lead / hari');
});

it('does not turn a merged continuation into its own KRA', function (): void {
    syncStrategy(dbenaStrategyGrid());

    expect(StrategyRow::where('kra', '25 lead / hari')->exists())->toBeFalse()
        ->and(StrategyRow::whereNull('kra')->exists())->toBeFalse();
});

it('builds the eight summary tiles from the target column', function (): void {
    // Reka bentuk asal membina lapan petaknya daripada sasaran yang sama
    // yang tersenarai dalam jadual. Menyalin nombor ke jalur berasingan
    // mencipta sumber kedua yang akan menyimpang.
    syncStrategy(dbenaStrategyGrid());

    expect(StrategyTile::count())->toBe(8);
});

it('leaves a target with no measurable number out of the tiles', function (): void {
    // "Project siap awal dari jadual" ialah komitmen, bukan nombor untuk
    // dipaparkan besar-besar. Reka bentuk asal turut meninggalkannya.
    syncStrategy(dbenaStrategyGrid());

    expect(StrategyTile::where('value', 'like', '%Project siap%')->exists())->toBeFalse();
});

it('splits each target into a value and a unit', function (): void {
    syncStrategy(dbenaStrategyGrid());

    $tiles = StrategyTile::orderBy('position')->get();

    expect($tiles[0]->value)->toBe('RM500,000')
        ->and($tiles[0]->unit)->toBe('/ bulan')
        ->and($tiles[1]->value)->toBe('150')
        ->and($tiles[1]->unit)->toBe('lead / minggu')
        ->and($tiles[2]->value)->toBe('25')
        ->and($tiles[2]->unit)->toBe('lead / hari');
});

it('keeps the greater-than sign on the quotation target', function (): void {
    // "> RM600,000" kehilangan maksudnya sebaik sahaja ia menjadi 600000.
    syncStrategy(dbenaStrategyGrid());

    expect(StrategyTile::where('value', 'like', '%600,000%')->firstOrFail()->value)
        ->toContain('>');
});

it('keeps a percent target whole', function (): void {
    syncStrategy(dbenaStrategyGrid());

    $claim = StrategyTile::where('unit', 'like', '%milestone%')->firstOrFail();

    expect($claim->value)->toBe('100%');
});

it('does not mistake the yellow banner row for the header', function (): void {
    // Baris "RENOVATION" duduk antara tajuk dan baris tajuk sebenar.
    syncStrategy(dbenaStrategyGrid());

    expect(StrategyRow::where('kra', 'RENOVATION')->exists())->toBeFalse();
});

it('strips HOD from every owner in the real tab', function (): void {
    syncStrategy(dbenaStrategyGrid());

    expect(StrategyRow::pluck('pic')->unique()->sort()->values()->all())
        ->toBe(['Azhari', 'Hafizan', 'Zikri']);
});

it('does not let the Vision column be read as PIC', function (): void {
    // "ci" sebagai subrentetan memadankan perkataan biasa dan boleh
    // merampas lajur yang salah.
    syncStrategy(dbenaStrategyGrid());

    expect(StrategyRow::orderBy('position')->first()->pic)->toBe('Zikri');
});

it('gives the daily and weekly lead targets different icons', function (): void {
    // Kedua-duanya berkongsi KPI yang sama, jadi hanya perkataan tempoh
    // membezakannya. Ikon yang sama menjadikannya kelihatan seperti satu
    // petak yang dipaparkan dua kali.
    syncStrategy(dbenaStrategyGrid());

    $tiles = StrategyTile::orderBy('position')->get();

    expect($tiles[1]->icon)->toBe('ph-users-three')
        ->and($tiles[2]->icon)->toBe('ph-calendar-blank');
});

it('does not give the collection tile a sales chart icon', function (): void {
    // "Sales Collection" mengandungi kedua-dua perkataan. Padanan pada
    // 'sales' menjadikan dua petak berbeza kelihatan seperti mengukur
    // perkara yang sama.
    syncStrategy(dbenaStrategyGrid());

    expect(StrategyTile::where('label', 'Sales Collection')->firstOrFail()->icon)
        ->toBe('ph-hand-coins');
});
