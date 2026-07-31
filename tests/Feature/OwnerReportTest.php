<?php

declare(strict_types=1);

use App\Enums\ReportPeriod;
use App\Enums\UserRole;
use App\Models\CriticalMetricMonth;
use App\Models\CriticalWeeklyEntry;
use App\Models\Owner;
use App\Models\Service;
use App\Models\User;
use App\Services\OwnerReportService;

beforeEach(function (): void {
    $this->seed();

    $this->user = User::where('role', UserRole::User)->firstOrFail();
    $this->service = Service::where('key', 'renovation')->firstOrFail();
    $this->reports = app(OwnerReportService::class);
});

it('builds a report for every scorable PIC', function (): void {
    $report = $this->reports->build(ReportPeriod::Monthly, 2026, 7);

    expect($report['owners'])->not->toBeEmpty()
        ->and($report['summary']['ownerCount'])->toBeGreaterThan(0);
});

it('excludes the INFO system label from PIC scoring', function (): void {
    $report = $this->reports->build(ReportPeriod::Monthly, 2026, 7);

    expect($report['owners']->pluck('name'))->not->toContain('INFO');
});

it('ranks PICs from strongest to weakest', function (): void {
    $scores = $this->reports->build(ReportPeriod::Monthly, 2026, 7)['owners']->pluck('scorePct')->all();

    expect($scores)->toBe(collect($scores)->sortDesc()->values()->all());
});

it('grades a perfect score as A and a zero score as E', function (): void {
    $grade = fn (int $score) => (new ReflectionMethod(OwnerReportService::class, 'grade'))
        ->invoke($this->reports, $score);

    expect($grade(100))->toBe('A')
        ->and($grade(85))->toBe('A')
        ->and($grade(70))->toBe('B')
        ->and($grade(55))->toBe('C')
        ->and($grade(40))->toBe('D')
        ->and($grade(0))->toBe('E');
});

it('writes commentary that names the PIC and the score', function (): void {
    $block = $this->reports->build(ReportPeriod::Monthly, 2026, 7)['owners']->first();

    expect($block['commentary'])->not->toBeEmpty()
        ->and($block['commentary'][0])->toContain($block['name'])
        ->and($block['commentary'][0])->toContain((string) $block['scorePct']);
});

it('always produces at least one required action', function (): void {
    foreach ($this->reports->build(ReportPeriod::Monthly, 2026, 7)['owners'] as $block) {
        expect($block['actions'])->not->toBeEmpty();

        foreach ($block['actions'] as $action) {
            expect($action['priority'])->toBeIn(['high', 'medium', 'low'])
                ->and($action['label'])->toBeString()->not->toBeEmpty()
                ->and($action['detail'])->toBeString()->not->toBeEmpty();
        }
    }
});

it('raises a high-priority action for a metric with no action plan', function (): void {
    $owner = Owner::where('name', 'ZIKRI')->firstOrFail();
    $metric = $this->service->metricByKey('no_of_lead');

    // Jauh di bawah sasaran, tiada pelan tindakan → Red
    CriticalMetricMonth::updateOrCreate(
        ['critical_metric_id' => $metric->id, 'year' => 2026, 'month' => 7],
        ['owner_id' => $owner->id, 'action_plan' => null]
    );

    CriticalWeeklyEntry::updateOrCreate(
        ['critical_metric_id' => $metric->id, 'year' => 2026, 'month' => 7, 'week_number' => 1],
        ['value' => 1]
    );

    $block = $this->reports->build(ReportPeriod::Monthly, 2026, 7, null, $this->service->id)['owners']
        ->firstWhere('name', 'ZIKRI');

    $highPriority = collect($block['actions'])->where('priority', 'high');

    expect($highPriority)->not->toBeEmpty();
});

it('quarters the monthly target when reporting on a single week', function (): void {
    $owner = Owner::where('name', 'ZIKRI')->firstOrFail();
    $metric = $this->service->metricByKey('no_of_site_visit'); // sasaran bulanan 24 → 6/minggu

    CriticalMetricMonth::updateOrCreate(
        ['critical_metric_id' => $metric->id, 'year' => 2026, 'month' => 7],
        ['owner_id' => $owner->id]
    );

    CriticalWeeklyEntry::updateOrCreate(
        ['critical_metric_id' => $metric->id, 'year' => 2026, 'month' => 7, 'week_number' => 1],
        ['value' => 6]
    );

    $block = $this->reports->build(ReportPeriod::Weekly, 2026, 7, 1, $this->service->id)['owners']
        ->firstWhere('name', 'ZIKRI');

    $row = collect($block['metrics'])->firstWhere('label', $metric->label);

    expect((float) $row['target'])->toBe(6.0)
        ->and($row['pct'])->toBe(100.0);
});

it('spans all twelve months in yearly mode', function (): void {
    $report = $this->reports->build(ReportPeriod::Yearly, 2026, 7);

    expect($report['months'])->toBe(range(1, 12))
        ->and($report['periodLabel'])->toBe('2026');
});

it('narrows to one service when filtered', function (): void {
    $all = $this->reports->build(ReportPeriod::Monthly, 2026, 7);
    $filtered = $this->reports->build(ReportPeriod::Monthly, 2026, 7, null, $this->service->id);

    expect($filtered['service']->key)->toBe('renovation')
        ->and($filtered['summary']['totalMetrics'])->toBeLessThanOrEqual($all['summary']['totalMetrics']);
});

it('summarises the team score across all PICs', function (): void {
    $summary = $this->reports->build(ReportPeriod::Monthly, 2026, 7)['summary'];

    expect($summary['teamScore'])->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(100)
        ->and($summary['headline'])->toContain((string) $summary['teamScore']);
});

it('flags PICs that need attention', function (): void {
    $summary = $this->reports->build(ReportPeriod::Monthly, 2026, 7)['summary'];

    foreach ($summary['needsAttention'] as $item) {
        expect($item['scorePct'] < 55 || $item['red'] > 0)->toBeTrue();
    }
});

/*
|--------------------------------------------------------------------------
| Halaman & PDF
|--------------------------------------------------------------------------
*/

it('renders the owner report page for any signed-in user', function (): void {
    $this->actingAs($this->user)->get(route('laporan.owner'))->assertOk();
});

it('requires authentication', function (): void {
    $this->get(route('laporan.owner'))->assertRedirect(route('login'));
    $this->get(route('laporan.owner.pdf'))->assertRedirect(route('login'));
});

it('downloads a PDF', function (): void {
    $response = $this->actingAs($this->user)->get(route('laporan.owner.pdf', [
        'tempoh' => 'monthly', 'tahun' => 2026, 'bulan' => 7,
    ]));

    $response->assertOk()->assertHeader('content-type', 'application/pdf');
})->skip(fn () => ! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class), 'dompdf belum dipasang');

it('clamps an out-of-range period in the PDF request', function (): void {
    $this->actingAs($this->user)
        ->get(route('laporan.owner.pdf', ['tempoh' => 'bogus', 'bulan' => 99, 'minggu' => 9]))
        ->assertOk();
})->skip(fn () => ! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class), 'dompdf belum dipasang');
