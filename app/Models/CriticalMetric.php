<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MetricType;
use App\Enums\MetricValueType;
use App\Models\Concerns\HasBilingualAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CriticalMetric extends Model
{
    use HasBilingualAttributes, HasFactory;

    protected $fillable = [
        'service_id', 'metric_key', 'label_ms', 'label_en',
        'type', 'value_type', 'default_owner_id', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => MetricType::class,
            'value_type' => MetricValueType::class,
            'sort_order' => 'integer',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function defaultOwner(): BelongsTo
    {
        return $this->belongsTo(Owner::class, 'default_owner_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(CriticalMetricTarget::class);
    }

    public function weeklyEntries(): HasMany
    {
        return $this->hasMany(CriticalWeeklyEntry::class);
    }

    public function months(): HasMany
    {
        return $this->hasMany(CriticalMetricMonth::class);
    }

    protected function label(): Attribute
    {
        return Attribute::get(fn (): string => $this->localized('label'));
    }

    public function targetForYear(int $year): ?CriticalMetricTarget
    {
        return $this->targets->firstWhere('year', $year)
            ?? $this->targets()->where('year', $year)->first();
    }

    public function isCurrency(): bool
    {
        return $this->value_type === MetricValueType::Currency;
    }
}
