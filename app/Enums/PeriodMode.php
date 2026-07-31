<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Keputusan D3 — dropdown Period kini AKTIF.
 *
 * Dalam prototaip, `periodConfig.mult` dikira tetapi tidak pernah masuk
 * mana-mana formula (isu #18). Di sini pengganda benar-benar digunakan
 * oleh DashboardMetricsService::applyPeriodMultiplier().
 */
enum PeriodMode: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';

    public function multiplier(): float
    {
        return (float) config('dbena.period_multipliers.'.$this->value);
    }

    public function label(): string
    {
        return __('dashboard.period.'.$this->value);
    }

    public function previousLabel(): string
    {
        return __('dashboard.period_prev.'.$this->value);
    }

    /** Bilangan minggu yang diwakili oleh satu unit period. */
    public function weeks(): float
    {
        return $this->multiplier();
    }

    public static function options(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->label()], self::cases());
    }
}
