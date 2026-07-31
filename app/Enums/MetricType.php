<?php

declare(strict_types=1);

namespace App\Enums;

/** Lajur "Jenis" dalam jadual Data Kritikal. */
enum MetricType: string
{
    case Total = 'total';
    case Avg = 'avg';

    public function label(): string
    {
        return match ($this) {
            self::Total => 'Total',
            self::Avg => 'Avg',
        };
    }

    /** Metrik jenis Avg dikira purata minggu, bukan jumlah. */
    public function aggregate(array $weekValues): ?float
    {
        $filled = array_values(array_filter($weekValues, fn ($v) => $v !== null && $v !== ''));

        if ($filled === []) {
            return null;
        }

        $sum = array_sum(array_map('floatval', $filled));

        return $this === self::Avg ? $sum / count($filled) : $sum;
    }
}
