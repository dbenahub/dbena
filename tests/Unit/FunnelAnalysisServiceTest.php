<?php

declare(strict_types=1);

use App\Enums\MetricStatus;
use App\Enums\MetricValueType;
use App\Services\DashboardMetricsService;
use App\Services\FunnelAnalysisService;

beforeEach(function (): void {
    $this->funnel = new FunnelAnalysisService(new DashboardMetricsService);
});

/** Bina satu baris metrik untuk ujian. */
function row(
    string $key,
    ?float $actual,
    ?float $target,
    MetricStatus $status = MetricStatus::Red,
    ?string $plan = null,
    MetricValueType $type = MetricValueType::Number,
): array {
    return [
        'metricKey' => $key,
        'label' => ucwords(str_replace('_', ' ', $key)),
        'actual' => $actual,
        'actualLabel' => $actual === null ? '—' : $type->format($actual),
        'target' => $target,
        'targetLabel' => $target === null ? '—' : $type->format($target),
        'valueType' => $type,
        'pct' => ($actual !== null && $target !== null && $target > 0) ? $actual / $target * 100 : null,
        'status' => $status,
        'actionPlan' => $plan,
    ];
}

/*
|--------------------------------------------------------------------------
| Kesan punca di HULU corong
|--------------------------------------------------------------------------
*/

it('blames the lead shortfall when quotations miss target', function (): void {
    $rows = collect([
        row('no_of_lead', 100, 600),                      // 17% - jauh gagal
        row('no_of_site_visit', 4, 24),                   // 17%
        row('no_of_new_quotation', 2, 16),                // 13%
    ]);

    $d = $this->funnel->diagnose($rows->last(), $rows);

    $causeKeys = collect($d['causes'])->pluck('metricKey')->all();

    expect($d)->not->toBeNull()
        ->and($causeKeys)->toContain('no_of_site_visit')
        ->and(collect($d['causes'])->where('type', 'driver_failed'))->not->toBeEmpty();
});

it('says there is no site visit activity at all when the driver is zero', function (): void {
    $rows = collect([
        row('no_of_lead', 500, 600),
        row('no_of_site_visit', 0, 24),                   // aktiviti kosong
        row('no_of_new_quotation', 1, 16),
    ]);

    $d = $this->funnel->diagnose($rows->last(), $rows);

    expect(collect($d['causes'])->where('type', 'driver_zero')->pluck('metricKey'))
        ->toContain('no_of_site_visit');
});

it('flags upstream data that has not been entered', function (): void {
    $rows = collect([
        row('no_of_lead', null, 600),                     // belum dikemas kini
        row('no_of_site_visit', null, 24),
        row('no_of_new_quotation', 2, 16),
    ]);

    $d = $this->funnel->diagnose($rows->last(), $rows);

    expect(collect($d['causes'])->where('type', 'driver_no_data'))->not->toBeEmpty();
});

it('calls it a conversion problem when upstream activity is healthy', function (): void {
    $rows = collect([
        row('no_of_lead', 580, 600),                      // 97% - sihat
        row('no_of_site_visit', 23, 24),                  // 96% - sihat
        row('no_of_new_quotation', 3, 16),                // tetap gagal
    ]);

    $d = $this->funnel->diagnose($rows->last(), $rows);

    expect(collect($d['causes'])->where('type', 'conversion'))->not->toBeEmpty()
        ->and(collect($d['causes'])->where('type', 'driver_failed'))->toBeEmpty()
        ->and($d['narrative'])->toContain('penukaran');
});

/*
|--------------------------------------------------------------------------
| Kesan HILIR ke atas syarikat
|--------------------------------------------------------------------------
*/

it('traces the damage from quotations down to collection', function (): void {
    $rows = collect([
        row('no_of_lead', 100, 600),
        row('no_of_new_quotation', 2, 16),
        row('amount_quotation_release', 50000, 2400000, MetricStatus::Red, null, MetricValueType::Currency),
        row('revenue_sales', 20000, 500000, MetricStatus::Red, null, MetricValueType::Currency),
        row('sales_collection_new', 5000, 150000, MetricStatus::Red, null, MetricValueType::Currency),
    ]);

    $d = $this->funnel->diagnose($rows->firstWhere('metricKey', 'no_of_new_quotation'), $rows);

    $impacted = collect($d['impacts'])->pluck('metricKey')->all();

    expect($impacted)->toContain('amount_quotation_release')
        ->and($impacted)->toContain('revenue_sales')
        ->and($impacted)->toContain('sales_collection_new')
        ->and($d['narrative'])->toContain('kutipan');
});

it('does not report healthy downstream metrics as impacted', function (): void {
    $rows = collect([
        row('no_of_new_quotation', 2, 16),
        row('revenue_sales', 500000, 500000, MetricStatus::Green, null, MetricValueType::Currency),
    ]);

    $d = $this->funnel->diagnose($rows->first(), $rows);

    expect(collect($d['impacts'])->pluck('metricKey'))->not->toContain('revenue_sales');
});

