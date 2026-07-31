<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id', 'name', 'client_name', 'value', 'status', 'project_date', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'status' => ProjectStatus::class,
            'project_date' => 'date',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForPeriod(Builder $query, int $year, ?int $month = null): Builder
    {
        $query->whereYear('project_date', $year);

        return $month ? $query->whereMonth('project_date', $month) : $query;
    }

    public function scopeConverted(Builder $query): Builder
    {
        return $query->whereIn('status', [ProjectStatus::Selesai->value, ProjectStatus::DalamProses->value]);
    }
}
