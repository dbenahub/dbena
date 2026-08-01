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

it('lets an admin set a specific password for a user', function (): void {
    $target = User::where('role', UserRole::User)->firstOrFail();
    $before = $target->password;

    Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->call('openPasswordModal', $target->id)
        ->set('newUserPassword', 'KataLaluanBaru123')
        ->set('newUserPasswordConfirmation', 'KataLaluanBaru123')
        ->call('savePassword')
        ->assertHasNoErrors();

    $fresh = $target->fresh();

    expect($fresh->password)->not->toBe($before)
        // Disimpan di-hash, tidak pernah sebagai teks biasa
        ->and($fresh->password)->not->toBe('KataLaluanBaru123')
        ->and($fresh->password)->toStartWith('$2y$')
        ->and(Illuminate\Support\Facades\Hash::check('KataLaluanBaru123', $fresh->password))->toBeTrue();

    $this->assertDatabaseHas('audit_logs', ['action' => 'user.password_reset']);
});

it('rejects a password that is too weak', function (): void {
    $target = User::where('role', UserRole::User)->firstOrFail();

    Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->call('openPasswordModal', $target->id)
        ->set('newUserPassword', 'abc')
        ->set('newUserPasswordConfirmation', 'abc')
        ->call('savePassword')
        ->assertHasErrors('newUserPassword');
});

it('rejects mismatched confirmation', function (): void {
    $target = User::where('role', UserRole::User)->firstOrFail();

    Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->call('openPasswordModal', $target->id)
        ->set('newUserPassword', 'KataLaluanBaru123')
        ->set('newUserPasswordConfirmation', 'LainSamaSekali456')
        ->call('savePassword')
        ->assertHasErrors('newUserPassword');
});

it('generates a password that satisfies its own rules', function (): void {
    $target = User::where('role', UserRole::User)->firstOrFail();

    $component = Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->call('openPasswordModal', $target->id)
        ->call('generatePassword');

    $generated = $component->get('newUserPassword');

    expect(strlen($generated))->toBeGreaterThanOrEqual(8)
        ->and($generated)->toMatch('/[A-Za-z]/')
        ->and($generated)->toMatch('/\d/')
        ->and($component->get('newUserPasswordConfirmation'))->toBe($generated);

    $component->call('savePassword')->assertHasNoErrors();
});

it('cancels outstanding OTPs when a password is reset', function (): void {
    $target = User::where('role', UserRole::User)->firstOrFail();

    App\Models\Otp::create([
        'user_id' => $target->id,
        'code_hash' => bcrypt('123456'),
        'type' => App\Enums\OtpType::Login,
        'expires_at' => now()->addMinutes(5),
    ]);

    Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->call('openPasswordModal', $target->id)
        ->set('newUserPassword', 'KataLaluanBaru123')
        ->set('newUserPasswordConfirmation', 'KataLaluanBaru123')
        ->call('savePassword');

    expect(App\Models\Otp::where('user_id', $target->id)->whereNull('consumed_at')->count())->toBe(0);
});

it('does not let a plain user reset anyone password', function (): void {
    $user = User::where('role', UserRole::User)->firstOrFail();

    Livewire::actingAs($user)
        ->test(ConfigPanel::class)
        ->call('openPasswordModal', $this->admin->id)
        ->assertForbidden();
})->throws(Illuminate\Auth\Access\AuthorizationException::class);

it('refuses to let an admin deactivate their own account', function (): void {
    Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->call('toggleUserActive', $this->admin->id);

    expect($this->admin->fresh()->is_active)->toBeTrue();
});
