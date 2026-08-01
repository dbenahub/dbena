<?php

declare(strict_types=1);

use App\Contracts\SheetReader;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Service;
use App\Models\SheetIntegration;
use App\Services\Sheets\ProjectSyncService;

/** Pembaca palsu yang memulangkan grid yang kita tetapkan. */
function fakeProjectReader(array $grid): SheetReader
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

/** Grid dengan tajuk sebenar Master List of Project. */
function projectGrid(array $rows): array
{
    return array_merge([[
        'Project Code', 'Date', 'Client Name', 'PIC SALES', 'Type Of Project',
        'Client Phone Number / Whatsapp', 'Address', 'Email',
        'Contract Amount', 'Variation Order (VO)', 'Status',
    ]], $rows);
}

function syncProjects(array $grid): array
{
    $integration = SheetIntegration::create(['kind' => 'project', 'connected' => true]);

    return (new ProjectSyncService(fakeProjectReader($grid)))->sync($integration);
}

beforeEach(function (): void {
    $this->seed();
});

/*
|--------------------------------------------------------------------------
| Import asas
|--------------------------------------------------------------------------
*/

it('imports a project row into the master list', function (): void {
    syncProjects(projectGrid([[
        'PRJ-240501', '01 May 2024', 'Encik Ahmad Zulkifli', 'Farid Hamzah',
        'Renovation', '012-345 6789', 'No 12, Kajang', 'ahmad@example.com',
        'RM 85,000.00', 'RM 5,000.00', 'In Progress',
    ]]));

    $project = Project::where('code', 'PRJ-240501')->firstOrFail();

    expect($project->client_name)->toBe('Encik Ahmad Zulkifli')
        ->and((float) $project->contract_amount)->toBe(85000.0)
        ->and((float) $project->variation_order)->toBe(5000.0)
        ->and($project->status)->toBe(ProjectStatus::InProgress)
        ->and($project->service->key)->toBe('renovation');
});

it('strips currency symbols and commas from amounts', function (): void {
    // Sheet diisi oleh manusia. "RM 85,000.00" ialah nilai biasa, bukan
    // kes tepi.
    syncProjects(projectGrid([[
        'PRJ-1', '01 May 2024', 'Klien', '', 'Renovation', '', '', '',
        'RM 1,250,500.50', 'RM 0.00', 'Pending',
    ]]));

    expect((float) Project::where('code', 'PRJ-1')->firstOrFail()->contract_amount)
        ->toBe(1250500.50);
});

it('matches a project to its service category by name', function (): void {
    syncProjects(projectGrid([
        ['PRJ-1', '', 'A', '', 'Renovation', '', '', '', '0', '0', 'Pending'],
        ['PRJ-2', '', 'B', '', 'Kabinet', '', '', '', '0', '0', 'Pending'],
        ['PRJ-3', '', 'C', '', 'Bina Rumah', '', '', '', '0', '0', 'Pending'],
    ]));

    expect(Project::where('code', 'PRJ-1')->firstOrFail()->service->key)->toBe('renovation')
        ->and(Project::where('code', 'PRJ-2')->firstOrFail()->service->key)->toBe('kabinet')
        ->and(Project::where('code', 'PRJ-3')->firstOrFail()->service->key)->toBe('bina-rumah');
});

it('matches the English service name too', function (): void {
    syncProjects(projectGrid([
        ['PRJ-1', '', 'A', '', 'House Construction', '', '', '', '0', '0', 'Pending'],
    ]));

    expect(Project::where('code', 'PRJ-1')->firstOrFail()->service->key)->toBe('bina-rumah');
});

/*
|--------------------------------------------------------------------------
| Sheet ialah satu-satunya sumber kebenaran
|--------------------------------------------------------------------------
*/

it('updates the same row instead of creating a duplicate', function (): void {
    // Padanan mengikut kod projek, jadi membetulkan baris dalam sheet
    // mengemas kini rekod yang sama.
    $grid = fn (string $client) => projectGrid([[
        'PRJ-240501', '01 May 2024', $client, '', 'Renovation', '', '', '',
        '85000', '0', 'In Progress',
    ]]);

    syncProjects($grid('Nama Asal'));
    syncProjects($grid('Nama Dibetulkan'));

    expect(Project::where('code', 'PRJ-240501')->count())->toBe(1)
        ->and(Project::where('code', 'PRJ-240501')->firstOrFail()->client_name)
        ->toBe('Nama Dibetulkan');
});

/*
|--------------------------------------------------------------------------
| Baris yang tidak boleh digunakan
|--------------------------------------------------------------------------
*/

