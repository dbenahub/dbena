<?php

declare(strict_types=1);

use App\Enums\MetricValueType;
use App\Models\StrategyRow;
use App\Services\TargetAlignmentService;

beforeEach(function (): void {
    $this->align = new TargetAlignmentService;
});

/** Satu baris Data Kritikal. */
function crow(string $key, string $label, ?float $target, MetricValueType $type = MetricValueType::Currency): array
{
    return ['metricKey' => $key, 'label' => $label, 'target' => $target, 'valueType' => $type];
}

/** Satu baris pelan (tidak disimpan — perbandingan tidak menyentuh DB). */
function prow(string $kra, string $kpi, ?string $target, ?string $pic = 'Zikri'): StrategyRow
{
    return new StrategyRow(['kra' => $kra, 'kpi' => $kpi, 'target' => $target, 'pic' => $pic]);
}

/*
|--------------------------------------------------------------------------
| Sasaran yang sepadan tidak menghasilkan bunyi
|--------------------------------------------------------------------------
*/

it('says nothing when both sheets agree', function (): void {
    $out = $this->align->compare(
        collect([crow('revenue_sales', 'Revenue/Sales', 500000.0)]),
        collect([prow('Peningkatan Jualan Renovation', 'Jualan bulanan', 'RM500,000 / bulan')])
    );

    expect($out)->toBe([]);
});

it('converts a weekly plan target to monthly before comparing', function (): void {
    // Pelan menulis mingguan; Data Kritikal sentiasa bulanan. Tanpa
    // penukaran setiap pasangan mingguan kelihatan berbeza empat kali
    // ganda dan amaran menjadi bising sehingga tiada gunanya.
    $out = $this->align->compare(
        collect([crow('no_of_lead', 'No of Lead', 600.0, MetricValueType::Number)]),
        collect([prow('Lead Management', 'Lead dilayan & direkod', '150 lead / minggu')])
    );

    expect($out)->toBe([]);
});

it('matches the quotation value target across periods', function (): void {
    // RM600,000 seminggu ialah RM2,400,000 sebulan.
    $out = $this->align->compare(
        collect([crow('amount_quotation_release', 'Amount Quotation Release (New)', 2400000.0)]),
        collect([prow('Quotation Performance', 'Nilai quotation dikeluarkan', '> RM600,000 / minggu')])
    );

    expect($out)->toBe([]);
});

it('tolerates a rounding difference of under one', function (): void {
    // Kedua-dua nilai melalui pembundaran perpuluhan dalam perjalanan
    // dari sheet ke pangkalan data, dan amaran sebanyak RM0.0001 tidak
    // boleh diselesaikan oleh sesiapa.
    $out = $this->align->compare(
        collect([crow('revenue_sales', 'Revenue/Sales', 499999.9999)]),
        collect([prow('Peningkatan Jualan', 'Jualan bulanan', 'RM500,000 / bulan')])
    );

    expect($out)->toBe([]);
});

/*
|--------------------------------------------------------------------------
| Percanggahan sebenar mesti bercakap
|--------------------------------------------------------------------------
*/

it('flags a target the two sheets disagree on', function (): void {
    // Kedua-dua nombor kelihatan rasmi pada skrinnya sendiri, jadi
    // pemilik yang mencapai satu daripadanya percaya dia sudah selamat.
    $out = $this->align->compare(
        collect([crow('revenue_sales', 'Revenue/Sales', 450000.0)]),
        collect([prow('Peningkatan Jualan', 'Jualan bulanan', 'RM500,000 / bulan')])
    );

    expect($out)->toHaveKey('revenue_sales')
        ->and($out['revenue_sales']['criticalLabel'])->toContain('450,000')
        ->and($out['revenue_sales']['plannedLabel'])->toContain('500,000');
});

it('carries the raw plan wording so the admin can find the cell', function (): void {
    $out = $this->align->compare(
        collect([crow('no_of_site_visit', 'No of Site Visit', 20.0, MetricValueType::Number)]),
        collect([prow('Site Visit Conversion', 'Site visit berjaya dibuat', '6 site visit / minggu')])
    );

    expect($out['no_of_site_visit']['planTargetText'])->toBe('6 site visit / minggu')
        ->and($out['no_of_site_visit']['planKra'])->toBe('Site Visit Conversion')
        ->and($out['no_of_site_visit']['planPic'])->toBe('Zikri');
});

