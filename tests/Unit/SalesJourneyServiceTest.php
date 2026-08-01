<?php

declare(strict_types=1);

use App\Enums\MetricStatus;
use App\Enums\MetricValueType;
use App\Services\SalesJourneyService;

beforeEach(function (): void {
    $this->journey = new SalesJourneyService;
});

/** Bina satu baris metrik untuk ujian. */
function jrow(
    string $key,
    ?float $actual,
    ?float $target,
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
        'status' => MetricStatus::Red,
        'actionPlan' => null,
    ];
}

/** Corong sihat sepenuhnya. */
function jsihat(): Illuminate\Support\Collection
{
    return collect([
        jrow('no_of_lead', 600, 600),
        jrow('no_of_site_visit', 24, 24),
        jrow('no_of_new_quotation', 16, 16),
        jrow('revenue_sales', 500000, 500000, MetricValueType::Currency),
    ]);
}

/*
|--------------------------------------------------------------------------
| Urutan perjalanan
|--------------------------------------------------------------------------
*/

it('lays the stages out in funnel order', function (): void {
    $out = $this->journey->build(jsihat());

    expect(collect($out['stages'])->pluck('key')->all())
        ->toBe(['lead', 'site_visit', 'quotation', 'sales']);
});

it('falls back to appointment when a service has no site visit metric', function (): void {
    // bina-rumah merekod appointment, bukan site visit. Peta mesti tetap
    // menunjukkan empat peringkat, bukan melangkau langkah tengah.
    $rows = collect([
        jrow('no_of_lead', 300, 300),
        jrow('no_of_appointment', 30, 30),
        jrow('no_of_new_quotation', 20, 20),
        jrow('revenue_sales', 500000, 500000, MetricValueType::Currency),
    ]);

    expect(collect($this->journey->build($rows)['stages'])->pluck('key')->all())
        ->toBe(['lead', 'site_visit', 'quotation', 'sales']);
});

/*
|--------------------------------------------------------------------------
| Halangan pertama
|--------------------------------------------------------------------------
*/

it('reports a clear road when every stage is on target', function (): void {
    $out = $this->journey->build(jsihat());

    expect($out['healthy'])->toBeTrue()
        ->and($out['firstBreak'])->toBeNull()
        ->and($out['blockedCount'])->toBe(0);
});

it('names the first break, not the worst one', function (): void {
    // Quotation lebih teruk secara peratusan, tetapi lead yang putus dahulu.
    // Menyalahkan quotation menghantar pemilik membetulkan gejala.
    $rows = collect([
        jrow('no_of_lead', 250, 600),        // 42%
        jrow('no_of_site_visit', 10, 24),    // 42%
        jrow('no_of_new_quotation', 1, 16),  // 6%
        jrow('revenue_sales', 100000, 500000, MetricValueType::Currency),
    ]);

    expect($this->journey->build($rows)['firstBreak']['key'])->toBe('lead');
});

it('separates blocked stages from the stage that is actually broken', function (): void {
    // Peringkat yang gagal memerlukan pembetulan sendiri; peringkat yang
    // tersekat pulih apabila hulu dibuka. Menandakan kesemuanya merah
    // menghantar pemilik mengejar empat masalah sedangkan hanya ada satu.
    $rows = collect([
        jrow('no_of_lead', 250, 600),
        jrow('no_of_site_visit', 10, 24),
        jrow('no_of_new_quotation', 1, 16),
        jrow('revenue_sales', 100000, 500000, MetricValueType::Currency),
    ]);

    $stages = collect($this->journey->build($rows)['stages'])->keyBy('key');

    expect($stages['lead']['blocked'])->toBeFalse()
        ->and($stages['lead']['cause'])->not->toBeNull()
        ->and($stages['site_visit']['blocked'])->toBeTrue()
        ->and($stages['site_visit']['blockedBy'])->toBe($stages['lead']['title']);
});

it('does not mark a healthy downstream stage as blocked', function (): void {
    // Kalau jualan tetap capai sasaran walaupun lead lemah, itu fakta —
    // bukan sesuatu untuk ditandakan merah kerana teori corong.
    $rows = collect([
        jrow('no_of_lead', 100, 600),
        jrow('no_of_site_visit', 24, 24),
        jrow('no_of_new_quotation', 16, 16),
        jrow('revenue_sales', 500000, 500000, MetricValueType::Currency),
    ]);

    $stages = collect($this->journey->build($rows)['stages'])->keyBy('key');

    expect($stages['sales']['blocked'])->toBeFalse()
        ->and($stages['sales']['status'])->toBe('green');
});

/*
|--------------------------------------------------------------------------
| Nombor yang boleh ditindaklanjuti
|--------------------------------------------------------------------------
*/

it('states the target as a weekly pace', function (): void {
    // "600 sebulan" ialah nombor untuk dipandang. "150 seminggu" ialah
    // nombor untuk dirancang.
    $stages = collect($this->journey->build(jsihat())['stages'])->keyBy('key');

    expect($stages['lead']['perWeekLabel'])->toContain('150');
});

it('shows the gap only when the stage is behind', function (): void {
    $rows = collect([
        jrow('no_of_lead', 474, 1035),
        jrow('no_of_site_visit', 24, 24),
        jrow('no_of_new_quotation', 16, 16),
        jrow('revenue_sales', 500000, 500000, MetricValueType::Currency),
    ]);

    $stages = collect($this->journey->build($rows)['stages'])->keyBy('key');

    expect($stages['lead']['gapLabel'])->toContain('561')
        ->and($stages['site_visit']['gapLabel'])->toBeNull();
});

it('survives a metric with no target', function (): void {
    // sales_collection_progress tiada sasaran berangka. Peta mesti tetap
    // dipaparkan dan bukan menghempaskan halaman servis.
    $rows = collect([
        jrow('no_of_lead', 100, null),
        jrow('no_of_site_visit', 5, 24),
        jrow('no_of_new_quotation', 16, 16),
        jrow('revenue_sales', 500000, 500000, MetricValueType::Currency),
    ]);

    $stages = collect($this->journey->build($rows)['stages'])->keyBy('key');

    expect($stages['lead']['status'])->toBe('none')
        ->and($stages['lead']['perWeekLabel'])->toBeNull()
        ->and($stages['lead']['broken'])->toBeFalse();
});

it('returns no stages when the service tracks none of the funnel metrics', function (): void {
    expect($this->journey->build(collect([jrow('cost_per_lead', 10, 10)]))['stages'])
        ->toBeEmpty();
});
