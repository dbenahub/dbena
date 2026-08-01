<?php

declare(strict_types=1);

use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Livewire\Dashboard\ProjectList;
use App\Models\Project;
use App\Models\Service;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed();

    $this->admin = User::where('role', UserRole::Admin)->firstOrFail();
    $this->user = User::where('role', UserRole::User)->firstOrFail();

    $this->renovation = Service::where('key', 'renovation')->firstOrFail();
    $this->kabinet = Service::where('key', 'kabinet')->firstOrFail();

    Project::create([
        'code' => 'PRJ-240501', 'service_id' => $this->renovation->id,
        'project_date' => '2024-05-01', 'client_name' => 'Encik Ahmad Zulkifli',
        'pic_sales' => 'Farid Hamzah', 'phone' => '012-345 6789',
        'email' => 'ahmad@example.com', 'address' => 'No 12, Kajang',
        'contract_amount' => 85000, 'variation_order' => 5000,
        'status' => ProjectStatus::InProgress,
    ]);

    Project::create([
        'code' => 'PRJ-240502', 'service_id' => $this->kabinet->id,
        'project_date' => '2024-05-03', 'client_name' => 'Puan Noraini',
        'pic_sales' => 'Siti Aisyah', 'contract_amount' => 32000,
        'variation_order' => 1200, 'status' => ProjectStatus::Quotation,
    ]);

    Project::create([
        'code' => 'PRJ-240503', 'service_id' => $this->renovation->id,
        'project_date' => '2024-05-05', 'client_name' => 'Surau Al-Ikhlas',
        'contract_amount' => 320000, 'status' => ProjectStatus::Closed,
    ]);
});

/*
|--------------------------------------------------------------------------
| Paparan & penapisan mengikut kategori
|--------------------------------------------------------------------------
*/

it('lists every project when no category is chosen', function (): void {
    Livewire::actingAs($this->user)
        ->test(ProjectList::class)
        ->assertSee('PRJ-240501')
        ->assertSee('PRJ-240502')
        ->assertSee('PRJ-240503');
});

it('narrows the list to one service category', function (): void {
    Livewire::actingAs($this->user)
        ->test(ProjectList::class)
        ->call('selectService', 'kabinet')
        ->assertSee('PRJ-240502')
        ->assertDontSee('PRJ-240501');
});

it('counts every project in the tiles, not just the current page', function (): void {
    // Petak yang berubah semasa menatal halaman tidak boleh dipercayai
    // sebagai jumlah.
    $component = Livewire::actingAs($this->user)
        ->test(ProjectList::class)
        ->set('perPage', 10);

    expect($component->viewData('totalProjects'))->toBe(3)
        ->and($component->viewData('closedProjects'))->toBe(1);
});

it('searches by code, client and owner', function (): void {
    foreach (['PRJ-240502', 'Noraini', 'Siti Aisyah'] as $term) {
        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->set('search', $term)
            ->assertSee('PRJ-240502')
            ->assertDontSee('PRJ-240501');
    }
});

it('filters by status', function (): void {
    Livewire::actingAs($this->user)
        ->test(ProjectList::class)
        ->set('status', ProjectStatus::Closed->value)
        ->assertSee('PRJ-240503')
        ->assertDontSee('PRJ-240501');
});

it('returns to page one when a filter changes', function (): void {
    // Kekal di halaman 4 selepas menapis menunjukkan senarai kosong yang
    // kelihatan seperti tiada keputusan.
    Livewire::actingAs($this->user)
        ->test(ProjectList::class)
        ->set('page', 2)
        ->set('search', 'Noraini')
        ->assertSet('paginators.page', 1);
});

/*
|--------------------------------------------------------------------------
| Kebenaran
|--------------------------------------------------------------------------
*/

it('hides the export button from a plain user', function (): void {
    Livewire::actingAs($this->user)
        ->test(ProjectList::class)
        ->assertDontSee(__('project.export'));
});

it('shows the export button to an admin', function (): void {
    Livewire::actingAs($this->admin)
        ->test(ProjectList::class)
        ->assertSee(__('project.export'));
});

it('refuses the export route for a plain user', function (): void {
    // Butang yang disorok bukan sekatan. Fail ini membawa senarai
    // pelanggan penuh keluar daripada sistem.
    $this->actingAs($this->user)
        ->get(route('projek.eksport'))
        ->assertForbidden();
});

it('lets an admin download the export', function (): void {
    $response = $this->actingAs($this->admin)->get(route('projek.eksport'));

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('text/csv');
});

