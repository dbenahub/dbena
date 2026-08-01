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

    /**
     * Kosongkan penapis tetapi KEKALKAN kategori yang dipilih.
     *
     * Kategori ialah tempat pengguna berada, bukan penapis yang mereka
     * kenakan. Menghantar mereka kembali ke "Semua" kerana mereka
     * mengosongkan carian bermakna kehilangan tempat.
     */
    public function clearFilters(): void
    {
        $this->status = '';
        $this->search = '';
        $this->resetPage();
    }

    public function selectService(?string $key): void
    {
        $this->serviceKey = $key;
        $this->resetPage();
    }

    /**
     * Status yang benar-benar wujud dalam data, bukan setiap kes enum.
     *
     * Enum mengetahui enam status kerana ia mesti menerima apa sahaja yang
     * mungkin ditaip dalam sheet. Sheet DBENA sebenarnya menggunakan tiga.
     * Menyenaraikan kesemua enam memberi pengguna tiga pilihan yang
     * sentiasa memulangkan senarai kosong — dan senarai kosong kelihatan
     * seperti penapis yang rosak, bukan seperti data yang tiada.
     *
     * Kiraan disertakan supaya pengguna tahu berapa banyak sebelum menapis.
     *
     * @return array<int, array{status: ProjectStatus, count: int}>
     */
    private function statusesInUse(): array
    {
        $counts = Project::query()
            ->selectRaw('status, count(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $used = [];

        // Diulang mengikut urutan enum, bukan urutan pangkalan data —
        // corong jualan mempunyai susunan semula jadi dan senarai turun
        // sepatutnya mengikutnya.
        foreach (ProjectStatus::cases() as $case) {
            $jumlah = (int) ($counts[$case->value] ?? 0);

            if ($jumlah > 0 || $this->status === $case->value) {
                $used[] = ['status' => $case, 'count' => $jumlah];
            }
        }

        return $used;
    }

    public function render(): View
    {
        $services = Service::orderBy('sort_order')->get();
        $selected = $this->serviceKey ? $services->firstWhere('key', $this->serviceKey) : null;

        /*
         * Kiraan petak mengikut penapis status dan carian, TETAPI bukan
         * kategori yang dipilih. Petak itu ialah pecahan mengikut kategori
         * — menapisnya mengikut kategori akan mengosongkan lima daripada
         * enam petak dan memusnahkan perbandingan.
         *
         * Memilih "Quotation" sepatutnya menjawab soalan sebenar pemilik:
         * berapa banyak sebut harga tergantung dalam SETIAP kategori.
         * Petak yang kekal pada 107 semasa jadual menunjukkan 62
         * bercanggah dengan jadual di bawahnya.
         *
         * Dikira daripada seluruh set yang ditapis, bukan halaman semasa.
         * Petak yang berubah semasa menatal halaman tidak boleh dipercayai
         * sebagai jumlah.
         */
        $tileScope = fn () => Project::query()
            ->withStatus($this->status ?: null)
            ->search($this->search);

        $countByService = $tileScope()
            ->selectRaw('service_id, count(*) as jumlah')
            ->groupBy('service_id')
            ->pluck('jumlah', 'service_id');

        $total = (int) $countByService->sum();

        $closed = $tileScope()->whereIn('status', [
            ProjectStatus::Completed->value,
            ProjectStatus::Closed->value,
        ])->count();

        // Jumlah keseluruhan yang tidak ditapis kekal kelihatan supaya
        // "36" mempunyai penyebut. Tanpanya nombor yang mengecil kelihatan
        // seperti data yang hilang.
        $grandByService = Project::query()
            ->selectRaw('service_id, count(*) as jumlah')
            ->groupBy('service_id')
            ->pluck('jumlah', 'service_id');

        $grandTotal = (int) $grandByService->sum();

        $activeStatus = $this->status !== ''
            ? ProjectStatus::tryFrom($this->status)
            : null;

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
            'grandTotal' => $grandTotal,
            'grandByService' => $grandByService,
            'activeStatus' => $activeStatus,
            'isFiltered' => $this->status !== '' || $this->search !== '',
            'statuses' => $this->statusesInUse(),
            'sheet' => SheetIntegration::projects()->first(),
        ])->layoutData([
            'pageTitle' => __('project.page_title'),
            'pageSubtitle' => __('project.page_subtitle'),
        ]);
    }
}
