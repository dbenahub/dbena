<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SheetIntegration;
use App\Services\Sheets\SheetSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class SyncSheetJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly int $integrationId,
        public readonly int $year,
        public readonly int $month,
        public readonly string $trigger = 'schedule',
        public readonly ?int $userId = null,
    ) {}

    /** Halang dua sync serentak pada integrasi yang sama. */
    public function middleware(): array
    {
        return [(new WithoutOverlapping((string) $this->integrationId))->expireAfter(180)];
    }

    public function handle(SheetSyncService $sync): void
    {
        $integration = SheetIntegration::with('service')->find($this->integrationId);

        if (! $integration || ! $integration->isReadyToSync()) {
            return;
        }

        $sync->sync($integration, $this->year, $this->month, $this->trigger, $this->userId);
    }
}
