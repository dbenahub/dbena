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

it('lets both roles record weekly values', function (): void {
    $metric = CriticalMetric::whereHas('service', fn ($q) => $q->where('key', 'kabinet'))
        ->where('metric_key', 'no_of_lead')
        ->firstOrFail();

    Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'kabinet'])
        ->set("weekValues.{$metric->id}.1", '120')
        ->call('saveWeekValue', $metric->id, 1);

    $this->assertDatabaseHas('critical_weekly_entries', [
        'critical_metric_id' => $metric->id,
        'week_number' => 1,
        'value' => 120.00,
        'updated_by' => $this->user->id,
    ]);
});

it('refuses to write a metric belonging to a different service', function (): void {
    $foreign = CriticalMetric::whereHas('service', fn ($q) => $q->where('key', 'mihrab'))
        ->firstOrFail();

    Livewire::actingAs($this->user)
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
| Alur kelulusan PIC (isu #11)
|--------------------------------------------------------------------------
*/

it('parks a PIC proposed by a plain user as pending approval', function (): void {
    Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->set('newOwnerName', 'farid')
        ->call('addOwner');

    $this->assertDatabaseHas('owners', [
        'name' => 'FARID',
        'status' => OwnerStatus::PendingApproval->value,
        'created_by' => $this->user->id,
    ]);
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
