<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\Admin\ConfigPanel;
use App\Models\Service;
use App\Models\ServiceMonthlyTarget;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed();

    $this->admin = User::where('role', UserRole::Admin)->firstOrFail();
    $this->service = Service::where('key', 'renovation')->firstOrFail(); // asas RM500,000
});

/*
|--------------------------------------------------------------------------
| Fallback ke sasaran asas
|--------------------------------------------------------------------------
*/

it('falls back to the base target when no month override exists', function (): void {
    expect($this->service->targetForMonth(2026, 3))->toBe(500000.0);
});

it('sums twelve months for the yearly target', function (): void {
    // PEPIJAT YANG DIBETULKAN: dahulu ia mendarab dengan bulan SEMASA,
    // jadi pada bulan Ogos ia menunjukkan RM4,000,000 dan bukan RM6,000,000.
    expect($this->service->targetForYear(2026))->toBe(6000000.0);
});

it('does not use the current month as a multiplier', function (): void {
    // Sasaran tahunan mesti sama tanpa mengira bila ia ditanya.
    $august = $this->service->targetForYear(2026);

    Carbon\CarbonImmutable::setTestNow('2026-03-15');
    $march = $this->service->fresh()->targetForYear(2026);

    Carbon\CarbonImmutable::setTestNow();

    expect($march)->toBe($august);
});

/*
|--------------------------------------------------------------------------
| Sasaran bermusim
|--------------------------------------------------------------------------
*/

it('honours a per-month override', function (): void {
    ServiceMonthlyTarget::create([
        'service_id' => $this->service->id,
        'year' => 2026,
        'month' => 7,
        'target' => 900000,
    ]);

    $fresh = $this->service->fresh();

    expect($fresh->targetForMonth(2026, 7))->toBe(900000.0)
        // Bulan lain tidak terjejas
        ->and($fresh->targetForMonth(2026, 8))->toBe(500000.0);
});

it('adds seasonal months into the yearly total correctly', function (): void {
    // Julai dan Disember lebih tinggi; sepuluh bulan lain kekal RM500,000
    foreach ([7 => 900000, 12 => 800000] as $month => $target) {
        ServiceMonthlyTarget::create([
            'service_id' => $this->service->id,
            'year' => 2026,
            'month' => $month,
            'target' => $target,
        ]);
    }

    // 10 × 500,000 + 900,000 + 800,000
    expect($this->service->fresh()->targetForYear(2026))->toBe(6700000.0);
});

it('keeps years independent of each other', function (): void {
    ServiceMonthlyTarget::create([
        'service_id' => $this->service->id,
        'year' => 2026,
        'month' => 1,
        'target' => 999999,
    ]);

    $fresh = $this->service->fresh();

    expect($fresh->targetForMonth(2026, 1))->toBe(999999.0)
        ->and($fresh->targetForMonth(2027, 1))->toBe(500000.0);
});

/*
|--------------------------------------------------------------------------
| Sasaran terkumpul
|--------------------------------------------------------------------------
*/

it('accumulates only the months up to the one asked for', function (): void {
    expect($this->service->cumulativeTargetTo(2026, 1))->toBe(500000.0)
        ->and($this->service->cumulativeTargetTo(2026, 3))->toBe(1500000.0)
        ->and($this->service->cumulativeTargetTo(2026, 12))->toBe(6000000.0);
});

it('reflects seasonal months in the cumulative figure', function (): void {
    ServiceMonthlyTarget::create([
        'service_id' => $this->service->id,
        'year' => 2026,
        'month' => 2,
        'target' => 700000,
    ]);

    // Jan 500k + Feb 700k
    expect($this->service->fresh()->cumulativeTargetTo(2026, 2))->toBe(1200000.0);
});

it('clamps an out-of-range month', function (): void {
    expect($this->service->cumulativeTargetTo(2026, 99))->toBe(6000000.0)
        ->and($this->service->cumulativeTargetTo(2026, 0))->toBe(500000.0);
});

/*
|--------------------------------------------------------------------------
| Admin Panel
|--------------------------------------------------------------------------
*/

it('loads every month into the admin form', function (): void {
    $component = Livewire::actingAs($this->admin)->test(ConfigPanel::class);

    $targets = $component->get('monthlyTargets')[$this->service->id];

    expect($targets)->toHaveCount(12)
        ->and((float) $targets[1])->toBe(500000.0);
});