it('skips rows with no project code', function (): void {
    // Baris kosong, baris jumlah dan nota tidak sepatutnya menjadi rekod
    // hantu yang muncul dalam kiraan.
    $result = syncProjects(projectGrid([
        ['PRJ-1', '', 'Klien Sah', '', 'Renovation', '', '', '', '0', '0', 'Pending'],
        ['', '', 'JUMLAH', '', '', '', '', '', '85000', '0', ''],
        ['', '', '', '', '', '', '', '', '', '', ''],
    ]));

    expect(Project::count())->toBe(1)
        ->and($result['skipped'])->toBe(2);
});

it('skips a row whose project type matches no service', function (): void {
    $result = syncProjects(projectGrid([
        ['PRJ-1', '', 'Klien', '', 'Landskap', '', '', '', '0', '0', 'Pending'],
    ]));

    expect(Project::count())->toBe(0)
        ->and($result['unknownServices'])->toContain('Landskap');
});

it('reports a missing required column instead of importing nothing quietly', function (): void {
    $result = syncProjects([
        ['Date', 'Address', 'Status'],
        ['01 May 2024', 'Kajang', 'Pending'],
    ]);

    expect($result['status'])->toBe('failed')
        ->and($result['message'])->toContain(__('project.field.code'));
});

/*
|--------------------------------------------------------------------------
| Status
|--------------------------------------------------------------------------
*/

it('reads status regardless of spelling or case', function (): void {
    expect(ProjectStatus::fromSheet('In Progress'))->toBe(ProjectStatus::InProgress)
        ->and(ProjectStatus::fromSheet('IN-PROGRESS'))->toBe(ProjectStatus::InProgress)
        ->and(ProjectStatus::fromSheet('ongoing'))->toBe(ProjectStatus::InProgress)
        ->and(ProjectStatus::fromSheet('Quotation'))->toBe(ProjectStatus::Quotation)
        ->and(ProjectStatus::fromSheet('Completed'))->toBe(ProjectStatus::Completed)
        ->and(ProjectStatus::fromSheet('Closed'))->toBe(ProjectStatus::Closed);
});

it('falls back to Pending for an unrecognised status', function (): void {
    // Menolak baris kerana ejaan status bermakna projek hilang daripada
    // dashboard tanpa sesiapa tahu sebabnya.
    expect(ProjectStatus::fromSheet('Tunggu bos approve'))->toBe(ProjectStatus::Pending)
        ->and(ProjectStatus::fromSheet(''))->toBe(ProjectStatus::Pending)
        ->and(ProjectStatus::fromSheet(null))->toBe(ProjectStatus::Pending);
});

/*
|--------------------------------------------------------------------------
| Tarikh
|--------------------------------------------------------------------------
*/

it('reads dates in the formats Google actually sends', function (): void {
    syncProjects(projectGrid([
        ['PRJ-1', '01 May 2024', 'A', '', 'Renovation', '', '', '', '0', '0', 'Pending'],
        ['PRJ-2', '2024-05-08', 'B', '', 'Renovation', '', '', '', '0', '0', 'Pending'],
    ]));

    expect(Project::where('code', 'PRJ-1')->firstOrFail()->project_date->format('Y-m-d'))
        ->toBe('2024-05-01')
        ->and(Project::where('code', 'PRJ-2')->firstOrFail()->project_date->format('Y-m-d'))
        ->toBe('2024-05-08');
});

