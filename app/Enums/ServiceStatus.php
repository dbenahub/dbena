<?php

declare(strict_types=1);

namespace App\Enums;

/** Ambang 35% — prototaip: `const good = pct >= 35`. */
enum ServiceStatus: string
{
    case Memuaskan = 'memuaskan';
    case PerluDipertingkat = 'perlu_dipertingkat';

    public function color(): string
    {
        return match ($this) {
            self::Memuaskan => 'oklch(0.72 0.15 145)',
            self::PerluDipertingkat => 'oklch(0.75 0.14 70)',
        };
    }

    public function barColor(): string
    {
        return match ($this) {
            self::Memuaskan => 'oklch(0.72 0.15 145)',
            self::PerluDipertingkat => 'oklch(0.6 0.22 350)',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Memuaskan => __('dashboard.status.satisfactory'),
            self::PerluDipertingkat => __('dashboard.status.needs_improvement'),
        };
    }
}
