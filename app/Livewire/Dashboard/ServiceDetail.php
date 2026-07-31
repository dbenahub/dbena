<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Enums\OwnerStatus;
use App\Models\CriticalMetric;
use App\Models\CriticalMetricMonth;
use App\Models\CriticalWeeklyEntry;
use App\Models\Owner;
use App\Models\Service;
use App\Models\SheetIntegration;
use App\Services\AuditLogger;
use App\Services\CriticalDataService;
use App\Services\DashboardMetricsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Detail Servis — port seksyen `isService` prototaip, dengan ciri-ciri
 * "dead code" yang dibina semula (keputusan D2):
 *   • Jadual Projek
 *   • Modal Sambung Google Sheet
 *   • Modal Raw Data (property BERASINGAN — betulkan isu #19)
 *   • Modal Tambah PIC (dengan alur kelulusan — betulkan isu #11)
 */
#[Layout('components.layouts.app')]
class ServiceDetail extends Component
{
    public string $key;

    #[Url(as: 'tahun')]
    public int $year;

    #[Url(as: 'bulan')]
    public int $month;

    #[Url(as: 'pemilik')]
    public ?int $ownerFilter = null;

    /** Nilai borang: [metricId][weekNumber] => string */
    public array $weekValues = [];

    /** [metricId] => ownerId */
    public array $rowOwners = [];

    /** [metricId] => teks */
    public array $rowPlans = [];

    // Modal (setiap satu property BEBAS — tiada sarang seperti prototaip)
    public bool $showSheetModal = false;
    public bool $showRawDataModal = false;
    public bool $showAddOwnerModal = false;

    public string $sheetUrl = '';
    public string $newOwnerName = '';

    public function mount(string $key): void
    {
        $this->key = $key;
        $this->year = (int) now()->year;
        $this->month = (int) now()->month;

        abort_unless(Service::where('key', $key)->exists(), 404);

        $this->loadFormState();
        $this->sheetUrl = (string) ($this->service()->sheetIntegration?->url ?? SheetIntegration::global()->url ?? '');
    }

    private function service(): Service
    {
        return Service::where('key', $this->key)->with('sheetIntegration')->firstOrFail();
    }

    public function selectMonth(int $month): void
    {
        $this->month = max(1, min(12, $month));
        $this->loadFormState();
    }

    public function selectYear(int $year): void
    {
        $this->year = $year;
        $this->loadFormState();
    }

    /** Muatkan nilai sedia ada dari DB ke dalam state borang. */
    private function loadFormState(): void
    {
        $this->weekValues = [];
        $this->rowOwners = [];
        $this->rowPlans = [];

        $metrics = $this->service()->criticalMetrics()
            ->with([
                'weeklyEntries' => fn ($q) => $q->where('year', $this->year)->where('month', $this->month),
                'months' => fn ($q) => $q->where('year', $this->year)->where('month', $this->month),
                'defaultOwner',
            ])->get();

        foreach ($metrics as $metric) {
            for ($week = 1; $week <= 4; $week++) {
                $entry = $metric->weeklyEntries->firstWhere('week_number', $week);
                $this->weekValues[$metric->id][$week] = $entry?->value !== null
                    ? (string) (float) $entry->value
                    : '';
            }

            $monthMeta = $metric->months->first();
            $this->rowOwners[$metric->id] = $monthMeta?->owner_id ?? $metric->default_owner_id;
            $this->rowPlans[$metric->id] = $monthMeta?->action_plan ?? '';
        }
    }

    // ── Menyimpan Data Kritikal ───────────────────────────────────────────

    public function saveWeekValue(int $metricId, int $week): void
    {
        $this->authorize('updateWeeklyValue', CriticalMetric::class);

        $metric = CriticalMetric::findOrFail($metricId);
        abort_unless($metric->service_id === $this->service()->id, 403);

        $raw = trim((string) ($this->weekValues[$metricId][$week] ?? ''));
        $value = $raw === '' ? null : (float) preg_replace('/[^0-9.\-]/', '', $raw);

        CriticalWeeklyEntry::updateOrCreate(
            [
                'critical_metric_id' => $metricId,
                'year' => $this->year,
                'month' => $this->month,
                'week_number' => $week,
            ],
            ['value' => $value, 'updated_by' => auth()->id()]
        );

        $this->dispatch('dbena-toast', message: __('service.saved'));
    }

    public function saveRowOwner(int $metricId): void
    {
        $this->authorize('updateMeta', CriticalMetric::class);

        $metric = CriticalMetric::findOrFail($metricId);
        abort_unless($metric->service_id === $this->service()->id, 403);

        CriticalMetricMonth::updateOrCreate(
            ['critical_metric_id' => $metricId, 'year' => $this->year, 'month' => $this->month],
            ['owner_id' => $this->rowOwners[$metricId] ?: null, 'updated_by' => auth()->id()]
        );

        $this->dispatch('dbena-toast', message: __('service.saved'));
    }

    public function saveRowPlan(int $metricId): void
    {
        $this->authorize('updateMeta', CriticalMetric::class);

        $metric = CriticalMetric::findOrFail($metricId);
        abort_unless($metric->service_id === $this->service()->id, 403);

        CriticalMetricMonth::updateOrCreate(
            ['critical_metric_id' => $metricId, 'year' => $this->year, 'month' => $this->month],
            ['action_plan' => $this->rowPlans[$metricId] ?: null, 'updated_by' => auth()->id()]
        );

        $this->dispatch('dbena-toast', message: __('service.saved'));
    }

    /**
     * GUARD KRITIKAL — hanya Admin boleh mengubah SASARAN.
     *
     * Policy disemak di sini, bukan hanya di Blade. Memanggil kaedah ini
     * secara langsung (devtools / network tab) sebagai role `user` akan
     * menghasilkan 403.
     */
    public function updateTarget(int $metricId, string $value, AuditLogger $audit): void
    {
        $this->authorize('updateTarget', CriticalMetric::class);

        $metric = CriticalMetric::findOrFail($metricId);
        abort_unless($metric->service_id === $this->service()->id, 403);

        $target = $metric->targets()->firstOrCreate(['year' => $this->year]);
        $old = $target->monthly_target;
        $new = $value === '' ? null : (float) preg_replace('/[^0-9.]/', '', $value);

        $target->update(['monthly_target' => $new]);

        $audit->record(
            'target.updated',
            $target,
            ['monthly_target' => $old],
            ['monthly_target' => $new],
            $metric->label
        );

        $this->dispatch('dbena-toast', message: __('service.saved'));
    }

    // ── Penapis PIC ───────────────────────────────────────────────────────

    public function toggleOwnerFilter(int $ownerId): void
    {
        $this->ownerFilter = $this->ownerFilter === $ownerId ? null : $ownerId;
    }

    public function clearOwnerFilter(): void
    {
        $this->ownerFilter = null;
    }

    // ── Modal Tambah PIC (keputusan D2, betulkan isu #11) ─────────────────

    public function addOwner(AuditLogger $audit): void
    {
        $this->authorize('create', Owner::class);

        $name = Str::upper(trim($this->newOwnerName));

        if ($name === '') {
            $this->dispatch('dbena-toast', message: __('service.owner_name_required'), variant: 'error');

            return;
        }

        if (Owner::where('name', $name)->exists()) {
            $this->dispatch('dbena-toast', message: __('service.owner_exists'), variant: 'error');

            return;
        }

        $isAdmin = auth()->user()->isAdmin();

        $owner = Owner::create([
            'name' => $name,
            'color_token' => Owner::nextColor(),
            'is_core' => false,
            'is_system' => false,
            // User biasa hanya boleh MENCADANG; Admin meluluskan.
            'status' => $isAdmin ? OwnerStatus::Active : OwnerStatus::PendingApproval,
            'created_by' => auth()->id(),
            'approved_by' => $isAdmin ? auth()->id() : null,
            'approved_at' => $isAdmin ? now() : null,
        ]);

        $audit->log('owner.created', $owner, $name, ['status' => $owner->status->value]);

        $this->newOwnerName = '';
        $this->showAddOwnerModal = false;

        $this->dispatch('dbena-toast', message: $isAdmin
            ? __('service.owner_added', ['name' => $name])
            : __('service.owner_pending', ['name' => $name]));
    }

    // ── Modal Google Sheet (keputusan D2) ─────────────────────────────────

    public function connectSheet(AuditLogger $audit): void
    {
        $this->authorize('manage-sheet-integration');

        if (trim($this->sheetUrl) === '') {
            $this->dispatch('dbena-toast', message: __('service.sheet_no_url'), variant: 'error');

            return;
        }

        $integration = SheetIntegration::updateOrCreate(
            ['service_id' => $this->service()->id],
            [
                'url' => $this->sheetUrl,
                'connected' => true,
                'last_synced_at' => now(),
                'updated_by' => auth()->id(),
            ]
        );

        $audit->log('sheet.updated', $integration, $this->service()->name, ['connected' => true]);

        $this->dispatch('dbena-toast', message: __('service.sheet_connected_toast'));
    }

    public function syncSheet(): void
    {
        $this->authorize('manage-sheet-integration');

        // Fasa 1: hanya kemas kini penanda masa. Sync sebenar = Fasa 2.
        SheetIntegration::where('service_id', $this->service()->id)
            ->update(['last_synced_at' => now()]);

        $this->dispatch('dbena-toast', message: __('service.sheet_synced_toast'));
    }

    public function disconnectSheet(AuditLogger $audit): void
    {
        $this->authorize('manage-sheet-integration');

        $integration = SheetIntegration::where('service_id', $this->service()->id)->first();

        $integration?->update(['connected' => false, 'last_synced_at' => null]);

        if ($integration) {
            $audit->log('sheet.updated', $integration, $this->service()->name, ['connected' => false]);
        }

        $this->showSheetModal = false;
        $this->dispatch('dbena-toast', message: __('service.sheet_disconnected_toast'));
    }

    // ── Render ────────────────────────────────────────────────────────────

    public function render(
        DashboardMetricsService $metrics,
        CriticalDataService $critical
    ): View {
        $service = $this->service();

        $rows = $critical->rowsFor($service, $this->year, $this->month);
        $ownerPerformance = $critical->ownerPerformance($rows);

        $displayRows = $this->ownerFilter
            ? $rows->filter(fn (array $r) => $r['ownerId'] === $this->ownerFilter)->values()
            : $rows;

        // ── Kad ringkasan ──
        $revenueRow = $rows->firstWhere('metricKey', 'revenue_sales');
        $actual = (float) ($revenueRow['actual'] ?? 0);
        $target = (float) ($revenueRow['target'] ?? $service->monthly_target);
        $pct = $target > 0 ? min(999, $actual / $target * 100) : 0.0;

        // ── Kad Actual vs Sasaran ──
        $quotationRow = $rows->firstWhere('metricKey', 'no_of_new_quotation');
        $siteVisitRow = $rows->first(fn (array $r) => in_array($r['metricKey'], ['no_of_site_visit', 'no_of_appointment'], true));
        $amountRow = $rows->firstWhere('metricKey', 'amount_quotation_release');

        $siteVisitLabel = ($siteVisitRow['metricKey'] ?? '') === 'no_of_appointment'
            ? __('service.appointment')
            : __('service.site_visit');

        // ── Carta trend bulanan ──
        $monthLabels = __('calendar.months_short');
        $chartActuals = [];
        $chartTargets = [];

        for ($m = 1; $m <= 12; $m++) {
            $value = $metrics->sumMetricActual(['revenue_sales'], $this->year, $m, $service->id);
            $chartActuals[] = $value > 0 ? $value : null;
            $chartTargets[] = (float) $service->monthly_target;
        }

        // ── Carta mingguan ──
        $weekLabels = collect(range(1, 4))->map(fn (int $n) => 'M'.$n)->all();

        $projects = $service->projects()->orderByDesc('project_date')->get();
        $projectCount = $projects->count();
        $gap = max(0, $target - $actual);

        return view('livewire.dashboard.service-detail', [
            'service' => $service,
            'rows' => $rows,
            'displayRows' => $displayRows,
            'ownerPerformance' => $ownerPerformance,
            'owners' => Owner::active()->orderBy('is_system')->orderBy('name')->get(),
            'actual' => $actual,
            'target' => $target,
            'pct' => $pct,
            'quotationCard' => $this->buildCard($quotationRow, $metrics),
            'siteVisitCard' => $this->buildCard($siteVisitRow, $metrics),
            'siteVisitLabel' => $siteVisitLabel,
            'weeklyBars' => [
                'amount' => $this->buildWeeklyBars($amountRow, $weekLabels, $metrics),
                'quotation' => $this->buildWeeklyBars($quotationRow, $weekLabels, $metrics),
                'siteVisit' => $this->buildWeeklyBars($siteVisitRow, $weekLabels, $metrics),
            ],
            'weeklyTargets' => [
                'amount' => $this->weeklyTargetLabel($amountRow, $metrics),
                'quotation' => $this->weeklyTargetLabel($quotationRow, $metrics),
                'siteVisit' => $this->weeklyTargetLabel($siteVisitRow, $metrics),
            ],
            'serviceChart' => $metrics->buildChart($monthLabels, $chartActuals, $chartTargets),
            'weekHeaders' => $metrics->getCriticalWeekLabels($this->month, $this->year),
            'monthLabels' => $monthLabels,
            'monthsFull' => __('calendar.months_full'),
            'projects' => $projects,
            'analysis' => [
                'gap' => $gap,
                'gapLabel' => $metrics->formatRm($gap),
                'avgProject' => $metrics->formatRm($metrics->calculateAvgProjectValue($actual, $projectCount)),
                'projectCount' => $projectCount,
                'runRate' => $metrics->formatRm($metrics->calculateRequiredRunRate($gap)),
                'monthsLeft' => $metrics->monthsLeftInFiscalYear(),
                'isGood' => $pct >= (float) config('dbena.service_status_threshold'),
            ],
            'priority' => $service->priorities()->active()->first(),
            'sheet' => SheetIntegration::firstWhere('service_id', $service->id),
            'canEditTarget' => auth()->user()->can('updateTarget', CriticalMetric::class),
            'rawDataJson' => $this->buildRawDataJson($rows),
            'metrics' => $metrics,
            'years' => range(2023, 2032),
        ])->layoutData([
            'pageTitle' => Str::upper($service->name),
            'pageSubtitle' => __('service.page_subtitle'),
        ]);
    }

    /** @return array<string, mixed>|null */
    private function buildCard(?array $row, DashboardMetricsService $metrics): ?array
    {
        // Divider tiada baris Site Visit — kad tidak dipaparkan langsung.
        if ($row === null || $row['target'] === null) {
            return null;
        }

        $actual = (float) ($row['actual'] ?? 0);
        $target = (float) $row['target'];
        $pct = $target > 0 ? min(100, $actual / $target * 100) : 0.0;

        return [
            'actualLabel' => $metrics->formatNumber($actual),
            'targetLabel' => $metrics->formatNumber($target),
            'pct' => $pct,
            'barColor' => $metrics->achievementBarColor($pct),
        ];
    }

    /**
     * @param  array<int, string>  $labels
     * @return array<int, array<string, mixed>>
     */
    private function buildWeeklyBars(?array $row, array $labels, DashboardMetricsService $metrics): array
    {
        if ($row === null) {
            return [];
        }

        $weeklyTarget = $metrics->calculateWeeklyTarget($row['target'], $row['valueType']);
        $bars = [];

        foreach ($labels as $index => $label) {
            $value = $row['weeks'][$index + 1] ?? null;
            $pct = ($weeklyTarget && $weeklyTarget > 0 && $value !== null)
                ? min(100, $value / $weeklyTarget * 100)
                : 0.0;

            $bars[] = [
                'label' => $label,
                'actualLabel' => $value === null ? '—' : $row['valueType']->format($value),
                'pctHeight' => $pct.'%',
                'barColor' => $metrics->achievementBarColor($pct, weekly: true),
            ];
        }

        return $bars;
    }

    private function weeklyTargetLabel(?array $row, DashboardMetricsService $metrics): ?string
    {
        if ($row === null || $row['target'] === null) {
            return null;
        }

        $weekly = $metrics->calculateWeeklyTarget($row['target'], $row['valueType']);

        return $weekly === null ? null : $row['valueType']->format($weekly);
    }

    /** JSON struktur data mentah untuk modal Raw Data (keputusan D2). */
    private function buildRawDataJson(Collection $rows): string
    {
        return json_encode(
            $rows->map(fn (array $r) => [
                'metric' => $r['metricKey'],
                'label' => $r['label'],
                'type' => $r['type']->value,
                'valueType' => $r['valueType']->value,
                'week1' => $r['weeks'][1],
                'week2' => $r['weeks'][2],
                'week3' => $r['weeks'][3],
                'week4' => $r['weeks'][4],
                'monthlyActual' => $r['actual'],
                'monthlyTarget' => $r['target'] ?? $r['targetLabel'],
                'status' => $r['status']->value,
                'owner' => $r['ownerName'],
                'actionPlan' => $r['actionPlan'] ?: null,
            ])->values(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) ?: '[]';
    }
}
