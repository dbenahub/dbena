<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoadmapPlan extends Model
{
    protected $fillable = ['year', 'title', 'subtitle', 'summary', 'calendar_id'];

    protected function casts(): array
    {
        return ['year' => 'integer', 'summary' => 'array'];
    }

    public static function forYear(int $year): self
    {
        return static::firstOrCreate(['year' => $year]);
    }
}
