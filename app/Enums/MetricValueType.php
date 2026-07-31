<?php

declare(strict_types=1);

namespace App\Enums;

enum MetricValueType: string
{
    case Currency = 'currency';
    case Number = 'number';

    public function format(float|int|null $value): string
    {
        if ($value === null) {
            return '—';
        }

        return $this === self::Currency
            ? 'RM'.number_format((float) round($value))
            : number_format((float) round($value));
    }

    /** Sasaran mingguan: ceil() untuk kiraan, round() untuk amaun. */
    public function weeklyRounding(): string
    {
        return $this === self::Currency ? 'round' : 'ceil';
    }
}
