<?php

declare(strict_types=1);

namespace App\Enums;

enum ProjectStatus: string
{
    case Selesai = 'selesai';
    case DalamProses = 'dalam_proses';
    case MenungguKelulusan = 'menunggu_kelulusan';
    case Perancangan = 'perancangan';

    public function color(): string
    {
        return match ($this) {
            self::Selesai => 'oklch(0.72 0.15 145)',
            self::DalamProses => 'oklch(0.75 0.14 70)',
            self::MenungguKelulusan => 'oklch(0.65 0.15 250)',
            self::Perancangan => 'var(--t60)',
        };
    }

    public function label(): string
    {
        return __('service.project_status.'.$this->value);
    }

    /** Projek yang dikira sebagai "disahkan" untuk kadar penukaran. */
    public function isConverted(): bool
    {
        return in_array($this, [self::Selesai, self::DalamProses], true);
    }

    public static function options(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->label()], self::cases());
    }
}
