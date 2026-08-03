<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaskMark;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskDayMark extends Model
{
    protected $fillable = [
        'monthly_task_id', 'day', 'mark', 'start_time',
        'google_event_id', 'google_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'day' => 'integer',
            'mark' => TaskMark::class,
            // Dilemparkan sebagai rentetan, bukan datetime. Casting masa
            // sahaja kepada Carbon melekatkan tarikh hari ini padanya, dan
            // tarikh itu muncul dalam pengisihan pada hari berikutnya.
            'start_time' => 'string',
            'google_synced_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(MonthlyTask::class, 'monthly_task_id');
    }
}
