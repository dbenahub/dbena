<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pelan strategik satu servis — salinan tab Google Sheet.
 */
class StrategyPlan extends Model
{
    protected $fillable = ['service_id', 'heading', 'vision', 'synced_at'];

    protected function casts(): array
    {
        return ['synced_at' => 'datetime'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
