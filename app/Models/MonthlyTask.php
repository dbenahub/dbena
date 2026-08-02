<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyTask extends Model
{
    protected $fillable = [
        'task_department_id', 'year', 'month', 'title',
        'action_by', 'monitor_by', 'remark', 'sort_order', 'created_by',
    ];

    protected function casts(): array
    {
        return ['year' => 'integer', 'month' => 'integer', 'sort_order' => 'integer'];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(TaskDepartment::class, 'task_department_id');
    }

    public function marks(): HasMany
    {
        return $this->hasMany(TaskDayMark::class);
    }
}
