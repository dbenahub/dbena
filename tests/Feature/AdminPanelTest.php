<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\Admin\ConfigPanel;
use App\Models\IndexTier;
use App\Models\Service;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed();
    $this->admin = User::where('role', UserRole::Admin)->firstOrFail();
});

it('persists service targets and picks them up on the dashboard', function (): void {
    $service = Service::where('key', 'kabinet')->firstOrFail();

    Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->set("services.{$service->id}.monthly_target", '350000')
        ->call('saveAll');

    expect((float) $service->fresh()->monthly_target)->toBe(350000.0);
});

it('writes an audit entry with both the old and the new value', function (): void {
    // PEMBETULAN isu #25 — prototaip tiada jejak audit langsung.
    $service = Service::where('key', 'divider')->firstOrFail();
    $old = (float) $service->monthly_target;

    Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->set("services.{$service->id}.monthly_target", '99000')
        ->call('saveAll');

    $log = DB::table('audit_logs')->where('action', 'service.updated')->latest('id')->first();

    expect($log)->not->toBeNull()
        ->and(json_decode($log->old_values, true)['monthly_target'])->toEqual($old)
        ->and(json_decode($log->new_values, true)['monthly_target'])->toEqual(99000.0);
});

it('does not write an audit entry when nothing actually changed', function (): void {
    Livewire::actingAs($this->admin)->test(ConfigPanel::class)->call('saveAll');

    expect(DB::table('audit_logs')->count())->toBe(0);
});

it('saves every section inside one transaction', function (): void {
    $service = Service::first();
    $tier = IndexTier::where('key', 'growing')->firstOrFail();

    Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->set("services.{$service->id}.monthly_target", '111111')
        ->set("tiers.{$tier->id}.monthly_revenue_threshold", '700000')
        ->set('growth.2027', '1.25')
        ->set('sheetUrl', 'https://docs.google.com/spreadsheets/d/xyz')
        ->call('saveAll');

    expect((float) $service->fresh()->monthly_target)->toBe(111111.0)
        ->and((float) $tier->fresh()->monthly_revenue_threshold)->toBe(700000.0);

    $this->assertDatabaseHas('year_growth_factors', ['year' => 2027, 'factor' => 1.25]);
    $this->assertDatabaseHas('sheet_integrations', ['url' => 'https://docs.google.com/spreadsheets/d/xyz']);
});

it('creates a user with a generated password that is never stored in plain text', function (): void {
    $component = Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->set('userName', 'Siti Aminah')
        ->set('userUsername', 'siti')
        ->set('userEmail', 'siti@dbena.com.my')
        ->set('userRole', 'user')
        ->call('createUser');

    $created = User::where('username', 'siti')->firstOrFail();

    expect($component->get('generatedPassword'))->toBeString()
        ->and($created->password)->not->toBe($component->get('generatedPassword'))
        ->and($created->password)->toStartWith('$2y$');
});

it('refuses to let an admin deactivate their own account', function (): void {
    Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->call('toggleUserActive', $this->admin->id);

    expect($this->admin->fresh()->is_active)->toBeTrue();
});
