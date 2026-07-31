<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CriticalMetricTarget extends Model
{
    use HasFactory;

    protected $fillable = ['critical_metric_id', 'year', 'monthly_target', 'target_text'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'monthly_target' => 'decimal:2',
        ];
    }

    public function criticalMetric(): BelongsTo
    {
        return $this->belongsTo(CriticalMetric::class);
    }

    /**
     * Prototaip mempunyai sasaran bukan-angka 'Progress' yang menyebabkan
     * parseNum() pulangkan null dan status kekal Red selamanya (soalan Q6).
     * Sasaran sedemikian dianggap "tidak boleh dinilai" — bukan gagal.
     */
    public function isNumeric(): bool
    {
        return $this->monthly_target !== null;
    }

    public function displayValue(): string
    {
        if (! $this->isNumeric()) {
            return $this->target_text ?? '—';
        }

        return (string) $this->monthly_target;
    }
}
