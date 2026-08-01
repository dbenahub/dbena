<?php

declare(strict_types=1);

use App\Enums\MetricStatus;
use App\Enums\MetricValueType;
use App\Enums\PeriodMode;
use App\Enums\ServiceStatus;
use App\Services\DashboardMetricsService;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    $this->metrics = new DashboardMetricsService;
});

/*
|--------------------------------------------------------------------------
| Format — mesti padan output prototaip (fmtRM / fmtNum)
|--------------------------------------------------------------------------
*/

it('formats currency exactly like the prototype fmtRM()', function (): void {
    expect($this->metrics->formatRm(512480))->toBe('RM512,480')
        ->and($this->metrics->formatRm(1172276.4))->toBe('RM1,172,276')
        ->and($this->metrics->formatRm(0))->toBe('RM0')
        ->and($this->metrics->formatRm(null))->toBe('—');
});

it('formats plain numbers exactly like the prototype fmtNum()', function (): void {
    expect($this->metrics->formatNumber(600))->toBe('600')
        ->and($this->metrics->formatNumber(1371428.57))->toBe('1,371,429');
});

/*
|--------------------------------------------------------------------------
| getMonthWeeks — penanda HARI KHAMIS, bukan Isnin–Ahad
|--------------------------------------------------------------------------
*/

it('splits a month into four weeks using Thursday boundaries', function (): void {
    // Julai 2026: Khamis pada 2, 9, 16, 23, 30.
    // w1end = Khamis KEDUA (9), w2end = ketiga (16), w3end = keempat (23).
    expect($this->metrics->getMonthWeeks(7, 2026))->toBe([
        [1, 9], [10, 16], [17, 23], [24, 31],
    ]);
});

it('falls back to the last day when a month has too few Thursdays', function (): void {
    // Februari 2026 (28 hari): Khamis pada 5, 12, 19, 26.
    expect($this->metrics->getMonthWeeks(2, 2026))->toBe([
        [1, 12], [13, 19], [20, 26], [27, 28],
    ]);
});

it('always returns exactly four week ranges for every month of the year', function (): void {
    foreach (range(1, 12) as $month) {
        expect($this->metrics->getMonthWeeks($month, 2026))->toHaveCount(4);
    }
});

it('builds two-line week labels with padded date ranges', function (): void {
    $labels = $this->metrics->getCriticalWeekLabels(7, 2026);

    expect($labels)->toHaveCount(4)
        ->and($labels[0])->toContain('01/07-09/07')
        ->and($labels[3])->toContain('24/07-31/07');
});

/*
|--------------------------------------------------------------------------
| Status servis — ambang 35%
|--------------------------------------------------------------------------
*/

it('marks a service satisfactory at exactly the 35% threshold', function (): void {
    expect($this->metrics->calculateServiceStatus(35.0))->toBe(ServiceStatus::Memuaskan);
});

it('marks a service as needing improvement just below the threshold', function (): void {
    expect($this->metrics->calculateServiceStatus(34.9))->toBe(ServiceStatus::PerluDipertingkat)
        ->and($this->metrics->calculateServiceStatus(0.0))->toBe(ServiceStatus::PerluDipertingkat)
        ->and($this->metrics->calculateServiceStatus(null))->toBe(ServiceStatus::PerluDipertingkat);
});

/*
|--------------------------------------------------------------------------
| Status metrik kritikal — 4 cabang keputusan
|--------------------------------------------------------------------------
*/

it('returns Belum Update when the month has no data at all', function (): void {
    expect($this->metrics->calculateMetricStatus(null, 1000.0, null, false))
        ->toBe(MetricStatus::BelumUpdate);
});

it('returns Green once the target is fully met', function (): void {
    expect($this->metrics->calculateMetricStatus(1000.0, 1000.0, null, true))->toBe(MetricStatus::Green)
        ->and($this->metrics->calculateMetricStatus(1500.0, 1000.0, null, true))->toBe(MetricStatus::Green);
});

it('returns Yellow when behind target but an action plan exists', function (): void {
    expect($this->metrics->calculateMetricStatus(500.0, 1000.0, 'Tingkatkan site visit', true))
        ->toBe(MetricStatus::Yellow);
});

it('returns Red when behind target with no action plan', function (): void {
    expect($this->metrics->calculateMetricStatus(500.0, 1000.0, null, true))->toBe(MetricStatus::Red)
        ->and($this->metrics->calculateMetricStatus(500.0, 1000.0, '   ', true))->toBe(MetricStatus::Red);
});

