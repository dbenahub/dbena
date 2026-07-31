<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CriticalMetricMonth extends Model
{
    use HasFactory;

    protected $fillable = [
        'critical_metric_id', 'year', 'month', 'owner_id', 'action_plan', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['year' => 'integer', 'month' => 'integer'];
    }

    public function criticalMetric(): BelongsTo
    {
        return $this->belongsTo(CriticalMetric::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForPeriod(Builder $query, int $year, int $month): Builder
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function hasActionPlan(): bool
    {
        return filled(trim((string) $this->action_plan));
    }
}