it('says which side is higher', function (): void {
    $out = $this->align->compare(
        collect([crow('revenue_sales', 'Revenue/Sales', 450000.0)]),
        collect([prow('Peningkatan Jualan', 'Jualan bulanan', 'RM500,000 / bulan')])
    );

    expect($out['revenue_sales']['higher'])->toBe('plan');
});

/*
|--------------------------------------------------------------------------
| Amaran palsu adalah kegagalan
|--------------------------------------------------------------------------
*/

it('stays quiet for metrics the plan does not cover', function (): void {
    // Ads Spend, CPL dan CPA tiada baris dalam pelan. Mereka-reka
    // padanan untuk metrik itu menghasilkan amaran palsu yang mengajar
    // orang mengabaikan penunjuk ini sepenuhnya.
    $out = $this->align->compare(
        collect([
            crow('ads_spend', 'Ads Spend', 6000.0),
            crow('cost_per_lead', 'Cost Per Lead (CPL)', 10.0),
        ]),
        collect([prow('Peningkatan Jualan', 'Jualan bulanan', 'RM500,000 / bulan')])
    );

    expect($out)->toBe([]);
});

it('stays quiet when there is no plan at all', function (): void {
    // Menandakan setiap sasaran sebagai tidak disahkan akan mengecat
    // seluruh jadual pada hari pertama, sebelum sesiapa sempat
    // menyambung sheet.
    $out = $this->align->compare(
        collect([crow('revenue_sales', 'Revenue/Sales', 450000.0)]),
        collect([])
    );

    expect($out)->toBe([]);
});

it('ignores a plan target that is a commitment, not a number', function (): void {
    // "Project siap awal dari jadual" tidak boleh dibandingkan dengan
    // apa-apa.
    $out = $this->align->compare(
        collect([crow('revenue_sales', 'Revenue/Sales', 450000.0)]),
        collect([prow('Peningkatan Jualan', 'Jualan bulanan', 'Project siap awal dari jadual')])
    );

    expect($out)->toBe([]);
});

it('ignores the daily breakdown of a weekly target', function (): void {
    // "25 lead / hari" ialah pecahan mingguan untuk kegunaan lapangan,
    // bukan komitmen bulanan berasingan, dan bilangan hari bekerja
    // sebulan tidak pernah dinyatakan di mana-mana.
    $out = $this->align->compare(
        collect([crow('no_of_lead', 'No of Lead', 600.0, MetricValueType::Number)]),
        collect([prow('Lead Management', 'Lead dilayan', '25 lead / hari')])
    );

    expect($out)->toBe([]);
});

it('uses the first target when a merged cell holds several', function (): void {
    // Yang pertama ialah komitmen utama; selebihnya pecahannya.
    $out = $this->align->compare(
        collect([crow('no_of_lead', 'No of Lead', 600.0, MetricValueType::Number)]),
        collect([prow('Lead Management', 'Lead dilayan', "150 lead / minggu\n25 lead / hari")])
    );

    expect($out)->toBe([]);
});

it('skips a metric with no target set yet', function (): void {
    $out = $this->align->compare(
        collect([crow('revenue_sales', 'Revenue/Sales', null)]),
        collect([prow('Peningkatan Jualan', 'Jualan bulanan', 'RM500,000 / bulan')])
    );

    expect($out)->toBe([]);
});

it('uses four weeks a month, not the calendar average', function (): void {
    // Pelan ditulis oleh manusia yang bermaksud empat minggu apabila
    // mereka menulis sebulan. Purata kalendar 4.345 akan menandakan
    // setiap pasangan mingguan sebagai tidak sepadan sebanyak 8%.
    $out = $this->align->compare(
        collect([crow('no_of_site_visit', 'No of Site Visit', 24.0, MetricValueType::Number)]),
        collect([prow('Site Visit Conversion', 'Site visit berjaya dibuat', '6 site visit / minggu')])
    );

    expect($out)->toBe([]);
});