it('does not award Green for a non-numeric target such as "Progress"', function (): void {
    // Sasaran 'Progress' disimpan sebagai NULL — tidak boleh dinilai (soalan Q6).
    expect($this->metrics->calculateMetricStatus(4890.0, null, null, true))->toBe(MetricStatus::Red)
        ->and($this->metrics->calculateMetricStatus(4890.0, null, 'Susulan kutipan', true))->toBe(MetricStatus::Yellow);
});

/*
|--------------------------------------------------------------------------
| Sasaran mingguan — ceil() untuk kiraan, round() untuk amaun
|--------------------------------------------------------------------------
*/

it('rounds weekly count targets up, matching the prototype ceil()', function (): void {
    // 16 quotation / 4 = 4 ; 11 / 4 = 2.75 → 3
    expect($this->metrics->calculateWeeklyTarget(16.0, MetricValueType::Number))->toBe(4.0)
        ->and($this->metrics->calculateWeeklyTarget(11.0, MetricValueType::Number))->toBe(3.0)
        ->and($this->metrics->calculateWeeklyTarget(30.0, MetricValueType::Number))->toBe(8.0);
});

it('rounds weekly currency targets to nearest, matching the prototype round()', function (): void {
    expect($this->metrics->calculateWeeklyTarget(2400000.0, MetricValueType::Currency))->toBe(600000.0)
        ->and($this->metrics->calculateWeeklyTarget(1000.0, MetricValueType::Currency))->toBe(250.0);
});

