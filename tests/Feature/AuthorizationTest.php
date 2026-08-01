<?php

declare(strict_types=1);

use App\Enums\OwnerStatus;
use App\Enums\UserRole;
use App\Livewire\Admin\ConfigPanel;
use App\Livewire\Dashboard\ServiceDetail;
use App\Models\CriticalMetric;
use App\Models\CriticalMetricMonth;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed();

    $this->admin = User::where('role', UserRole::Admin)->firstOrFail();
    $this->user = User::where('role', UserRole::User)->firstOrFail();
});

/*
|--------------------------------------------------------------------------
| Middleware role — /admin
|--------------------------------------------------------------------------
*/

it('blocks a plain user from the admin panel even by typing the URL', function (): void {
    // PEMBETULAN isu #5 — dalam prototaip ini hanyalah <a href> tanpa semakan.
    $this->actingAs($this->user)->get('/admin')->assertForbidden();
});

it('lets an admin into the admin panel', function (): void {
    $this->actingAs($this->admin)->get('/admin')->assertOk();
});

it('sends guests to the login screen', function (): void {
    $this->get('/dashboard')->assertRedirect(route('login'));
    $this->get('/admin')->assertRedirect(route('login'));
});

it('blocks a deactivated admin from the admin panel', function (): void {
    $this->admin->update(['is_active' => false]);

    $this->actingAs($this->admin)->get('/admin')->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Guard SASARAN — tidak boleh dipintas melalui network request
|--------------------------------------------------------------------------
*/

it('refuses to let a plain user change a target, even calling the method directly', function (): void {
    $metric = CriticalMetric::whereHas('service', fn ($q) => $q->where('key', 'renovation'))
        ->where('metric_key', 'revenue_sales')
        ->firstOrFail();

    Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->call('updateTarget', $metric->id, '999999')
        ->assertForbidden();

    expect((float) $metric->targetForYear((int) now()->year)->monthly_target)->toBe(500000.0);
});

it('lets an admin change a target and records it in the audit log', function (): void {
    $metric = CriticalMetric::whereHas('service', fn ($q) => $q->where('key', 'renovation'))
        ->where('metric_key', 'revenue_sales')
        ->firstOrFail();

    Livewire::actingAs($this->admin)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->call('updateTarget', $metric->id, '750000');

    expect((float) $metric->fresh()->targetForYear((int) now()->year)->monthly_target)->toBe(750000.0);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'target.updated',
        'user_id' => $this->admin->id,
    ]);
});

it('lets an admin record weekly values', function (): void {
    $metric = CriticalMetric::whereHas('service', fn ($q) => $q->where('key', 'kabinet'))
        ->where('metric_key', 'no_of_lead')
        ->firstOrFail();

    Livewire::actingAs($this->admin)
        ->test(ServiceDetail::class, ['key' => 'kabinet'])
        ->set("weekValues.{$metric->id}.1", '120')
        ->call('saveWeekValue', $metric->id, 1);

    $this->assertDatabaseHas('critical_weekly_entries', [
        'critical_metric_id' => $metric->id,
        'week_number' => 1,
        'value' => 120.00,
        'updated_by' => $this->admin->id,
    ]);
});

it('does not let a plain user record weekly values', function (): void {
    // Nilai mingguan datang dari Google Sheet dan ditulis semula pada
    // setiap sync. Suntingan oleh pengguna akan hilang tanpa amaran.
    $metric = CriticalMetric::whereHas('service', fn ($q) => $q->where('key', 'kabinet'))
        ->where('metric_key', 'no_of_lead')
        ->firstOrFail();

    Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'kabinet'])
        ->set("weekValues.{$metric->id}.1", '999')
        ->call('saveWeekValue', $metric->id, 1);

    $this->assertDatabaseMissing('critical_weekly_entries', [
        'critical_metric_id' => $metric->id,
        'week_number' => 1,
        'value' => 999.00,
    ]);
})->throws(AuthorizationException::class);

it('hides the weekly inputs from a plain user but still shows the numbers', function (): void {
    Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'kabinet'])
        ->assertDontSee('wire:model.blur="weekValues', escape: false)
        ->assertOk();
});

