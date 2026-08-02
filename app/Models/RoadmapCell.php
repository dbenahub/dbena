<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoadmapStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoadmapCell extends Model
{
    protected $fillable = ['service_id', 'year', 'month', 'status', 'note'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'status' => RoadmapStatus::class,
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