it('returns null when the metric has no numeric target', function (): void {
    expect($this->metrics->calculateWeeklyTarget(null, MetricValueType::Currency))->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Untung & run-rate
|--------------------------------------------------------------------------
*/

it('estimates net profit at the configured 18% margin', function (): void {
    expect($this->metrics->calculateEstimatedProfit(1000000.0))->toBe(180000.0);
});

it('derives months left dynamically instead of the hardcoded 5', function (): void {
    // PEMBETULAN isu #14 — prototaip mengunci monthsLeft = 5.
    expect($this->metrics->monthsLeftInFiscalYear(CarbonImmutable::create(2026, 7, 15)))->toBe(5)
        ->and($this->metrics->monthsLeftInFiscalYear(CarbonImmutable::create(2026, 1, 5)))->toBe(11)
        ->and($this->metrics->monthsLeftInFiscalYear(CarbonImmutable::create(2026, 11, 20)))->toBe(1);
});

it('never divides by zero in December', function (): void {
    expect($this->metrics->monthsLeftInFiscalYear(CarbonImmutable::create(2026, 12, 31)))->toBe(1)
        ->and($this->metrics->calculateRequiredRunRate(100000.0, CarbonImmutable::create(2026, 12, 31)))
        ->toBe(100000.0);
});

it('spreads the remaining gap across the months that are left', function (): void {
    expect($this->metrics->calculateRequiredRunRate(500000.0, CarbonImmutable::create(2026, 7, 1)))
        ->toBe(100000.0);
});

/*
|--------------------------------------------------------------------------
| Period multiplier — keputusan D3 (isu #18)
|--------------------------------------------------------------------------
*/

it('applies the period multipliers that the prototype computed but never used', function (): void {
    expect(PeriodMode::Weekly->multiplier())->toBe(1.0)
        ->and(PeriodMode::Monthly->multiplier())->toBe(4.33)
        ->and(PeriodMode::Quarterly->multiplier())->toBe(13.0);
});

it('converts a monthly figure into the selected period unit', function (): void {
    expect($this->metrics->toPeriodUnit(4330.0, PeriodMode::Monthly))->toBe(4330.0)
        ->and($this->metrics->toPeriodUnit(4330.0, PeriodMode::Quarterly))->toBe(12990.0)
        ->and(round($this->metrics->toPeriodUnit(4330.0, PeriodMode::Weekly)))->toBe(1000.0);
});

/*
|--------------------------------------------------------------------------
| Kadar penukaran & purata quotation - kini dari data sheet, bukan projek
|--------------------------------------------------------------------------
*/

it('no longer depends on the dropped projects table', function (): void {
    // Kedua-dua kaedah lama dibuang bersama jadual `projects`.
    expect(method_exists($this->metrics, 'calculateAvgProjectValue'))->toBeFalse()
        ->and(method_exists($this->metrics, 'calculateAvgDealValue'))->toBeFalse()
        ->and(method_exists($this->metrics, 'calculateAvgQuotationValue'))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Piramid tier
|--------------------------------------------------------------------------
*/

it('narrows each pyramid tier by 16 percentage points', function (): void {
    expect($this->metrics->calculateTierWidthPct(0))->toBe(100.0)
        ->and($this->metrics->calculateTierWidthPct(1))->toBe(84.0)
        ->and($this->metrics->calculateTierWidthPct(2))->toBe(68.0)
        ->and($this->metrics->calculateTierWidthPct(3))->toBe(52.0)
        ->and($this->metrics->calculateTierWidthPct(4))->toBe(36.0);
});

/*
|--------------------------------------------------------------------------
| Peratus perubahan
|--------------------------------------------------------------------------
*/

it('computes percentage change against target', function (): void {
    expect($this->metrics->percentChange(120.0, 100.0))->toBe(20.0)
        ->and($this->metrics->percentChange(80.0, 100.0))->toBe(-20.0)
        ->and($this->metrics->percentChange(50.0, 0.0))->toBe(0.0);
});

it('prefixes the change label with a direction arrow', function (): void {
    expect($this->metrics->changeLabel(12.34))->toBe('↑ +12.3%')
        ->and($this->metrics->changeLabel(-8.15))->toBe('↓ -8.2%');
});

/*
|--------------------------------------------------------------------------
| Warna bar pencapaian
|--------------------------------------------------------------------------
*/

it('colours achievement bars green, yellow then red', function (): void {
    expect($this->metrics->achievementBarColor(100.0))->toBe('oklch(0.55 0.15 145)')
        ->and($this->metrics->achievementBarColor(50.0))->toBe('oklch(0.78 0.15 85)')
        ->and($this->metrics->achievementBarColor(49.9))->toBe('oklch(0.55 0.2 25)')
        ->and($this->metrics->achievementBarColor(20.0, weekly: true))->toBe('oklch(0.6 0.22 350)');
});

it('scores owners green above 70 and red below 40', function (): void {
    expect($this->metrics->ownerScoreColor(70))->toBe('oklch(0.55 0.15 145)')
        ->and($this->metrics->ownerScoreColor(40))->toBe('oklch(0.78 0.15 85)')
        ->and($this->metrics->ownerScoreColor(39))->toBe('oklch(0.55 0.2 25)');
});

/*
|--------------------------------------------------------------------------
| Carta — koordinat SVG mesti padan prototaip
|--------------------------------------------------------------------------
*/

it('places chart dots on the exact coordinates the prototype used', function (): void {
    // x = i/(n-1) × 1160 + 20 ; y = (1 − v/max) × 340 + 20
    $chart = $this->metrics->buildChart(['A', 'B', 'C'], [50.0, 75.0, 100.0], [100.0, 100.0, 100.0]);

    expect($chart['dots'][0]['x'])->toBe('20.0')
        ->and($chart['dots'][2]['x'])->toBe('1180.0')
        ->and($chart['dots'][0]['y'])->toBe('20.0');
});

it('scales bar heights against the chart maximum', function (): void {
    $chart = $this->metrics->buildChart(['A', 'B'], [50.0, 100.0], [100.0, 100.0]);

    expect($chart['bars'][0]['pctHeight'])->toBe('50%')
        ->and($chart['bars'][1]['pctHeight'])->toBe('100%')
        ->and($chart['bars'][0]['hasValue'])->toBeTrue();
});

it('skips bars for months that have no data yet', function (): void {
    $chart = $this->metrics->buildChart(['A', 'B'], [100.0, null], [100.0, 100.0]);

    expect($chart['bars'][1]['hasValue'])->toBeFalse()
        ->and($chart['bars'][1]['pctHeight'])->toBe('0%')
        ->and($chart['bars'][1]['valueLabel'])->toBe('');
});

it('survives an entirely empty dataset without dividing by zero', function (): void {
    $chart = $this->metrics->buildChart(['A'], [null], [0.0]);

    expect($chart['maxLabel'])->toBe('1')
        ->and($chart['bars'])->toHaveCount(1);
});

/*
|--------------------------------------------------------------------------
| Donut
|--------------------------------------------------------------------------
*/

it('clamps the donut gradient between 0 and 100 percent', function (): void {
    expect($this->metrics->donutGradient(150.0))->toContain('0% 100%')
        ->and($this->metrics->donutGradient(-20.0))->toContain('0% 0%')
        ->and($this->metrics->donutGradient(null))->toContain('0% 0%');
});
