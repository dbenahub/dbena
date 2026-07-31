<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status baris Data Kritikal Mingguan.
 *
 * Logik prototaip (Executive Dashboard.dc.html, baris ~1355):
 *   !hasMonthData          -> Belum Update (kelabu)
 *   pct >= 100             -> Green
 *   ada action plan        -> Yellow
 *   selainnya              -> Red
 */
enum MetricStatus: string
{
    case Green = 'green';
    case Yellow = 'yellow';
    case Red = 'red';
    case BelumUpdate = 'belum_update';

    public function color(): string
    {
        return match ($this) {
            self::Green => 'oklch(0.55 0.15 145)',
            self::Yellow => 'oklch(0.78 0.15 85)',
            self::Red => 'oklch(0.55 0.2 25)',
            self::BelumUpdate => 'oklch(0.6 0.02 260)',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Green => 'Green',
            self::Yellow => 'Yellow',
            self::Red => 'Red',
            self::BelumUpdate => __('service.status.not_updated'),
        };
    }

    public function isCritical(): bool
    {
        return $this === self::Red;
    }
}
