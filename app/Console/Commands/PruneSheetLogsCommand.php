<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SheetSyncLog;
use Illuminate\Console\Command;

class PruneSheetLogsCommand extends Command
{
    protected $signature = 'dbena:prune-sheet-logs';

    protected $description = 'Buang log sync sheet yang melebihi tempoh simpanan';

    public function handle(): int
    {
        $days = (int) config('dbena.sheets.log_retention_days');
        $deleted = SheetSyncLog::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("{$deleted} log sync dibuang (lebih {$days} hari).");

        return self::SUCCESS;
    }
}
