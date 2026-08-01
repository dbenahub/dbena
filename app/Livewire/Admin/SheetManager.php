<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Exceptions\SheetReadException;
use App\Jobs\SyncSheetJob;
use App\Models\Service;
use App\Models\SheetIntegration;
use App\Models\SheetSyncLog;
use App\Services\AuditLogger;
use App\Services\Sheets\SheetSyncService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Pengurus Integrasi Google Sheet.
 *
 * Direka untuk sheet SEDIA ADA: admin menampal pautan, sistem membaca
 * header, mencadangkan pemetaan lajur, dan memaparkan pratonton langsung
 * baris mana yang akan padan sebelum sebarang data ditulis.
 */
#[Layout('components.layouts.app')]
class SheetManager extends Component
{
    public ?int $selectedServiceId = null;

    public string $url = '';
    public string $tabName = '';
    public int $headerRow = 1;
    public string $matchMode = 'label';
    public string $layoutMode = 'multi';
    public bool $importTargets = false;
    public bool $syncEnabled = false;

    /** @var array<string, string> */
    public array $columnMap = [
        'metric' => '', 'week1' => '', 'week2' => '', 'week3' => '', 'week4' => '',
        'data_type' => '', 'target' => '', 'owner' => '', 'action_plan' => '',
    ];

    public int $year;
    public int $month;

    /** @var array<string, mixed>|null */
    public ?array $preview = null;

    public ?string $previewError = null;
    public bool $showAppsScript = false;

    public function mount(): void
    {
        $this->authorize('manage-sheet-integration');

        $this->year = (int) now()->year;
        $this->month = (int) now()->month;

        // NULL = satu sheet memegang SEMUA servis (susun atur DBENA sebenar).
        $this->selectedServiceId = null;
        $this->loadIntegration();
    }

    /** Simpan serta-merta apabila suis sync ditukar, supaya UI tidak menipu. */
    public function updatedSyncEnabled(): void
    {
        $this->saveConfig(app(AuditLogger::class));
    }

    public function updatedSelectedServiceId(): void
    {
        $this->preview = null;
        $this->previewError = null;
        $this->loadIntegration();
    }

    private function loadIntegration(): void
    {
        $integration = $this->integration();

        $this->url = (string) $integration->url;
        $this->tabName = (string) $integration->tab_name;
        $this->headerRow = (int) ($integration->header_row ?: 1);
        $this->matchMode = $integration->match_mode ?? 'label';
        $this->layoutMode = $integration->layout_mode ?? 'multi';
        $this->importTargets = (bool) $integration->import_targets;
        $this->syncEnabled = (bool) $integration->sync_enabled;

        $map = $integration->column_map ?? [];

        foreach (array_keys($this->columnMap) as $field) {
            $this->columnMap[$field] = (string) ($map[$field] ?? '');
        }
    }

    private function integration(): SheetIntegration
    {
        return SheetIntegration::firstOrCreate(['service_id' => $this->selectedServiceId]);
    }

    /** Simpan konfigurasi tanpa menyentuh data metrik. */
    public function saveConfig(AuditLogger $audit): SheetIntegration
    {
        $this->authorize('manage-sheet-integration');

        $integration = $this->integration();
        $old = $integration->only(['url', 'tab_name', 'header_row', 'sync_enabled']);

        $integration->fill([
            'url' => trim($this->url) ?: null,
            'spreadsheet_id' => SheetIntegration::extractSpreadsheetId($this->url),
            'gid' => SheetIntegration::extractGid($this->url),
            'tab_name' => trim($this->tabName) ?: null,
            'header_row' => max(0, $this->headerRow),
            'match_mode' => $this->matchMode,
            'layout_mode' => $this->layoutMode,
            'import_targets' => $this->importTargets,
            'sync_enabled' => $this->syncEnabled,
            'column_map' => array_filter($this->columnMap, fn (string $v) => trim($v) !== ''),
            'updated_by' => auth()->id(),
        ])->save();

        $audit->record(
            'sheet.updated',
            $integration,
            $old,
            $integration->only(['url', 'tab_name', 'header_row', 'sync_enabled']),
            $integration->service?->name ?? 'global'
        );

        return $integration;
    }

    public function save(AuditLogger $audit): void
    {
        $this->saveConfig($audit);
        $this->dispatch('dbena-toast', message: __('sheets.saved'));
    }

