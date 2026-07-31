<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CriticalWeeklyEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'critical_metric_id', 'year', 'month', 'week_number', 'value', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'week_number' => 'integer',
            'value' => 'decimal:2',
        ];
    }

    public function criticalMetric(): BelongsTo
    {
        return $this->belongsTo(CriticalMetric::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForPeriod(Builder $query, int $year, int $month): Builder
    {
        return $query->where('year', $year)->where('month', $month);
    }
}
