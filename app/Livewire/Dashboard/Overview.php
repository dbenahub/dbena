<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Enums\PeriodMode;
use App\Enums\ViewMode;
use App\Models\IndexTier;
use App\Models\Service;
use App\Services\DashboardMetricsService;
use App\Services\RoadmapService;
use App\Services\WeeklyPriorityService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Dashboard Utama — port seksyen `isDashboard` prototaip.
 *
 * Perbezaan utama daripada prototaip:
 *  • Semua nilai datang dari MySQL, bukan array JS hardcoded.
 *  • `baseActualRatios` (taburan 7 bulan hardcoded, isu #17) digantikan
 *    dengan jumlah sebenar per bulan dari critical_weekly_entries.
 *  • Dropdown Period benar-benar berfungsi (keputusan D3, isu #18).
 */
#[Layout('components.layouts.app')]
class Overview extends Component
{
    #[Url(as: 'tahun')]
    public int $year;

    #[Url(as: 'bulan')]
    public int $month;

    #[Url(as: 'mod')]
    public string $viewMode = 'monthly';

    #[Url(as: 'period')]
    public string $period = 'monthly';

    /*
     * Tahun roadmap TERPISAH daripada tahun dashboard.
     *
     * Roadmap ialah dokumen perancangan — melihat rancangan tahun hadapan
     * pada bulan Ogos ialah perkara biasa. Mengikatnya kepada penapis
     * tahun dashboard bermakna menukar rancangan turut menukar setiap
     * nombor prestasi di halaman, dan pengguna kehilangan tempatnya.
     */
    #[Url(as: 'roadmap')]
    public ?int $roadmapYear = null;

    /**
     * Kaedah dinamakan berbeza daripada sifat dengan sengaja.
     *
     * Livewire menyelesaikan wire:click terhadap kaedah DAN sifat. Kaedah
     * bernama sama dengan sifatnya ialah kekaburan yang gagal secara
     * senyap dan bukan dengan ralat.
     */
    public function showRoadmapYear(int $year): void
    {
        // Julat dihadkan supaya URL yang dikarang tangan tidak menghasilkan
        // tahun 9999 dan dua belas sel kosong yang kelihatan seperti bug.
        $this->roadmapYear = max(2023, min(2035, $year));
    }

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

    public function selectPeriod(string $period): void
    {
        if (in_array($period, array_column(PeriodMode::cases(), 'value'), true)) {
            $this->period = $period;
        }
    }

    private function mode(): ViewMode
    {
        return ViewMode::tryFrom($this->viewMode) ?? ViewMode::Monthly;
    }

    private function periodMode(): PeriodMode
    {
        return PeriodMode::tryFrom($this->period) ?? PeriodMode::Monthly;
    }

    public function render(DashboardMetricsService $metrics): View
    {
        $mode = $this->mode();
        $periodMode = $this->periodMode();
        $yearFactor = $metrics->yearFactor($this->year);

        $services = Service::orderBy('sort_order')->with('monthlyTargets')->get();
        $tiers = IndexTier::orderBy('sort_order')->get();

        // ── Jualan sebenar per servis per bulan (dari data kritikal) ──
        $monthlyByService = [];
        foreach ($services as $service) {
            for ($m = 1; $m <= 12; $m++) {
                $monthlyByService[$service->id][$m] =
                    $metrics->sumMetricActual(['revenue_sales'], $this->year, $m, $service->id) * $yearFactor;
            }
        }

        $monthlyTotals = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyTotals[$m] = collect($services)->sum(fn (Service $s) => $monthlyByService[$s->id][$m]);
        }

        $cumulativeTotals = [];
        $running = 0.0;
        for ($m = 1; $m <= 12; $m++) {
            $running += $monthlyTotals[$m];
            $cumulativeTotals[$m] = $running;
        }

        // Sasaran diambil per bulan supaya bulan bermusim dihormati.
        $targetByMonth = [];
        for ($m = 1; $m <= 12; $m++) {
            $targetByMonth[$m] = $services->sum(fn (Service $s) => $s->targetForMonth($this->year, $m)) * $yearFactor;
        }

        $selectedMonthTarget = $targetByMonth[$this->month];
        $cumulativeTargetToMonth = collect(range(1, $this->month))->sum(fn (int $m) => $targetByMonth[$m]);
        $fullYearTarget = collect($targetByMonth)->sum();

        // ── Nilai bulan/mod dipilih ──
        //
        // Mod tahunan menggunakan sasaran SETAHUN PENUH — nombor yang sama
        // seperti yang ditetapkan Admin. Sasaran terkumpul dipapar berasingan
        // sebagai penanda RENTAK, supaya "berapa sasaran saya" dan "sepatutnya
        // di mana saya sekarang" tidak dicampur menjadi satu angka.
        $monthActual = $mode->isYearly() ? $cumulativeTotals[$this->month] : $monthlyTotals[$this->month];
        $monthTarget = $mode->isYearly() ? $fullYearTarget : $selectedMonthTarget;

        // Keputusan D3 — pengganda period benar-benar dipakai.
        $displayActual = $metrics->toPeriodUnit($monthActual, $periodMode);
        $displayTarget = $metrics->toPeriodUnit($monthTarget, $periodMode);

        $overallPct = $displayTarget > 0 ? $displayActual / $displayTarget * 100 : 0.0;

        // Rentak: dibandingkan dengan sasaran sepatutnya setakat bulan ini.
        $paceTargetOverall = $mode->isYearly()
            ? $metrics->toPeriodUnit($cumulativeTargetToMonth, $periodMode)
            : $displayTarget;
        $pacePctOverall = $paceTargetOverall > 0 ? $displayActual / $paceTargetOverall * 100 : 0.0;

        $changeVsTarget = $metrics->percentChange($displayActual, $paceTargetOverall);

        // ── Tier index ──
        $currentTier = $metrics->calculateTierIndex($tiers, $monthActual, $mode);
        $tierMultiplier = $mode->tierMultiplier();

        $tiersView = $tiers->sortByDesc('sort_order')->values()->map(fn (IndexTier $tier) => [
            'key' => $tier->key,
            'name' => $tier->name,
            'color' => $tier->color_token,
            'isCurrent' => $tier->id === $currentTier->id,
            'widthPct' => $metrics->calculateTierWidthPct($tier->sort_order).'%',
            'rowBg' => $tier->id === $currentTier->id
                ? "color-mix(in oklch, {$tier->color_token} 12%, transparent)"
                : 'transparent',
            'monthlyRevenue' => $metrics->formatRm($tier->revenueFor(1)),
            'monthlyProfit' => $metrics->formatRm($tier->profitFor(1)),
            'quarterlyRevenue' => $metrics->formatRm($tier->revenueFor(3)),
            'quarterlyProfit' => $metrics->formatRm($tier->profitFor(3)),
            'yearlyRevenue' => $metrics->formatRm($tier->revenueFor(12)),
            'yearlyProfit' => $metrics->formatRm($tier->profitFor(12)),
        ]);

        // ── Baris jadual servis ──
        $serviceRows = $services->map(function (Service $service) use ($metrics, $mode, $monthlyByService, $yearFactor) {
            if ($mode->isYearly()) {
                $actual = collect(range(1, $this->month))
                    ->sum(fn (int $m) => $monthlyByService[$service->id][$m]);
                $target = $service->targetForYear($this->year) * $yearFactor;
                $paceTarget = $service->cumulativeTargetTo($this->year, $this->month) * $yearFactor;
            } else {
                $actual = $monthlyByService[$service->id][$this->month];
                $target = $service->targetForMonth($this->year, $this->month) * $yearFactor;
                $paceTarget = $target;
            }

            $pct = $target > 0 ? $actual / $target * 100 : 0.0;

            // Status dinilai terhadap RENTAK, bukan sasaran setahun penuh.
            // Servis yang mencapai 100% sasaran Januari–Ogos adalah sihat,
            // walaupun ia baru 67% daripada sasaran setahun.
            $pacePct = $paceTarget > 0 ? $actual / $paceTarget * 100 : 0.0;
            $status = $metrics->calculateServiceStatus($pacePct);

            return [
                'service' => $service,
                'name' => $service->name,
                'icon' => $service->icon_class,
                'key' => $service->key,
                'actual' => $actual,
                'salesLabel' => $metrics->formatRm($actual),
                'targetLabel' => $metrics->formatRm($target),
                'pct' => $pct,
                'pacePct' => $pacePct,
                'paceTargetLabel' => $metrics->formatRm($paceTarget),
                'onPace' => $actual >= $paceTarget,
                'status' => $status,
                'statusLabel' => $status->label(),
                'statusColor' => $status->color(),
                'barColor' => $status->barColor(),
            ];
        });

        // ── Kad ringkasan (agregat merentasi semua servis) ──
        $collectionKeys = ['sales_collection_new', 'sales_collection_progress'];
        $summary = [
            'collection' => $this->summaryFor($metrics, $collectionKeys, 'currency'),
            'quotation' => $this->summaryFor($metrics, ['amount_quotation_release'], 'currency'),
            'leads' => $this->summaryFor($metrics, ['no_of_lead'], 'number'),
        ];

        // ── Carta ──
        $monthLabels = __('calendar.months_short');

        $chartActuals = [];
        $chartTargets = [];
        for ($m = 1; $m <= 12; $m++) {
            $chartActuals[] = $monthlyTotals[$m] > 0 ? $monthlyTotals[$m] : null;
            $chartTargets[] = $targetByMonth[$m];
        }

        $dashboardChart = $metrics->buildChart($monthLabels, $chartActuals, $chartTargets);

        $baseMonthlySales = collect($monthlyTotals)->filter()->avg() ?: 0.0;
        $yearlyChart = $metrics->buildYearlyChart(
            $baseMonthlySales / max(0.0001, $yearFactor),
            $fullYearTarget / max(0.0001, $yearFactor) / 12,
            range(2023, 2032)
        );

        // Carta bertindan — 12 bulan, indeks 0-based untuk buildStackedBars()
        $stackedInput = [];
        foreach ($services as $service) {
            foreach (range(1, 12) as $m) {
                $stackedInput[$service->id][$m - 1] = $monthlyByService[$service->id][$m];
            }
        }
        $stackBars = $metrics->buildStackedBars($services, $stackedInput, $monthLabels);

        $roadmap = app(RoadmapService::class)->build($this->roadmapYear ?? $this->year);

        return view('livewire.dashboard.overview', [
            'roadmap' => $roadmap,
            'services' => $services,
            'serviceRows' => $serviceRows,
            'tiersView' => $tiersView,
            'currentTier' => $currentTier,
            'summary' => $summary,
            'monthLabels' => $monthLabels,
            'monthsFull' => __('calendar.months_full'),
            'displayActual' => $displayActual,
            'displayTarget' => $displayTarget,
            'overallPct' => $overallPct,
            'changeVsTarget' => $changeVsTarget,
            'estProfit' => $metrics->calculateEstimatedProfit($monthActual),
            'fullYearTarget' => $fullYearTarget,
            'cumulativeTargetToMonth' => $cumulativeTargetToMonth,
            'paceTargetOverall' => $paceTargetOverall,
            'pacePctOverall' => $pacePctOverall,
            'dashboardChart' => $dashboardChart,
            'yearlyChart' => $yearlyChart,
            'stackBars' => $stackBars,
            'stackLegend' => $services->map(fn (Service $s) => ['name' => $s->name, 'color' => $s->chart_color])->all(),
            /*
             * Dikira daripada data sebenar, bukan daripada jadual yang
             * disemai. Senarai statik kekal betul selama seminggu dan
             * salah selepas itu — dan senarai keutamaan yang salah lebih
             * teruk daripada tiada senarai, kerana orang bertindak
             * mengikutnya.
             */
            'priorities' => app(WeeklyPriorityService::class)->build($this->year, $this->month),
            'mode' => $mode,
            'periodMode' => $periodMode,
            'metrics' => $metrics,
            'years' => range(2023, 2032),
        ])->layoutData([
            'pageTitle' => __('dashboard.page_title'),
            'pageSubtitle' => __('dashboard.page_subtitle'),
        ]);
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    private function summaryFor(DashboardMetricsService $metrics, array $keys, string $format): array
    {
        $actual = $metrics->sumMetricActual($keys, $this->year, $this->month);
        $target = $metrics->sumMetricTarget($keys, $this->year);
        $change = $metrics->percentChange($actual, $target);

        return [
            'value' => $format === 'currency' ? $metrics->formatRm($actual) : $metrics->formatNumber($actual),
            'changeLabel' => $metrics->changeLabel($change),
            'changeColor' => $metrics->changeColor($change),
        ];
    }
}
