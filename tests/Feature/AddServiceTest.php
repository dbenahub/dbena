<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\Admin\ConfigPanel;
use App\Livewire\Dashboard\Overview;
use App\Livewire\Dashboard\ServiceDetail;
use App\Models\CriticalWeeklyEntry;
use App\Models\Service;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed();

    $this->admin = User::where('role', UserRole::Admin)->firstOrFail();
    $this->user = User::where('role', UserRole::User)->firstOrFail();
    $this->renovation = Service::where('key', 'renovation')->firstOrFail();
});

/** Tambah satu servis melalui Admin Panel. */
function tambahServis(User $admin, Service $templat, string $nama = 'Kitchen Top'): Service
{
    Livewire::actingAs($admin)
        ->test(ConfigPanel::class)
        ->set('newServiceNameMs', $nama)
        ->set('newServiceNameEn', 'Kitchen Top')
        ->set('newServiceTarget', '150000')
        ->set('copyFromServiceId', $templat->id)
        ->call('createService');

    return Service::where('name_ms', $nama)->firstOrFail();
}

/*
|--------------------------------------------------------------------------
| Penciptaan
|--------------------------------------------------------------------------
*/

it('creates a service from the admin panel', function (): void {
    $servis = tambahServis($this->admin, $this->renovation);

    expect($servis->key)->toBe('kitchen-top')
        ->and($servis->name_en)->toBe('Kitchen Top')
        ->and((float) $servis->monthly_target)->toBe(150000.0);
});

it('copies the metric set so the new service is usable immediately', function (): void {
    // Servis tanpa metrik ialah halaman kosong — tiada corong, tiada
    // diagnosis, tiada baris dalam laporan. Ia kelihatan seperti sistem
    // rosak dan bukan servis yang baru dicipta.
    $servis = tambahServis($this->admin, $this->renovation);

    expect($servis->criticalMetrics()->count())
        ->toBe($this->renovation->criticalMetrics()->count())
        ->and($servis->criticalMetrics()->pluck('metric_key')->sort()->values()->all())
        ->toBe($this->renovation->criticalMetrics()->pluck('metric_key')->sort()->values()->all());
});

it('copies metric targets, not just metric names', function (): void {
    $servis = tambahServis($this->admin, $this->renovation);

    $sumber = $this->renovation->metricByKey('no_of_lead');
    $salinan = $servis->metricByKey('no_of_lead');

    expect($salinan->targets()->count())->toBe($sumber->targets()->count());
});

it('never copies weekly values from the template', function (): void {
    // Sasaran ialah struktur; nilai mingguan ialah sejarah. Menyalin
    // sejarah servis lain akan menjadikan servis baharu kelihatan seolah-olah
    // ia sudah berniaga berbulan-bulan.
    $servis = tambahServis($this->admin, $this->renovation);

    $adaData = CriticalWeeklyEntry::whereIn(
        'critical_metric_id',
        $servis->criticalMetrics()->select('id')
    )->exists();

    expect($adaData)->toBeFalse();
});

it('gives each service its own chart colour', function (): void {
    tambahServis($this->admin, $this->renovation, 'Kitchen Top');
    tambahServis($this->admin, $this->renovation, 'Wardrobe');

    $warna = Service::pluck('chart_color');

    expect($warna->unique()->count())->toBe($warna->count());
});

it('places the new service last in the order', function (): void {
    $sebelum = (int) Service::max('sort_order');

    expect(tambahServis($this->admin, $this->renovation)->sort_order)
        ->toBeGreaterThan($sebelum);
});

/*
|--------------------------------------------------------------------------
| Berfungsi sepenuhnya selepas ditambah
|--------------------------------------------------------------------------
*/

it('shows the new service on the dashboard', function (): void {
    $servis = tambahServis($this->admin, $this->renovation);

    Livewire::actingAs($this->user)
        ->test(Overview::class)
        ->assertSee($servis->name);
});

it('opens the new service detail page without error', function (): void {
    $servis = tambahServis($this->admin, $this->renovation);

    Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => $servis->key])
        ->assertOk();
});

it('accepts monthly targets for the new service', function (): void {
    $servis = tambahServis($this->admin, $this->renovation);

    Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->set("monthlyTargets.{$servis->id}.1", '99000')
        ->call('saveAll');

    expect($servis->fresh()->targetForMonth((int) now()->year, 1))->toBe(99000.0);
});

/*
|--------------------------------------------------------------------------
| Pengesahan
|--------------------------------------------------------------------------
*/

it('refuses a blank service name', function (): void {
    $sebelum = Service::count();

    Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->set('newServiceNameMs', '   ')
        ->set('copyFromServiceId', $this->renovation->id)
        ->call('createService');

    expect(Service::count())->toBe($sebelum);
});

it('refuses a duplicate service name', function (): void {
    $sebelum = Service::count();

    Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->set('newServiceNameMs', 'Renovation')
        ->set('copyFromServiceId', $this->renovation->id)
        ->call('createService');

    expect(Service::count())->toBe($sebelum);
});

it('falls back to the Malay name when the English one is blank', function (): void {
    Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->set('newServiceNameMs', 'Meja Dapur')
        ->set('newServiceNameEn', '')
        ->set('copyFromServiceId', $this->renovation->id)
        ->call('createService');

    expect(Service::where('name_ms', 'Meja Dapur')->firstOrFail()->name_en)
        ->toBe('Meja Dapur');
});

it('does not let a plain user create a service', function (): void {
    $sebelum = Service::count();

    Livewire::actingAs($this->user)
        ->test(ConfigPanel::class)
        ->set('newServiceNameMs', 'Selundup')
        ->call('createService');

    expect(Service::count())->toBe($sebelum);
})->throws(Illuminate\Auth\Access\AuthorizationException::class);

/*
|--------------------------------------------------------------------------
| Pembuangan
|--------------------------------------------------------------------------
*/

it('removes a service that carries no data', function (): void {
    $servis = tambahServis($this->admin, $this->renovation);

    Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->call('removeService', $servis->id);

    expect(Service::find($servis->id))->toBeNull();
});

it('refuses to remove a service that already has weekly data', function (): void {
    // Memadamnya memusnahkan sejarah yang laporan bulan lalu bergantung
    // padanya, dan tiada cara untuk memulihkannya dari dalam sistem.
    Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->call('removeService', $this->renovation->id);

    expect(Service::find($this->renovation->id))->not->toBeNull();
});

it('does not let a plain user remove a service', function (): void {
    $servis = tambahServis($this->admin, $this->renovation);

    Livewire::actingAs($this->user)
        ->test(ConfigPanel::class)
        ->call('removeService', $servis->id);

    expect(Service::find($servis->id))->not->toBeNull();
})->throws(Illuminate\Auth\Access\AuthorizationException::class);

it('records both actions in the audit log', function (): void {
    $servis = tambahServis($this->admin, $this->renovation);

    Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->call('removeService', $servis->id);

    $this->assertDatabaseHas('audit_logs', ['action' => 'service.created']);
    $this->assertDatabaseHas('audit_logs', ['action' => 'service.removed']);
});
