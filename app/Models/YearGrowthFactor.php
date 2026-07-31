<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YearGrowthFactor extends Model
{
    use HasFactory;

    protected $fillable = ['year', 'factor'];

    protected function casts(): array
    {
        return ['year' => 'integer', 'factor' => 'float'];
    }

    public static function factorFor(int $year): float
    {
        return (float) (static::query()->where('year', $year)->value('factor') ?? 1.0);
    }

    /** @return array<int, float> */
    public static function map(): array
    {
        return static::query()->orderBy('year')->pluck('factor', 'year')
            ->map(fn ($v) => (float) $v)->all();
    }
}
