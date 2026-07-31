<?php

declare(strict_types=1);

namespace App\Enums;

enum ViewMode: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return __('dashboard.view_mode.'.$this->value);
    }

    public function isYearly(): bool
    {
        return $this === self::Yearly;
    }

    /** Pengganda threshold tier: bulanan ×1, tahunan ×12. */
    public function tierMultiplier(): int
    {
        return $this === self::Yearly ? 12 : 1;
    }
}
