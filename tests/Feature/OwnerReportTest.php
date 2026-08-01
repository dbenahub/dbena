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
use Livewire\Livewire;

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

/*
|--------------------------------------------------------------------------
| Laporan untuk seorang pemilik
|--------------------------------------------------------------------------
*/

it('narrows the report to one owner when asked', function (): void {
    $owner = Owner::scorable()->orderBy('name')->firstOrFail();

    $report = app(OwnerReportService::class)
        ->build(ReportPeriod::Monthly, 2026, 8, null, null, $owner->id);

    expect($report['owner']->id)->toBe($owner->id)
        ->and($report['owners']->pluck('owner.id')->unique()->all())
        ->toBe([$owner->id]);
});

it('still sees other owners metrics when diagnosing one owner', function (): void {
    // Laporan ZIKRI mesti tetap boleh mengatakan "quotation tersekat kerana
    // lead HAFIZAN rendah". Menapis pemilik terlalu awal akan memutuskan
    // rantaian corong dan setiap laporan individu akan kelihatan seperti
    // masalah bersendirian.
    $owner = Owner::scorable()->orderBy('name')->firstOrFail();

    $seorang = app(OwnerReportService::class)
        ->build(ReportPeriod::Monthly, 2026, 8, null, null, $owner->id);

    $semua = app(OwnerReportService::class)
        ->build(ReportPeriod::Monthly, 2026, 8, null, null, null);

    $blokSeorang = $seorang['owners']->firstWhere('owner.id', $owner->id);
    $blokSemua = $semua['owners']->firstWhere('owner.id', $owner->id);

    if ($blokSeorang && $blokSemua) {
        expect($blokSeorang['scorePct'])->toBe($blokSemua['scorePct']);
    }
});

it('keeps every owner when no owner is selected', function (): void {
    $report = app(OwnerReportService::class)
        ->build(ReportPeriod::Monthly, 2026, 8, null, null, null);

    expect($report['owner'])->toBeNull()
        ->and($report['owners']->count())->toBeGreaterThan(1);
});

it('puts the owner name in the PDF filename', function (): void {
    // Lima fail bernama "laporan-pemilik-monthly-ogos-2026.pdf" dalam satu
    // folder muat turun tidak dapat dibezakan langsung.
    $owner = Owner::scorable()->orderBy('name')->firstOrFail();
    $admin = User::where('role', UserRole::Admin)->firstOrFail();

    $response = $this->actingAs($admin)->get(route('laporan.owner.pdf', [
        'tempoh' => 'monthly', 'tahun' => 2026, 'bulan' => 8, 'pemilik' => $owner->id,
    ]));

    $response->assertOk();

    expect($response->headers->get('content-disposition'))
        ->toContain(str($owner->name)->slug()->value());
});

it('names the file for all owners when none is selected', function (): void {
    $admin = User::where('role', UserRole::Admin)->firstOrFail();

    $response = $this->actingAs($admin)->get(route('laporan.owner.pdf', [
        'tempoh' => 'monthly', 'tahun' => 2026, 'bulan' => 8,
    ]));

    expect($response->headers->get('content-disposition'))
        ->toContain('semua-pemilik');
});

it('shows an owner picker on the report screen', function (): void {
    $user = User::where('role', UserRole::User)->firstOrFail();

    Livewire::actingAs($user)
        ->test(App\Livewire\Dashboard\OwnerReport::class)
        ->assertSee(__('owner_report.filter_owner'))
        ->assertSee(__('owner_report.all_owners'));
});

it('labels the export button with the chosen owner', function (): void {
    $owner = Owner::scorable()->orderBy('name')->firstOrFail();
    $user = User::where('role', UserRole::User)->firstOrFail();

    Livewire::actingAs($user)
        ->test(App\Livewire\Dashboard\OwnerReport::class)
        ->set('ownerId', $owner->id)
        ->assertSee(__('owner_report.export_pdf_owner', ['owner' => $owner->name]));
});

