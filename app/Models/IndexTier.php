<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasBilingualAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndexTier extends Model
{
    use HasBilingualAttributes, HasFactory;

    protected $fillable = [
        'key', 'name_ms', 'name_en', 'color_token', 'sort_order',
        'monthly_revenue_threshold', 'monthly_profit_threshold',
    ];

    protected function casts(): array
    {
        return [
            'monthly_revenue_threshold' => 'decimal:2',
            'monthly_profit_threshold' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => $this->localized('name'));
    }

    protected function alternateName(): Attribute
    {
        return Attribute::get(fn (): string => app()->getLocale() === 'en' ? $this->name_ms : $this->name_en);
    }

    public function revenueFor(int $multiplier): float
    {
        return (float) $this->monthly_revenue_threshold * $multiplier;
    }

    public function profitFor(int $multiplier): float
    {
        return (float) $this->monthly_profit_threshold * $multiplier;
    }
}