    /**
     * Ambil sheet dan papar apa yang akan berlaku — tanpa menulis apa-apa.
     */
    public function loadPreview(AuditLogger $audit, SheetSyncService $sync): void
    {
        $this->authorize('manage-sheet-integration');

        $integration = $this->saveConfig($audit);
        $this->previewError = null;

        if (blank($integration->spreadsheet_id)) {
            $this->previewError = __('sheets.error.bad_url');
            $this->preview = null;

            return;
        }

        try {
            $this->preview = $sync->preview($integration);
        } catch (SheetReadException $e) {
            $this->previewError = $e->getMessage();
            $this->preview = null;

            return;
        }

        // Isi pemetaan yang belum ditetapkan dengan cadangan automatik.
        $applied = 0;

        foreach (($this->preview['suggestions'] ?? []) as $field => $letter) {
            if (trim($this->columnMap[$field] ?? '') === '') {
                $this->columnMap[$field] = $letter;
                $applied++;
            }
        }

        if ($applied > 0) {
            $this->saveConfig($audit);
            $this->preview = $sync->preview($integration->fresh());
            $this->dispatch('dbena-toast', message: __('sheets.auto_mapped', ['count' => $applied]));
        }
    }

    /** Jalankan sync sebenar sekarang. */
    public function syncNow(AuditLogger $audit, SheetSyncService $sync): void
    {
        $this->authorize('manage-sheet-integration');

        $integration = $this->saveConfig($audit);

        if ($problem = $integration->readinessProblem()) {
            $this->dispatch('dbena-toast',
                message: __('sheets.not_ready.'.$problem, [
                    'fields' => implode(', ', $integration->missingMappings()),
                ]),
                variant: 'error');

            return;
        }

        $result = $sync->sync($integration, $this->year, $this->month, 'manual', auth()->id());

        $this->dispatch('dbena-toast',
            message: $result['message'],
            variant: $result['status'] === 'failed' ? 'error' : 'success');
    }

    /** Baris gilirkan sync untuk SEMUA servis yang dikonfigurasi. */
    public function syncAll(): void
    {
        $this->authorize('manage-sheet-integration');

        $queued = 0;

        foreach (SheetIntegration::where('sync_enabled', true)->get() as $integration) {
            if ($integration->isReadyToSync()) {
                SyncSheetJob::dispatch($integration->id, $this->year, $this->month, 'manual', auth()->id());
                $queued++;
            }
        }

        $this->dispatch('dbena-toast', message: __('sheets.queued_all', ['count' => $queued]));
    }

    public function regenerateSecret(): void
    {
        $this->authorize('manage-sheet-integration');

        $integration = $this->integration();
        $integration->forceFill(['webhook_secret' => null])->save();
        $integration->ensureWebhookSecret();

        $this->dispatch('dbena-toast', message: __('sheets.secret_regenerated'));
    }

    /** Skrip yang admin salin ke Extensions → Apps Script dalam sheet mereka. */
    public function appsScript(): string
    {
        $url = $this->integration()->webhookUrl();

        return <<<JS
        /**
         * DBENA Dashboard — penyegerak automatik
         * Tampal dalam: Extensions → Apps Script, kemudian Run → installTrigger (sekali sahaja).
         */
        const DBENA_WEBHOOK = '{$url}';

        function notifyDbena() {
          const now = new Date();
          UrlFetchApp.fetch(DBENA_WEBHOOK, {
            method: 'post',
            contentType: 'application/json',
            payload: JSON.stringify({ year: now.getFullYear(), month: now.getMonth() + 1 }),
            muteHttpExceptions: true,
          });
        }

        function installTrigger() {
          ScriptApp.getProjectTriggers().forEach(function (t) {
            if (t.getHandlerFunction() === 'notifyDbena') ScriptApp.deleteTrigger(t);
          });
          ScriptApp.newTrigger('notifyDbena')
            .forSpreadsheet(SpreadsheetApp.getActive())
            .onEdit()
            .create();
        }
        JS;
    }

    public function render(): View
    {
        $integration = $this->integration();

        return view('livewire.admin.sheet-manager', [
            'services' => Service::orderBy('sort_order')->get(),
            'integration' => $integration,
            'logs' => SheetSyncLog::with('triggeredBy')
                ->where('sheet_integration_id', $integration->id)
                ->latest('created_at')
                ->limit(15)
                ->get(),
            'months' => __('calendar.months_full'),
            'years' => range(2023, 2032),
            'driverLabel' => app(\App\Contracts\SheetReader::class)->label(),
        ])->layoutData([
            'pageTitle' => __('sheets.page_title'),
            'pageSubtitle' => __('sheets.page_subtitle'),
        ]);
    }
}