it('leaves an unparseable date empty rather than guessing today', function (): void {
    // Tarikh palsu yang kelihatan munasabah lebih teruk daripada tiada
    // tarikh.
    syncProjects(projectGrid([
        ['PRJ-1', 'nanti', 'A', '', 'Renovation', '', '', '', '0', '0', 'Pending'],
    ]));

    expect(Project::where('code', 'PRJ-1')->firstOrFail()->project_date)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Nilai projek
|--------------------------------------------------------------------------
*/

it('counts variation orders in the project value', function (): void {
    // Melaporkan jumlah kontrak sahaja memandang rendah buku pesanan
    // setiap kali VO diluluskan.
    $project = new Project(['contract_amount' => 85000, 'variation_order' => 5000]);

    expect($project->totalValue())->toBe(90000.0);
});

/*
|--------------------------------------------------------------------------
| Sheet DBENA sebenar — MASTER LIST PROJECT DBENA
|--------------------------------------------------------------------------
*/

it('reads day-first dates the way the DBENA sheet writes them', function (): void {
    // Sheet menulis 05/01/26 untuk 5 Januari. Carbon::parse mengikut
    // strtotime, yang menganggap garis miring sebagai format Amerika —
    // jadi 05/01/26 menjadi 1 Mei dan 21/11/25 gagal sepenuhnya.
    //
    // Tarikh yang salah empat bulan lebih teruk daripada tarikh yang
    // hilang: ia kelihatan munasabah dan tiada siapa menyemaknya.
    syncProjects(projectGrid([
        ['001/DB/2026/', '05/01/26', 'Fazli Aziz', 'Hazira', 'CABINET', '', '', '', '10566', '', 'Closed'],
        ['002/DB/2026/', '21/11/25', 'Fazilah', 'Farez', 'CABINET', '', '', '', '18000', '', 'Closed'],
        ['013/DB/2026/', '18/02/26', 'Adlin', 'Hazira', 'CABINET', '', '', '', '13759', '', 'Closed'],
    ]));

    expect(Project::where('code', '001/DB/2026/')->firstOrFail()->project_date->format('Y-m-d'))
        ->toBe('2026-01-05')
        ->and(Project::where('code', '002/DB/2026/')->firstOrFail()->project_date->format('Y-m-d'))
        ->toBe('2025-11-21')
        ->and(Project::where('code', '013/DB/2026/')->firstOrFail()->project_date->format('Y-m-d'))
        ->toBe('2026-02-18');
});

it('matches CABINET to the Kabinet service', function (): void {
    // Sheet menulis CABINET; servis dinamakan Kabinet / Cabinetry.
    // Menuntut padanan tepat bermakna seluruh kategori hilang daripada
    // dashboard.
    syncProjects(projectGrid([
        ['001/DB/2026/', '05/01/26', 'Fazli Aziz', '', 'CABINET', '', '', '', '10566', '', 'Closed'],
    ]));

    expect(Project::where('code', '001/DB/2026/')->firstOrFail()->service->key)->toBe('kabinet');
});

it('matches MASJID to the Mihrab service through the alias list', function (): void {
    syncProjects(projectGrid([
        ['050/DB/2026/', '05/01/26', 'Surau Al-Ikhlas', '', 'MASJID', '', '', '', '320000', '', 'Quotation'],
    ]));

    expect(Project::where('code', '050/DB/2026/')->firstOrFail()->service->key)->toBe('mihrab');
});

it('imports every project type the DBENA dropdown offers', function (): void {
    $result = syncProjects(projectGrid([
        ['A/1', '05/01/26', 'A', '', 'CABINET', '', '', '', '1', '', 'Quotation'],
        ['A/2', '05/01/26', 'B', '', 'RENOVATION', '', '', '', '1', '', 'Quotation'],
        ['A/3', '05/01/26', 'C', '', 'BINA RUMAH', '', '', '', '1', '', 'Quotation'],
        ['A/4', '05/01/26', 'D', '', 'MASJID', '', '', '', '1', '', 'Quotation'],
    ]));

    expect($result['unknownServices'])->toBeEmpty()
        ->and(Project::count())->toBe(4);
});

it('records Turned Down as its own status, not Pending', function (): void {
    // Menjatuhkannya ke Pending menjadikan projek yang ditolak kelihatan
    // seperti projek yang masih menunggu, dan corong nampak lebih sihat
    // daripada keadaan sebenar.
    syncProjects(projectGrid([
        ['A/1', '05/01/26', 'A', '', 'CABINET', '', '', '', '1', '', 'Turned Down'],
    ]));

    expect(Project::where('code', 'A/1')->firstOrFail()->status)
        ->toBe(ProjectStatus::TurnedDown)
        ->and(ProjectStatus::TurnedDown->isFinished())->toBeTrue();
});

it('keeps project codes that contain slashes', function (): void {
    syncProjects(projectGrid([
        ['001/DB/2026/', '05/01/26', 'Fazli Aziz', '', 'CABINET', '', '', '', '10566', '', 'Closed'],
    ]));

    expect(Project::where('code', '001/DB/2026/')->exists())->toBeTrue();
});

it('reads amounts already formatted as RM by the sheet', function (): void {
    syncProjects(projectGrid([
        ['A/1', '05/01/26', 'A', '', 'CABINET', '', '', '', 'RM10,566.00', 'RM1,200.00', 'Closed'],
    ]));

    $project = Project::where('code', 'A/1')->firstOrFail();

    expect((float) $project->contract_amount)->toBe(10566.0)
        ->and((float) $project->variation_order)->toBe(1200.0);
});
