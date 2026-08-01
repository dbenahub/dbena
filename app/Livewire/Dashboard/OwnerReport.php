<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Enums\ReportPeriod;
use App\Models\Owner;
use App\Models\Service;
use App\Services\OwnerReportService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class OwnerReport extends Component
{
    #[Url(as: 'tempoh')]
    public string $period = 'monthly';

    #[Url(as: 'tahun')]
    public int $year;

    #[Url(as: 'bulan')]
    public int $month;

    #[Url(as: 'minggu')]
    public ?int $week = null;

    #[Url(as: 'servis')]
    public ?string $serviceKey = null;

    /** ID PIC yang kadnya sedang dikembangkan. */
    #[Url(as: 'pemilik', except: '')]
    public ?int $ownerId = null;

    public ?int $expandedOwner = null;

    public function mount(): void
    {
        $this->year = (int) now()->year;
        $this->month = (int) now()->month;
    }

    public function updatedPeriod(): void
    {
        // Minggu hanya bermakna dalam mod mingguan.
        $this->week = $this->period === 'weekly' ? ($this->week ?? 1) : null;
    }

    public function toggleOwner(int $ownerId): void
    {
        $this->expandedOwner = $this->expandedOwner === $ownerId ? null : $ownerId;
    }

    public function render(OwnerReportService $reports): View
    {
        $period = ReportPeriod::tryFrom($this->period) ?? ReportPeriod::Monthly;
        $service = $this->serviceKey ? Service::where('key', $this->serviceKey)->first() : null;

        $report = $reports->build(
            $period,
            $this->year,
            $this->month,
            $period->isWeekly() ? ($this->week ?? 1) : null,
            $service?->id,
            $this->ownerId,
        );

        return view('livewire.dashboard.owner-report', [
            'report' => $report,
            'periodEnum' => $period,
            'services' => Service::orderBy('sort_order')->get(),
            'selectedService' => $service,
            'ownerOptions' => Owner::scorable()->orderBy('name')->get(),
            'months' => __('calendar.months_full'),
            'years' => range(2023, 2032),
        ])->layoutData([
            'pageTitle' => __('owner_report.page_title'),
            'pageSubtitle' => __('owner_report.page_subtitle'),
        ]);
    }
}