it('saves per-month targets and records them in the audit log', function (): void {
    Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->set('targetYear', 2026)
        ->set("monthlyTargets.{$this->service->id}.7", '900000')
        ->call('saveAll');

    $this->assertDatabaseHas('service_monthly_targets', [
        'service_id' => $this->service->id,
        'year' => 2026,
        'month' => 7,
        'target' => 900000.00,
    ]);

    $this->assertDatabaseHas('audit_logs', ['action' => 'service.monthly_target_updated']);
});

it('copies January across the remaining eleven months', function (): void {
    $component = Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->set("monthlyTargets.{$this->service->id}.1", '750000')
        ->call('fillFromJanuary', $this->service->id);

    $targets = $component->get('monthlyTargets')[$this->service->id];

    foreach (range(1, 12) as $m) {
        expect($targets[$m])->toBe('750000');
    }
});

it('switches the whole grid when the target year changes', function (): void {
    ServiceMonthlyTarget::create([
        'service_id' => $this->service->id,
        'year' => 2027,
        'month' => 1,
        'target' => 123456,
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(ConfigPanel::class)
        ->set('targetYear', 2027);

    expect((float) $component->get('monthlyTargets')[$this->service->id][1])->toBe(123456.0);
});

/*
|--------------------------------------------------------------------------
| Kesan ke atas dashboard
|--------------------------------------------------------------------------
*/

it('shows the yearly target as the sum of all twelve months', function (): void {
    $total = Service::all()->sum(fn (Service $s) => $s->targetForYear(2026));

    // 500k + 200k + 500k + 40k + 80k = 1,320,000 sebulan → 15,840,000 setahun
    expect($total)->toBe(15840000.0);
});

it('renders the dashboard without error in yearly mode', function (): void {
    $user = User::where('role', UserRole::User)->firstOrFail();

    Livewire::actingAs($user)
        ->test(App\Livewire\Dashboard\Overview::class)
        ->set('viewMode', 'yearly')
        ->assertOk();
});

/*
|--------------------------------------------------------------------------
| Sasaran tahunan mesti sepadan dengan Admin Panel
|--------------------------------------------------------------------------
*/

it('shows the same yearly target the admin configured, not a month multiple', function (): void {
    // Pepijat asal: mod tahunan mendarab dengan bulan semasa, jadi pada
    // bulan Ogos ia menunjukkan RM4,000,000 sedangkan Admin Panel kata
    // RM6,000,000. Dua nombor untuk perkara yang sama.
    $user = User::where('role', UserRole::User)->firstOrFail();

    $component = Livewire::actingAs($user)
        ->test(App\Livewire\Dashboard\Overview::class)
        ->set('viewMode', 'yearly')
        ->set('year', 2026)
        ->set('month', 8);

    $rows = collect($component->viewData('serviceRows'));
    $renovation = $rows->firstWhere('key', 'renovation');

    // Sama seperti yang dipapar Admin Panel
    expect($renovation['targetLabel'])->toBe('RM6,000,000');
});

it('keeps the yearly target steady no matter which month is selected', function (): void {
    $user = User::where('role', UserRole::User)->firstOrFail();

    $targetIn = function (int $month) use ($user): string {
        $component = Livewire::actingAs($user)
            ->test(App\Livewire\Dashboard\Overview::class)
            ->set('viewMode', 'yearly')
            ->set('year', 2026)
            ->set('month', $month);

        return collect($component->viewData('serviceRows'))->firstWhere('key', 'renovation')['targetLabel'];
    };

    expect($targetIn(3))->toBe($targetIn(8))
        ->and($targetIn(8))->toBe($targetIn(12));
});

it('reports pace separately from the yearly target', function (): void {
    $user = User::where('role', UserRole::User)->firstOrFail();

    $component = Livewire::actingAs($user)
        ->test(App\Livewire\Dashboard\Overview::class)
        ->set('viewMode', 'yearly')
        ->set('year', 2026)
        ->set('month', 8);

    $renovation = collect($component->viewData('serviceRows'))->firstWhere('key', 'renovation');

    // Rentak setakat Ogos = 8 x RM500,000
    expect($renovation['paceTargetLabel'])->toBe('RM4,000,000')
        // ...dan berbeza daripada sasaran setahun
        ->and($renovation['targetLabel'])->toBe('RM6,000,000');
});

it('judges status against pace, not the full-year target', function (): void {
    // Servis yang mencapai tepat sasaran Jan-Ogos adalah SIHAT, walaupun
    // ia baru 67% daripada sasaran setahun. Menilainya terhadap setahun
    // penuh akan menandakan setiap servis gagal sehingga Disember.
    $metrics = app(App\Services\DashboardMetricsService::class);

    expect($metrics->calculateServiceStatus(100.0))
        ->toBe(App\Enums\ServiceStatus::Memuaskan);
});
