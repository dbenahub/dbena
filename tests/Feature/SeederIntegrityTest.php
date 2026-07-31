<?php

declare(strict_types=1);

use App\Models\CriticalMetric;
use App\Models\IndexTier;
use App\Models\Owner;
use App\Models\Project;
use App\Models\Service;
use App\Models\YearGrowthFactor;

beforeEach(fn () => $this->seed());

it('seeds exactly the five DBENA services', function (): void {
    expect(Service::count())->toBe(5)
        ->and(Service::pluck('key')->sort()->values()->all())
        ->toBe(['bina-rumah', 'divider', 'kabinet', 'mihrab', 'renovation']);
});

it('seeds all 49 critical metric rows from the prototype', function (): void {
    expect(CriticalMetric::count())->toBe(49);
});

it('gives each service the row count the prototype had', function (): void {
    // Divider sengaja mempunyai 9 baris, bukan 10.
    $counts = Service::withCount('criticalMetrics')->get()->pluck('critical_metrics_count', 'key');

    expect($counts['renovation'])->toBe(10)
        ->and($counts['kabinet'])->toBe(10)
        ->and($counts['bina-rumah'])->toBe(10)
        ->and($counts['mihrab'])->toBe(10)
        ->and($counts['divider'])->toBe(9);
});

it('omits site visit for Divider and uses appointment for Bina Rumah', function (): void {
    $divider = Service::where('key', 'divider')->firstOrFail();
    $binaRumah = Service::where('key', 'bina-rumah')->firstOrFail();

    expect($divider->siteVisitMetric())->toBeNull()
        ->and($divider->metricByKey('cost_per_quotation'))->not->toBeNull()
        ->and($binaRumah->siteVisitMetric()?->metric_key)->toBe('no_of_appointment');
});

it('stores "Progress" targets as text rather than a number', function (): void {
    $metric = CriticalMetric::where('metric_key', 'sales_collection_progress')->first();
    $target = $metric->targetForYear(2026);

    expect($target->monthly_target)->toBeNull()
        ->and($target->target_text)->toBe('Progress')
        ->and($target->isNumeric())->toBeFalse();
});

it('seeds the five index tiers with the exact prototype thresholds', function (): void {
    expect(IndexTier::count())->toBe(5)
        ->and((float) IndexTier::where('key', 'survival')->value('monthly_revenue_threshold'))->toBe(457142.86)
        ->and((float) IndexTier::where('key', 'sustainability')->value('monthly_revenue_threshold'))->toBe(1371428.57);
});

it('seeds four core PICs plus the INFO system label', function (): void {
    expect(Owner::where('is_core', true)->where('is_system', false)->count())->toBe(4)
        ->and(Owner::where('is_system', true)->pluck('name')->all())->toBe(['INFO']);
});

it('gives every PIC a distinct colour from one source of truth', function (): void {
    // PEMBETULAN isu #12 — prototaip menjana warna berbeza di setiap skrin.
    $colours = Owner::where('is_system', false)->pluck('color_token');

    expect($colours->unique()->count())->toBe($colours->count());
});

it('seeds the 16 demo projects', function (): void {
    expect(Project::count())->toBe(16);
});

it('seeds growth factors with 2026 as the 1.0 baseline', function (): void {
    expect(YearGrowthFactor::count())->toBe(10)
        ->and(YearGrowthFactor::factorFor(2026))->toBe(1.0)
        ->and(YearGrowthFactor::factorFor(2023))->toBe(0.58);
});

it('corrects the English service names that the prototype had reversed', function (): void {
    // Prototaip: renovation.nameEn = "Ubah Suai", divider.nameEn = "Pembahagi" (BM!)
    expect(Service::where('key', 'renovation')->value('name_en'))->not->toBe('Ubah Suai')
        ->and(Service::where('key', 'divider')->value('name_en'))->not->toBe('Pembahagi')
        ->and(Service::where('key', 'mihrab')->value('name_en'))->not->toBe('');
});

it('seeds weekly data for more than a single month', function (): void {
    // PEMBETULAN isu #13 — prototaip hanya mempunyai data untuk Julai.
    $months = DB::table('critical_weekly_entries')->distinct()->pluck('month');

    expect($months->count())->toBeGreaterThan(1);
});
