<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceMonthlyTarget extends Model
{
    protected $fillable = ['service_id', 'year', 'month', 'target', 'updated_by'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'target' => 'decimal:2',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
