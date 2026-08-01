<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Service;
use App\Models\SheetIntegration;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Master List of Project.
 *
 * Paparan sahaja. Data dimasukkan dalam Google Sheet dan disegerakkan
 * oleh Admin — tiada tindakan pada skrin ini menulis ke pangkalan data.
 */
#[Layout('components.layouts.app')]
class ProjectList extends Component
{
    use WithPagination;

    #[Url(as: 'servis', except: '')]
    public ?string $serviceKey = null;

    #[Url(as: 'cari', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: '')]
    public string $status = '';

    #[Url(as: 'baris', except: 25)]
    public int $perPage = 25;

    public string $sortField = 'project_date';

    public string $sortDirection = 'desc';

    /** Menukar penapis mesti kembali ke halaman 1. */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedServiceKey(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function selectService(?string $key): void
    {
        $this->serviceKey = $key;
        $this->resetPage();
    }

    public function render(): View
    {
        $services = Service::orderBy('sort_order')->get();
        $selected = $this->serviceKey ? $services->firstWhere('key', $this->serviceKey) : null;

        /*
         * Kiraan petak dikira daripada SELURUH jadual, bukan halaman
         * semasa. Petak yang berubah semasa menatal halaman tidak boleh
         * dipercayai sebagai jumlah.
         */
        $countByService = Project::query()
            ->selectRaw('service_id, count(*) as jumlah')
            ->groupBy('service_id')
            ->pluck('jumlah', 'service_id');

        $total = (int) $countByService->sum();

        $closed = Project::whereIn('status', [
            ProjectStatus::Completed->value,
            ProjectStatus::Closed->value,
        ])->count();

        $allowedSorts = [
            'code', 'project_date', 'client_name', 'pic_sales',
            'contract_amount', 'variation_order', 'status',
        ];

        $projects = Project::query()
            ->with('service')
            ->forService($selected?->id)
            ->withStatus($this->status ?: null)
            ->search($this->search)
            ->orderBy(
                in_array($this->sortField, $allowedSorts, true) ? $this->sortField : 'project_date',
                $this->sortDirection === 'asc' ? 'asc' : 'desc'
            )
            ->paginate(max(10, min(100, $this->perPage)));

        return view('livewire.dashboard.project-list', [
            'services' => $services,
            'selectedService' => $selected,
            'projects' => $projects,
            'countByService' => $countByService,
            'totalProjects' => $total,
            'closedProjects' => $closed,
            'statuses' => ProjectStatus::cases(),
            'sheet' => SheetIntegration::where('kind', 'project')->first(),
        ])->layoutData([
            'pageTitle' => __('project.page_title'),
            'pageSubtitle' => __('project.page_subtitle'),
        ]);
    }
}
