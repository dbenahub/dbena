<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaskMark;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskDayMark extends Model
{
    protected $fillable = ['monthly_task_id', 'day', 'mark'];

    protected function casts(): array
    {
        return ['day' => 'integer', 'mark' => TaskMark::class];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(MonthlyTask::class, 'monthly_task_id');
    }
}
