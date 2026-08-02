<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskDepartment extends Model
{
    protected $fillable = ['name', 'icon', 'sort_order', 'active'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'active' => 'boolean'];
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(MonthlyTask::class);
    }
}