it('respects the category filter in the export filename', function (): void {
    $response = $this->actingAs($this->admin)
        ->get(route('projek.eksport', ['servis' => 'kabinet']));

    expect($response->headers->get('content-disposition'))->toContain('kabinet');
});

/*
|--------------------------------------------------------------------------
| Paparan sahaja
|--------------------------------------------------------------------------
*/

it('offers no way to create or edit a project from the dashboard', function (): void {
    // Sheet ialah satu-satunya penulis. Dua penulis kepada data yang sama
    // bermakna suntingan hilang secara senyap pada sync berikutnya.
    $kaedah = get_class_methods(ProjectList::class);

    foreach (['save', 'store', 'update', 'delete', 'destroy', 'create'] as $tulis) {
        expect($kaedah)->not->toContain($tulis);
    }
});

it('shows the view-sheet link to both roles when a sheet is connected', function (): void {
    App\Models\SheetIntegration::create([
        'kind' => 'project',
        'url' => 'https://docs.google.com/spreadsheets/d/abc123/edit',
        'connected' => true,
    ]);

    foreach ([$this->user, $this->admin] as $orang) {
        Livewire::actingAs($orang)
            ->test(ProjectList::class)
            ->assertSee(__('project.view_sheet'));
    }
});

it('explains what to do when no sheet is connected', function (): void {
    Project::query()->delete();

    Livewire::actingAs($this->user)
        ->test(ProjectList::class)
        ->assertSee(__('project.no_sheet'));
});

/*
|--------------------------------------------------------------------------
| Integrasi projek mesti terpisah daripada integrasi Data Kritikal
|--------------------------------------------------------------------------
*/

it('keeps the project sheet separate from the critical-data sheet', function (): void {
    // Kedua-duanya mempunyai service_id NULL. Mencari mengikut service_id
    // sahaja boleh memulangkan baris yang salah, dan sync Data Kritikal
    // akan menulis konfigurasinya ke integrasi projek.
    $kritikal = App\Models\SheetIntegration::firstOrCreate(
        ['kind' => 'critical', 'service_id' => null],
        ['url' => 'https://docs.google.com/spreadsheets/d/kritikal/edit']
    );

    $projek = App\Models\SheetIntegration::firstOrCreate(
        ['kind' => 'project', 'service_id' => null],
        ['url' => 'https://docs.google.com/spreadsheets/d/projek/edit']
    );

    expect($kritikal->id)->not->toBe($projek->id)
        ->and(App\Models\SheetIntegration::critical()->whereNull('service_id')->first()->id)
        ->toBe($kritikal->id)
        ->and(App\Models\SheetIntegration::projects()->first()->id)
        ->toBe($projek->id);
});

it('never offers the project sheet to the critical-data sync', function (): void {
    // Enjin Data Kritikal akan cuba membaca tab Master Project dan
    // melaporkan sheet itu rosak.
    App\Models\SheetIntegration::create([
        'kind' => 'project',
        'service_id' => null,
        'sync_enabled' => true,
        'url' => 'https://docs.google.com/spreadsheets/d/projek/edit',
    ]);

    $untukSync = App\Models\SheetIntegration::critical()
        ->where('sync_enabled', true)
        ->pluck('kind')
        ->unique();

    expect($untukSync)->not->toContain('project');
});

it('allows one global row per kind', function (): void {
    // Kunci unik lama pada service_id sahaja menghalang baris NULL kedua,
    // jadi projek tidak boleh mempunyai konfigurasi globalnya sendiri.
    App\Models\SheetIntegration::create(['kind' => 'critical', 'service_id' => null]);
    App\Models\SheetIntegration::create(['kind' => 'project', 'service_id' => null]);

    expect(App\Models\SheetIntegration::whereNull('service_id')->count())->toBe(2);
});

/*
|--------------------------------------------------------------------------
| Penomboran mesti kelihatan
|--------------------------------------------------------------------------
*/

it('renders pagination with theme colours, not Laravel light-theme defaults', function (): void {
    // Paparan lalai Laravel menggunakan bg-white dan text-gray-500. Pada
    // dashboard gelap ini nombor halaman menjadi putih di atas putih:
    // pautan ada, boleh diklik, dan langsung tidak kelihatan.
    for ($i = 4; $i <= 40; $i++) {
        Project::create([
            'code' => "PRJ-2405{$i}",
            'service_id' => $this->renovation->id,
            'client_name' => "Klien {$i}",
            'contract_amount' => 1000,
            'status' => ProjectStatus::Pending,
        ]);
    }

    $html = Livewire::actingAs($this->user)
        ->test(ProjectList::class)
        ->set('perPage', 10)
        ->html();

    expect($html)->toContain('aria-label="'.__('pagination.nav').'"')
        ->and($html)->toContain('var(--t75)')
        ->and($html)->not->toContain('bg-white');
});

