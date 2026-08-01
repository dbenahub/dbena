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
