<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SheetSyncLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sheet_integration_id', 'service_id', 'trigger', 'status', 'year', 'month',
        'rows_read', 'rows_matched', 'values_updated', 'rows_skipped',
        'unmatched_labels', 'message', 'duration_ms', 'triggered_by', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'unmatched_labels' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(SheetIntegration::class, 'sheet_integration_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'success' => 'oklch(0.55 0.15 145)',
            'partial' => 'oklch(0.78 0.15 85)',
            default => 'oklch(0.55 0.2 25)',
        };
    }
}
