<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\Service;
use App\Services\DashboardMetricsService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Laporan extends Component
{
    #[Url(as: 'tahun')]
    public int $year;

    #[Url(as: 'bulan')]
    public int $month;

    #[Url(as: 'servis')]
    public ?string $serviceKey = null;

    public function mount(): void
    {
        $this->year = (int) now()->year;
        $this->month = (int) now()->month;
    }

    public function selectMonth(int $month): void
    {
        $this->month = max(1, min(12, $month));
    }

    public function selectYear(int $year): void
    {
        $this->year = $year;
    }

    public function selectService(?string $key): void
    {
        $this->serviceKey = $key;
    }

    public function render(DashboardMetricsService $metrics): View
    {
        $selected = $this->serviceKey ? Service::where('key', $this->serviceKey)->first() : null;
        $services = $selected ? collect([$selected]) : Service::orderBy('sort_order')->get();
        $allServices = Service::orderBy('sort_order')->get();

        $yearFactor = $metrics->yearFactor($this->year);

        $rows = $services->map(function (Service $service) use ($metrics, $yearFactor) {
            $actual = $metrics->sumMetricActual(['revenue_sales'], $this->year, $this->month, $service->id) * $yearFactor;
            $target = (float) $service->monthly_target * $yearFactor;
            $pct = $target > 0 ? $actual / $target * 100 : 0.0;
            $status = $metrics->calculateServiceStatus($pct);

            return [
                'service' => $service,
                'name' => $service->name,
                'icon' => $service->icon_class,
                'actual' => $actual,
                'salesLabel' => $metrics->formatRm($actual),
                'target' => $target,
                'targetLabel' => $metrics->formatRm($target),
                'pct' => $pct,
                'status' => $status,
                'statusLabel' => $status->label(),
                'statusColor' => $status->color(),
                'barColor' => $status->barColor(),
            ];
        });

        $totalRevenue = $rows->sum('actual');

        // PEMBETULAN isu #16 — jumlah quotation SEBENAR, bukan revenue × 3.83.
        $totalQuotation = $metrics->sumMetricActual(
            ['amount_quotation_release'],
            $this->year,
            $this->month,
            $selected?->id
        );

        // PEMBETULAN isu #15 — kadar penukaran DIKIRA, bukan hardcode 8.2%.
        $conversionRate = $metrics->calculateConversionRate($this->year, $this->month, $selected?->id);

        // ── Carta trend ──
        $monthLabels = __('calendar.months_short');
        $chartActuals = [];
        $chartTargets = [];

        for ($m = 1; $m <= 12; $m++) {
            $value = $metrics->sumMetricActual(['revenue_sales'], $this->year, $m, $selected?->id) * $yearFactor;
            $chartActuals[] = $value > 0 ? $value : null;
            $chartTargets[] = (float) $services->sum('monthly_target') * $yearFactor;
        }

        return view('livewire.dashboard.laporan', [
            'rows' => $rows,
            'allServices' => $allServices,
            'selected' => $selected,
            'totalRevenueLabel' => $metrics->formatRm($totalRevenue),
            'totalQuotationLabel' => $metrics->formatRm($totalQuotation),
            'conversionRateLabel' => $metrics->formatPercent($conversionRate),
            'avgQuotationLabel' => $metrics->formatRm(
                $metrics->calculateAvgQuotationValue($this->year, $this->month, $selected?->id)
            ),
            'reportChart' => $metrics->buildChart($monthLabels, $chartActuals, $chartTargets),
            'monthLabels' => $monthLabels,
            'monthsFull' => __('calendar.months_full'),
            'metrics' => $metrics,
            'years' => range(2023, 2032),
        ])->layoutData([
            'pageTitle' => __('laporan.page_title'),
            'pageSubtitle' => __('laporan.page_subtitle'),
        ]);
    }
}