/*
|--------------------------------------------------------------------------
| Aktiviti yang diperlukan - berkuantiti
|--------------------------------------------------------------------------
*/

it('works out how many site visits are needed to close the quotation gap', function (): void {
    // 4 site visit menghasilkan 2 quotation - kadar 0.5 quotation setiap visit.
    // Jurang 14 quotation, jadi perlu 28 site visit lagi.
    $rows = collect([
        row('no_of_site_visit', 4, 24),
        row('no_of_new_quotation', 2, 16),
    ]);

    $d = $this->funnel->diagnose($rows->last(), $rows);
    $need = collect($d['required'])->firstWhere('metricKey', 'no_of_site_visit');

    expect($need)->not->toBeNull()
        ->and($need['needed'])->toBe(28)
        ->and($need['perWeek'])->toBe(7)
        ->and($need['isActual'])->toBeTrue();
});

it('falls back to the target ratio when there is no actual conversion yet', function (): void {
    // Tiada quotation langsung, jadi kadar sebenar tidak dapat dikira.
    // Nisbah sasaran: 16 quotation daripada 24 visit = 0.667
    $rows = collect([
        row('no_of_site_visit', 0, 24),
        row('no_of_new_quotation', 0, 16),
    ]);

    $d = $this->funnel->diagnose($rows->last(), $rows);
    $need = collect($d['required'])->firstWhere('metricKey', 'no_of_site_visit');

    expect($need['needed'])->toBe(24)
        ->and($need['isActual'])->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Tindakan
|--------------------------------------------------------------------------
*/

it('always produces at least one concrete action', function (): void {
    $rows = collect([
        row('no_of_lead', 100, 600),
        row('no_of_site_visit', 4, 24),
        row('no_of_new_quotation', 2, 16),
    ]);

    $d = $this->funnel->diagnose($rows->last(), $rows);

    expect($d['actions'])->not->toBeEmpty();

    foreach ($d['actions'] as $a) {
        expect($a['priority'])->toBeIn(['high', 'medium', 'low'])
            ->and($a['label'])->toBeString()->not->toBeEmpty()
            ->and($a['detail'])->toBeString()->not->toBeEmpty();
    }
});

it('quantifies the upstream action with a weekly figure', function (): void {
    $rows = collect([
        row('no_of_site_visit', 4, 24),
        row('no_of_new_quotation', 2, 16),
    ]);

    $d = $this->funnel->diagnose($rows->last(), $rows);
    $action = collect($d['actions'])->firstWhere('priority', 'high');

    expect($action['label'])->toContain('28')
        ->and($action['detail'])->toContain('7');
});

it('demands an action plan when none is recorded', function (): void {
    $rows = collect([row('no_of_lead', 100, 600)]);

    $d = $this->funnel->diagnose($rows->first(), $rows);

    expect(collect($d['causes'])->where('type', 'no_action_plan'))->not->toBeEmpty()
        ->and($d['narrative'])->toContain('pelan tindakan');
});

it('stops demanding a plan once one exists', function (): void {
    $rows = collect([
        row('no_of_lead', 100, 600, MetricStatus::Yellow, 'Tambah bajet iklan Facebook'),
    ]);

    $d = $this->funnel->diagnose($rows->first(), $rows);

    expect(collect($d['causes'])->where('type', 'no_action_plan'))->toBeEmpty();
});

/*
|--------------------------------------------------------------------------
| Skop
|--------------------------------------------------------------------------
*/

it('says nothing about metrics that are on target', function (): void {
    $rows = collect([row('no_of_lead', 700, 600, MetricStatus::Green)]);

    expect($this->funnel->diagnose($rows->first(), $rows))->toBeNull();
});

it('skips metrics with no data for the period', function (): void {
    $rows = collect([row('no_of_lead', null, 600, MetricStatus::BelumUpdate)]);

    expect($this->funnel->diagnose($rows->first(), $rows))->toBeNull();
});

it('treats cost-per-unit metrics as efficiency problems', function (): void {
    $rows = collect([
        row('cost_per_lead', 40, 15, MetricStatus::Red, null, MetricValueType::Currency),
    ]);

    $d = $this->funnel->diagnose($rows->first(), $rows);

    expect(collect($d['causes'])->where('type', 'efficiency'))->not->toBeEmpty();
});

it('orders diagnoses worst-first', function (): void {
    $rows = collect([
        row('no_of_lead', 500, 600, MetricStatus::Yellow, 'ada plan'),
        row('no_of_new_quotation', 1, 16),
    ]);

    $out = $this->funnel->diagnoseOwner($rows, $rows);

    expect($out->first()['severity'])->toBe('critical');
});