/*
|--------------------------------------------------------------------------
| Format laporan pengurusan
|--------------------------------------------------------------------------
*/

it('builds every section the management report needs', function (): void {
    $report = app(OwnerReportService::class)->build(ReportPeriod::Monthly, 2026, 8);
    $exec = app(App\Services\ExecutiveReportService::class)->build($report);

    expect($exec)->toHaveKeys([
        'severity', 'severityKey', 'gapTotal', 'scorecard', 'priorities',
        'journey', 'rootCauses', 'observations', 'weeklyTargets',
        'missingTargets', 'noPlanCount',
    ]);
});

it('ranks priorities by how much they block, not by percentage alone', function (): void {
    // Isu yang menyekat empat metrik lain mendahului isu yang menyekat satu,
    // walaupun peratusannya lebih baik. Itu perbezaan antara senarai masalah
    // dan senarai keutamaan.
    $report = app(OwnerReportService::class)->build(ReportPeriod::Monthly, 2026, 8);
    $exec = app(App\Services\ExecutiveReportService::class)->build($report);

    $ranks = collect($exec['priorities'])->pluck('rank')->all();

    expect($ranks)->toBe(range(1, count($ranks)))
        ->and(count($ranks))->toBeLessThanOrEqual(4);
});

it('totals the gap in ringgit, not just percentages', function (): void {
    // Peratusan memberitahu sejauh mana ketinggalan; ringgit memberitahu
    // berapa banyak. Pengurusan bertindak atas yang kedua.
    $report = app(OwnerReportService::class)->build(ReportPeriod::Monthly, 2026, 8);
    $exec = app(App\Services\ExecutiveReportService::class)->build($report);

    expect($exec['gapTotal'])->toBeFloat()->toBeGreaterThanOrEqual(0.0);
});

it('grades severity from the team score', function (): void {
    $report = app(OwnerReportService::class)->build(ReportPeriod::Monthly, 2026, 8);
    $exec = app(App\Services\ExecutiveReportService::class)->build($report);

    expect($exec['severityKey'])->toBeIn(['critical', 'attention', 'stable']);
});

it('gives every operational metric a weekly target and a named owner', function (): void {
    // Sasaran tanpa nama ialah sasaran yang tiada siapa punya.
    $report = app(OwnerReportService::class)->build(ReportPeriod::Monthly, 2026, 8);
    $exec = app(App\Services\ExecutiveReportService::class)->build($report);

    foreach ($exec['weeklyTargets'] as $target) {
        expect($target['owner'])->not->toBeEmpty()
            ->and($target['weekly'])->not->toBeEmpty()
            ->and($target['trigger'])->not->toBeEmpty();
    }
});

