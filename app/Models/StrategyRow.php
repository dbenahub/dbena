<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StrategyRow extends Model
{
    protected $fillable = [
        'service_id', 'position', 'kra', 'kpi', 'target',
        'tactics', 'initiatives', 'timeline', 'pic', 'source_row',
    ];

    protected function casts(): array
    {
        return ['position' => 'integer', 'source_row' => 'integer'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
