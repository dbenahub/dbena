<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\Admin\SheetManager;
use App\Livewire\Dashboard\ServiceDetail;
use App\Models\Service;
use App\Models\SheetIntegration;
use App\Models\StrategyPlan;
use App\Models\StrategyRow;
use App\Models\StrategyTile;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed();

    $this->admin = User::where('role', UserRole::Admin)->firstOrFail();
    $this->user = User::where('role', UserRole::User)->firstOrFail();
    $this->renovation = Service::where('key', 'renovation')->firstOrFail();

    StrategyPlan::create([
        'service_id' => $this->renovation->id,
        'heading' => 'Strategic Planning & KPI Alignment - RENOVATION',
        'vision' => 'Menjadi pilihan utama pelanggan di Klang Valley',
        'synced_at' => now(),
    ]);

    StrategyTile::create([
        'service_id' => $this->renovation->id, 'position' => 1,
        'label' => 'JUALAN BULANAN', 'value' => 'RM500,000', 'unit' => '/ bulan',
        'icon' => 'ph-chart-line-up',
    ]);

    StrategyRow::create([
        'service_id' => $this->renovation->id, 'position' => 1,
        'kra' => 'Peningkatan Jualan Renovation', 'kpi' => 'Jualan bulanan',
        'target' => 'RM500,000 / bulan', 'tactics' => 'Fokus project RM100k ke atas',
        'initiatives' => 'Give The Best Consultation', 'timeline' => 'Jan-Dis', 'pic' => 'Zikri',
    ]);
});

/*
|--------------------------------------------------------------------------
| Paparan
|--------------------------------------------------------------------------
*/

it('shows the board on the service dashboard', function (): void {
    Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->assertSee('Peningkatan Jualan Renovation')
        ->assertSee('Klang Valley')
        ->assertSee('RM500,000');
});

it('shows only this service’s plan', function (): void {
    Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'kabinet'])
        ->assertDontSee('Peningkatan Jualan Renovation');
});

it('explains who can connect it when nothing is synced', function (): void {
    // "Tiada data" menghantar pengguna mencari butang yang tidak wujud
    // untuk mereka.
    Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'kabinet'])
        ->assertSee(__('strategy.empty_title'));
});

/*
|--------------------------------------------------------------------------
| Paparan sahaja — untuk SEMUA peranan
|--------------------------------------------------------------------------
*/

it('offers no way to edit the board from the dashboard', function (): void {
    // Sheet ialah satu-satunya penulis. Dua penulis kepada data yang sama
    // bermakna suntingan hilang secara senyap pada sync berikutnya —
    // termasuk suntingan Admin.
    $kaedah = get_class_methods(ServiceDetail::class);

    foreach (['saveStrategy', 'updateStrategy', 'editStrategy', 'deleteStrategy'] as $tulis) {
        expect($kaedah)->not->toContain($tulis);
    }
});

it('says the sheet is the writer', function (): void {
    Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->assertSee(__('strategy.view_only'));
});

it('shows the same read-only board to an admin', function (): void {
    Livewire::actingAs($this->admin)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->assertSee('Peningkatan Jualan Renovation')
        ->assertSee(__('strategy.view_only'));
});

/*
|--------------------------------------------------------------------------
| Sambung & sync — Admin sahaja
|--------------------------------------------------------------------------
*/

it('refuses a plain user the strategy settings', function (): void {
    // Butang yang disorok bukan sekatan.
    $this->actingAs($this->user)->get('/admin/sheets')->assertForbidden();
});

it('lets an admin save the link once for every service', function (): void {
    // Menyimpan pautan lima kali bermakna lima peluang untuk ia menyimpang.
    Livewire::actingAs($this->admin)
        ->test(SheetManager::class)
        ->set('strategyUrl', 'https://docs.google.com/spreadsheets/d/strategi1234567890abc/edit')
        ->set('strategyTabs.'.$this->renovation->id, 'STRATEGIC_RENOVATION')
        ->call('saveStrategySheets');

    $baris = SheetIntegration::where('kind', 'strategy')
        ->where('service_id', $this->renovation->id)
        ->firstOrFail();

    expect($baris->tab_name)->toBe('STRATEGIC_RENOVATION')
        ->and($baris->spreadsheet_id)->toBe('strategi1234567890abc')
        ->and($baris->connected)->toBeTrue();
});

it('refuses to sync a service with no tab name', function (): void {
    // Tanpa nama tab, Google memulangkan helaian PERTAMA fail — jadi empat
    // servis akan menyegerak data servis yang salah tanpa sebarang ralat.
    Livewire::actingAs($this->admin)
        ->test(SheetManager::class)
        ->set('strategyUrl', 'https://docs.google.com/spreadsheets/d/strategi1234567890abc/edit')
        ->set('strategyTabs.'.$this->renovation->id, '')
        ->call('syncStrategy', $this->renovation->id)
        ->assertDispatched('dbena-toast');

    expect(StrategyRow::where('service_id', $this->renovation->id)->count())->toBe(1);
});

