<?php

declare(strict_types=1);

namespace App\Enums;

enum ReportPeriod: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return __('owner_report.period.'.$this->value);
    }

    /** Bilangan bulan yang dirangkumi satu tempoh. */
    public function monthSpan(): int
    {
        return match ($this) {
            self::Weekly, self::Monthly => 1,
            self::Yearly => 12,
        };
    }

    public function isWeekly(): bool
    {
        return $this === self::Weekly;
    }

    public function isYearly(): bool
    {
        return $this === self::Yearly;
    }

    public static function options(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->label()], self::cases());
    }
}
