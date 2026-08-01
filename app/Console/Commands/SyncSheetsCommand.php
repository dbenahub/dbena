<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncSheetJob;
use App\Models\SheetIntegration;
use Illuminate\Console\Command;

class SyncSheetsCommand extends Command
{
    protected $signature = 'dbena:sync-sheets
        {--service= : Hadkan kepada satu kunci servis (cth. renovation)}
        {--year= : Tahun sasaran (lalai: tahun semasa)}
        {--month= : Bulan sasaran 1-12 (lalai: bulan semasa)}
        {--sync : Jalankan serta-merta dan bukan melalui baris gilir}';

    protected $description = 'Tarik Data Kritikal Mingguan dari Google Sheet yang disambungkan';

    public function handle(): int
    {
        $year = (int) ($this->option('year') ?: now()->year);
        $month = (int) ($this->option('month') ?: now()->month);

        $integrations = SheetIntegration::query()
            ->critical()
            ->where('sync_enabled', true)
            ->with('service')
            ->when($this->option('service'), fn ($q, $key) => $q->whereHas(
                'service', fn ($s) => $s->where('key', $key)
            ))
            ->get()
            ->filter(fn (SheetIntegration $i) => $i->isReadyToSync());

        if ($integrations->isEmpty()) {
            $this->warn('Tiada integrasi sheet yang sedia untuk disegerak.');

            return self::SUCCESS;
        }

        foreach ($integrations as $integration) {
            $name = $integration->service?->name ?? __('sheets.global_sheet');
            $job = new SyncSheetJob($integration->id, $year, $month, 'schedule');

            if ($this->option('sync')) {
                dispatch_sync($job);
                $fresh = $integration->fresh();
                $this->line(sprintf(
                    '  %s  %s — %s',
                    $fresh->last_sync_status === 'success' ? '<fg=green>✓</>' : '<fg=yellow>!</>',
                    $name,
                    $fresh->last_sync_message
                ));
            } else {
                dispatch($job);
                $this->line("  → {$name} dibaris gilirkan");
            }
        }

        $this->info(sprintf('%d integrasi diproses untuk %02d/%d.', $integrations->count(), $month, $year));

        return self::SUCCESS;
    }
}
