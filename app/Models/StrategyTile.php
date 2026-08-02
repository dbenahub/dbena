<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StrategyTile extends Model
{
    protected $fillable = ['service_id', 'position', 'label', 'value', 'unit', 'icon'];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