it('keeps the strategy integration apart from critical and project', function (): void {
    // Ketiga-tiganya boleh mempunyai service_id NULL. Mencari mengikut
    // service_id sahaja memulangkan baris yang salah.
    SheetIntegration::firstOrCreate(['kind' => 'critical', 'service_id' => null]);
    SheetIntegration::firstOrCreate(['kind' => 'project', 'service_id' => null]);
    $strategi = SheetIntegration::firstOrCreate(['kind' => 'strategy', 'service_id' => null]);

    expect(SheetIntegration::where('kind', 'strategy')->whereNull('service_id')->first()->id)
        ->toBe($strategi->id)
        ->and(SheetIntegration::critical()->whereNull('service_id')->first()->id)
        ->not->toBe($strategi->id);
});

it('does not sweep strategy rows into the critical data sync', function (): void {
    // Enjin Data Kritikal akan cuba membaca tab perancangan dan melaporkan
    // sheet itu rosak.
    SheetIntegration::firstOrCreate(
        ['kind' => 'strategy', 'service_id' => $this->renovation->id],
        ['sync_enabled' => true, 'url' => 'https://docs.google.com/spreadsheets/d/abc12345678901234567/edit']
    );

    $diambil = SheetIntegration::critical()->where('sync_enabled', true)->get();

    expect($diambil->where('kind', 'strategy'))->toBeEmpty();
});

/*
|--------------------------------------------------------------------------
| Penjajaran sasaran antara dua sheet
|--------------------------------------------------------------------------
*/

it('marks a target the two sheets disagree on', function (): void {
    // Kedua-dua nombor kelihatan rasmi pada skrinnya sendiri, jadi tanpa
    // penanda ini pemilik yang mencapai satu daripadanya percaya dia
    // sudah selamat.
    StrategyRow::create([
        'service_id' => $this->renovation->id, 'position' => 2,
        'kra' => 'Closing Sales', 'kpi' => 'Sales Collection',
        'target' => 'RM999,000 / bulan', 'pic' => 'Zikri',
    ]);

    Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->assertSee(__('align.critical'))
        ->assertSee('RM999,000');
});

it('stays quiet when the two sheets agree', function (): void {
    // Amaran palsu mengajar orang mengabaikan penunjuk ini sepenuhnya.
    Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->assertDontSee(__('align.critical'));
});

it('tells an admin which sheet to edit', function (): void {
    // Kedua-dua nombor dimiliki oleh Google Sheet, bukan oleh dashboard.
    StrategyRow::create([
        'service_id' => $this->renovation->id, 'position' => 2,
        'kra' => 'Closing Sales', 'kpi' => 'Sales Collection',
        'target' => 'RM999,000 / bulan', 'pic' => 'Zikri',
    ]);

    Livewire::actingAs($this->admin)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->assertSee(__('align.body_admin'));

    Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->assertSee(__('align.body'));
});

/*
|--------------------------------------------------------------------------
| Akses sheet dari kepala papan — Admin sahaja
|--------------------------------------------------------------------------
*/

/** Sambungkan tab strategic planning servis ini. */
function connectStrategySheet(int $serviceId): SheetIntegration
{
    return SheetIntegration::updateOrCreate(
        ['kind' => 'strategy', 'service_id' => $serviceId],
        [
            'url' => 'https://docs.google.com/spreadsheets/d/pelanstrategik12345678/edit',
            'tab_name' => 'RENOVATION',
            'connected' => true,
        ]
    );
}

it('gives an admin a link to the planning sheet', function (): void {
    connectStrategySheet($this->renovation->id);

    Livewire::actingAs($this->admin)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->assertSee('pelanstrategik12345678', false);
});

it('does not give a plain user that link', function (): void {
    // Tab ini boleh DISUNTING oleh sesiapa yang membukanya, dan pelan
    // strategik ialah dokumen tadbir urus yang diluluskan pengurusan.
    // Menghantar pengguna ke sel yang boleh diubah menjemput suntingan
    // yang tiada siapa minta dan tiada siapa akan perasan.
    connectStrategySheet($this->renovation->id);

    Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->assertDontSee('pelanstrategik12345678', false);
});

it('shows no link when no planning sheet is connected', function (): void {
    Livewire::actingAs($this->admin)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->assertDontSee(__('project.view_sheet'));
});

it('links to the planning sheet, not the critical data sheet', function (): void {
    // Ketiga-tiga jenis integrasi wujud untuk servis ini. Mencari tanpa
    // jenis memulangkan pautan ke sheet yang salah.
    SheetIntegration::updateOrCreate(
        ['kind' => 'critical', 'service_id' => $this->renovation->id],
        ['url' => 'https://docs.google.com/spreadsheets/d/datakritikal987654321/edit']
    );

    connectStrategySheet($this->renovation->id);

    Livewire::actingAs($this->admin)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->assertSee('pelanstrategik12345678', false)
        ->assertDontSee('datakritikal987654321', false);
});

it('still links when only the spreadsheet id was saved', function (): void {
    // ID mentah yang ditampal tidak menghasilkan medan url, dan butang
    // hilang senyap walaupun sheet bersambung dan sedang menyegerak.
    SheetIntegration::updateOrCreate(
        ['kind' => 'strategy', 'service_id' => $this->renovation->id],
        ['spreadsheet_id' => 'pelanstrategik12345678', 'tab_name' => 'RENOVATION', 'connected' => true]
    );

    Livewire::actingAs($this->admin)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->assertSee('pelanstrategik12345678', false);
});