it('marks the current page for screen readers and by colour', function (): void {
    for ($i = 4; $i <= 40; $i++) {
        Project::create([
            'code' => "PRJ-2405{$i}",
            'service_id' => $this->renovation->id,
            'client_name' => "Klien {$i}",
            'contract_amount' => 1000,
            'status' => ProjectStatus::Pending,
        ]);
    }

    $html = Livewire::actingAs($this->user)
        ->test(ProjectList::class)
        ->set('perPage', 10)
        ->html();

    expect($html)->toContain('aria-current="page"');
});

it('keeps pagination links usable without Livewire', function (): void {
    // Paparan ini ialah lalai global, jadi ia mesti betul untuk senarai
    // bernombor yang ditambah kemudian — termasuk yang bukan Livewire.
    $view = file_get_contents(
        base_path('resources/views/vendor/pagination/dbena.blade.php')
    );

    expect($view)->toContain('href=')
        ->and($view)->toContain('wire:click.prevent');
});

/*
|--------------------------------------------------------------------------
| Penapis status mengikut data, bukan enum
|--------------------------------------------------------------------------
*/

it('lists only the statuses that exist in the data', function (): void {
    // Enum mengetahui enam status kerana ia mesti menerima apa sahaja yang
    // mungkin ditaip dalam sheet. Sheet DBENA menggunakan tiga.
    // Menyenaraikan kesemua enam memberi pengguna pilihan yang sentiasa
    // memulangkan senarai kosong — dan senarai kosong kelihatan seperti
    // penapis yang rosak, bukan seperti data yang tiada.
    $statuses = collect(
        Livewire::actingAs($this->user)->test(ProjectList::class)->viewData('statuses')
    )->pluck('status');

    expect($statuses)->toContain(ProjectStatus::InProgress)
        ->and($statuses)->toContain(ProjectStatus::Quotation)
        ->and($statuses)->toContain(ProjectStatus::Closed)
        ->and($statuses)->not->toContain(ProjectStatus::TurnedDown)
        ->and($statuses)->not->toContain(ProjectStatus::Completed);
});

it('counts each status so the number is visible before filtering', function (): void {
    $statuses = collect(
        Livewire::actingAs($this->user)->test(ProjectList::class)->viewData('statuses')
    )->keyBy(fn (array $s) => $s['status']->value);

    expect($statuses[ProjectStatus::InProgress->value]['count'])->toBe(1)
        ->and($statuses[ProjectStatus::Closed->value]['count'])->toBe(1);
});

it('keeps the funnel order rather than database order', function (): void {
    // Corong jualan mempunyai susunan semula jadi dan senarai turun
    // sepatutnya mengikutnya.
    $urutan = collect(
        Livewire::actingAs($this->user)->test(ProjectList::class)->viewData('statuses')
    )->pluck('status.value')->all();

    expect(array_search('quotation', $urutan, true))
        ->toBeLessThan(array_search('in_progress', $urutan, true))
        ->and(array_search('in_progress', $urutan, true))
        ->toBeLessThan(array_search('closed', $urutan, true));
});

it('keeps a chosen status in the list even after its last project goes', function (): void {
    // Kalau tidak, penapis aktif hilang daripada senarai turun dan
    // pengguna tidak boleh melihat apa yang sedang ditapis.
    Project::where('status', ProjectStatus::Quotation)->delete();

    $statuses = collect(
        Livewire::actingAs($this->user)
            ->test(ProjectList::class)
            ->set('status', ProjectStatus::Quotation->value)
            ->viewData('statuses')
    )->pluck('status');

    expect($statuses)->toContain(ProjectStatus::Quotation);
});

it('shows a new status as soon as the sheet introduces it', function (): void {
    Project::create([
        'code' => 'PRJ-TD-1',
        'service_id' => $this->renovation->id,
        'client_name' => 'Klien Ditolak',
        'contract_amount' => 5000,
        'status' => ProjectStatus::TurnedDown,
    ]);

    $statuses = collect(
        Livewire::actingAs($this->user)->test(ProjectList::class)->viewData('statuses')
    )->pluck('status');

    expect($statuses)->toContain(ProjectStatus::TurnedDown);
});