it('refuses to write a metric belonging to a different service', function (): void {
    $foreign = CriticalMetric::whereHas('service', fn ($q) => $q->where('key', 'mihrab'))
        ->firstOrFail();

    // Admin, supaya semakan yang diuji ialah pengasingan servis dan bukan
    // peranan. Pengguna biasa akan ditolak lebih awal atas sebab lain.
    Livewire::actingAs($this->admin)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->call('saveWeekValue', $foreign->id, 1)
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Guard buang PIC (isu #10)
|--------------------------------------------------------------------------
*/

it('refuses to delete a core PIC', function (): void {
    $core = Owner::where('name', 'ZIKRI')->firstOrFail();

    Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->call('removeOwner', $core->id);

    $this->assertDatabaseHas('owners', ['id' => $core->id]);
});

it('refuses to delete the INFO system label', function (): void {
    $system = Owner::where('name', 'INFO')->firstOrFail();

    Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->call('removeOwner', $system->id);

    $this->assertDatabaseHas('owners', ['id' => $system->id]);
});

it('refuses to delete a PIC that still holds active metric data', function (): void {
    $owner = Owner::create([
        'name' => 'FARID',
        'color_token' => Owner::nextColor(),
        'status' => OwnerStatus::Active,
    ]);

    CriticalMetricMonth::create([
        'critical_metric_id' => CriticalMetric::first()->id,
        'year' => (int) now()->year,
        'month' => (int) now()->month,
        'owner_id' => $owner->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->call('removeOwner', $owner->id);

    $this->assertDatabaseHas('owners', ['id' => $owner->id]);
});

it('deletes a PIC that has no data attached', function (): void {
    $owner = Owner::create([
        'name' => 'BARU',
        'color_token' => Owner::nextColor(),
        'status' => OwnerStatus::Active,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->call('removeOwner', $owner->id);

    $this->assertDatabaseMissing('owners', ['id' => $owner->id]);
});

/*
|--------------------------------------------------------------------------
| Lajur PEMILIK — paparan sahaja di Dashboard Pengguna
|--------------------------------------------------------------------------
*/

it('does not let a plain user reassign the PIC of a row', function (): void {
    $metric = CriticalMetric::whereRelation('service', 'key', 'renovation')->firstOrFail();
    $owner = Owner::where('status', OwnerStatus::Active)->firstOrFail();

    Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->set("rowOwners.{$metric->id}", $owner->id)
        ->call('saveRowOwner', $metric->id);

    $this->assertDatabaseMissing('critical_metric_months', [
        'critical_metric_id' => $metric->id,
        'owner_id' => $owner->id,
    ]);
})->throws(AuthorizationException::class);

it('still lets an admin reassign the PIC of a row', function (): void {
    $metric = CriticalMetric::whereRelation('service', 'key', 'renovation')->firstOrFail();
    $owner = Owner::where('status', OwnerStatus::Active)->firstOrFail();

    Livewire::actingAs($this->admin)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->set("rowOwners.{$metric->id}", $owner->id)
        ->call('saveRowOwner', $metric->id);

    $this->assertDatabaseHas('critical_metric_months', [
        'critical_metric_id' => $metric->id,
        'owner_id' => $owner->id,
    ]);
});

it('lets a plain user still write an action plan', function (): void {
    // Hanya lajur PEMILIK yang dikunci. Pelan tindakan kekal boleh diisi,
    // kalau tidak pengguna tidak dapat merekod apa yang mereka akan buat.
    $metric = CriticalMetric::whereRelation('service', 'key', 'renovation')->firstOrFail();

    Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->set("rowPlans.{$metric->id}", 'Tambah 20 lead minggu depan')
        ->call('saveRowPlan', $metric->id);

    $this->assertDatabaseHas('critical_metric_months', [
        'critical_metric_id' => $metric->id,
        'action_plan' => 'Tambah 20 lead minggu depan',
    ]);
});

/*
|--------------------------------------------------------------------------
| Alur kelulusan PIC (isu #11)
|--------------------------------------------------------------------------
*/

it('does not let a plain user add a PIC from the dashboard', function (): void {
    // "Tambah PIC" dialih keluar daripada Dashboard Pengguna. Butang yang
    // disorok tidak memadai — panggilan terus mesti ditolak juga.
    Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->set('newOwnerName', 'farid')
        ->call('addOwner');

    $this->assertDatabaseMissing('owners', ['name' => 'FARID']);
})->throws(AuthorizationException::class);

it('hides the Tambah PIC and Raw Data buttons from a plain user', function (): void {
    Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->assertDontSee(__('service.add_owner'))
        ->assertDontSee(__('service.view_raw_data'));
});

it('still shows both buttons to an admin', function (): void {
    Livewire::actingAs($this->admin)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->assertSee(__('service.add_owner'))
        ->assertSee(__('service.view_raw_data'));
});

it('activates a PIC added by an admin straight away', function (): void {
    Livewire::actingAs($this->admin)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->set('newOwnerName', 'suhaimi')
        ->call('addOwner');

    $this->assertDatabaseHas('owners', [
        'name' => 'SUHAIMI',
        'status' => OwnerStatus::Active->value,
    ]);
});

it('lets an admin approve a pending PIC', function (): void {
    $owner = Owner::create([
        'name' => 'PENDING',
        'color_token' => Owner::nextColor(),
        'status' => OwnerStatus::PendingApproval,
        'created_by' => $this->user->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->call('approveOwner', $owner->id);

    expect($owner->fresh()->status)->toBe(OwnerStatus::Active);

    $this->assertDatabaseHas('audit_logs', ['action' => 'owner.approved']);
});

it('does not let a plain user approve a PIC', function (): void {
    $owner = Owner::create([
        'name' => 'PENDING2',
        'color_token' => Owner::nextColor(),
        'status' => OwnerStatus::PendingApproval,
    ]);

    Livewire::actingAs($this->user)
        ->test(ConfigPanel::class)
        ->call('approveOwner', $owner->id)
        ->assertForbidden();
})->throws(AuthorizationException::class);

/*
|--------------------------------------------------------------------------
| Integrasi Google Sheet — admin sahaja
|--------------------------------------------------------------------------
*/

it('does not let a plain user connect a Google Sheet', function (): void {
    Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->set('sheetUrl', 'https://docs.google.com/spreadsheets/d/abc')
        ->call('connectSheet')
        ->assertForbidden();
});

it('lets an admin connect a Google Sheet', function (): void {
    Livewire::actingAs($this->admin)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->set('sheetUrl', 'https://docs.google.com/spreadsheets/d/abc')
        ->call('connectSheet');

    $this->assertDatabaseHas('sheet_integrations', ['connected' => true]);
});

/*
|--------------------------------------------------------------------------
| Butang Lihat Google Sheet pada Dashboard Pengguna
|--------------------------------------------------------------------------
*/

it('gives a plain user a direct link to the sheet, not a settings modal', function (): void {
    // Sebelum ini satu-satunya jalan ialah modal tetapan: medan URL yang
    // dilumpuhkan, status sync, dan pautan sebenar tersembunyi di kaki
    // modal. Tiga klik melalui skrin tetapan untuk melihat sheet sendiri.
    App\Models\SheetIntegration::global()->update([
        'url' => 'https://docs.google.com/spreadsheets/d/kritikal123456789012/edit',
    ]);

    Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->assertSee(__('project.view_sheet'))
        ->assertSee('kritikal123456789012', false);
});

it('keeps the sheet settings modal for admins only', function (): void {
    // Sambung, sync dan putus sambungan hidup dalam modal itu. Menunjukkan
    // pintu masuknya kepada pengguna yang tidak boleh melakukan apa-apa di
    // dalamnya hanyalah jalan mati.
    App\Models\SheetIntegration::global()->update([
        'url' => 'https://docs.google.com/spreadsheets/d/kritikal123456789012/edit',
    ]);

    Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->assertDontSee(__('service.google_sheet'));

    Livewire::actingAs($this->admin)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->assertSee(__('project.view_sheet'));
});

it('shows no sheet button at all when no sheet is connected', function (): void {
    // Butang mati yang membuka panel kosong kelihatan seperti ciri yang
    // rosak, bukan seperti sheet yang belum disambung.
    App\Models\SheetIntegration::global()->update(['url' => null]);

    Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->assertDontSee(__('project.view_sheet'));
});