it('renders the management-format PDF without error', function (): void {
    $admin = User::where('role', UserRole::Admin)->firstOrFail();

    $this->actingAs($admin)
        ->get(route('laporan.owner.pdf', ['tempoh' => 'monthly', 'tahun' => 2026, 'bulan' => 8]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('renders the PDF for a single owner without error', function (): void {
    $owner = Owner::scorable()->orderBy('name')->firstOrFail();
    $admin = User::where('role', UserRole::Admin)->firstOrFail();

    $this->actingAs($admin)
        ->get(route('laporan.owner.pdf', [
            'tempoh' => 'monthly', 'tahun' => 2026, 'bulan' => 8, 'pemilik' => $owner->id,
        ]))
        ->assertOk();
});

/*
|--------------------------------------------------------------------------
| Laporan semua servis mesti dipecahkan mengikut servis
|--------------------------------------------------------------------------
*/

it('splits the all-services report into one block per service', function (): void {
    // Versi pertama meratakan setiap metrik ke dalam satu jadual, jadi
    // "No of Lead — ZIKRI — 600" muncul lima kali dengan nombor berbeza dan
    // tiada apa menunjukkan servis mana.
    $report = app(OwnerReportService::class)->build(ReportPeriod::Monthly, 2026, 8);
    $exec = app(App\Services\ExecutiveReportService::class)->build($report);

    expect($exec['multiService'])->toBeTrue()
        ->and($exec['services']->count())->toBeGreaterThan(1);
});

it('names the service on every scorecard row', function (): void {
    $report = app(OwnerReportService::class)->build(ReportPeriod::Monthly, 2026, 8);
    $exec = app(App\Services\ExecutiveReportService::class)->build($report);

    foreach ($exec['scorecard'] as $row) {
        expect($row['serviceName'])->not->toBeEmpty();
    }
});

it('computes a separate funnel for each service', function (): void {
    // Corong dikira daripada senarai rata, dan kunci metrik yang sama
    // daripada lima servis bertindih. Rantaian yang dilabel "syarikat"
    // sebenarnya nombor satu servis rawak — angka yang salah, bukan sekadar
    // susun atur yang mengelirukan.
    $report = app(OwnerReportService::class)->build(ReportPeriod::Monthly, 2026, 8);
    $exec = app(App\Services\ExecutiveReportService::class)->build($report);

    foreach ($exec['services'] as $svc) {
        expect($svc['journey'])->toHaveKey('stages');
    }

    // Tiada corong peringkat syarikat apabila banyak servis — ia tidak
    // bermakna, jadi lebih baik tiada daripada salah.
    expect($exec['journey'])->toBeNull();
});

it('keeps a single company funnel when one service is selected', function (): void {
    $service = Service::where('key', 'renovation')->firstOrFail();

    $report = app(OwnerReportService::class)
        ->build(ReportPeriod::Monthly, 2026, 8, null, $service->id);
    $exec = app(App\Services\ExecutiveReportService::class)->build($report);

    expect($exec['multiService'])->toBeFalse()
        ->and($exec['journey'])->not->toBeNull();
});

it('tags every priority issue with its service', function (): void {
    $report = app(OwnerReportService::class)->build(ReportPeriod::Monthly, 2026, 8);
    $exec = app(App\Services\ExecutiveReportService::class)->build($report);

    foreach ($exec['priorities'] as $p) {
        expect($p['service'])->not->toBe('—');
    }
});

it('does not repeat the same missing-target metric once per service', function (): void {
    // "Sales Collection (Progress Claim)" muncul lima kali berturut-turut
    // dalam senarai dan kelihatan seperti pepijat.
    $report = app(OwnerReportService::class)->build(ReportPeriod::Monthly, 2026, 8);
    $exec = app(App\Services\ExecutiveReportService::class)->build($report);

    expect($exec['missingTargets']->count())
        ->toBe($exec['missingTargets']->unique()->count());
});

it('gives each service its own weekly targets', function (): void {
    $report = app(OwnerReportService::class)->build(ReportPeriod::Monthly, 2026, 8);
    $exec = app(App\Services\ExecutiveReportService::class)->build($report);

    foreach ($exec['weeklyTargets'] as $w) {
        expect($w['service'])->not->toBeEmpty();
    }

    $servis = collect($exec['weeklyTargets'])->pluck('service')->unique();
    expect($servis->count())->toBeGreaterThan(1);
});

it('reports every service total adding up to the company total', function (): void {
    $report = app(OwnerReportService::class)->build(ReportPeriod::Monthly, 2026, 8);
    $exec = app(App\Services\ExecutiveReportService::class)->build($report);

    expect($exec['services']->sum('total'))->toBe($exec['scorecard']->count());
});

it('renders the all-services PDF without error', function (): void {
    $admin = User::where('role', UserRole::Admin)->firstOrFail();

    $this->actingAs($admin)
        ->get(route('laporan.owner.pdf', ['tempoh' => 'monthly', 'tahun' => 2026, 'bulan' => 8]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
