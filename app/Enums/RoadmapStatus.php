<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status satu servis dalam satu bulan pada Roadmap Tahunan.
 *
 * Empat status ini datang terus daripada petunjuk reka bentuk DBENA.
 * Mereka bukan darjah kesihatan — mereka NIAT. "Pause" tidak bermakna
 * servis itu gagal; ia bermakna kempen dihentikan dengan sengaja, dan
 * membezakan keduanya ialah seluruh gunanya roadmap ini.
 */
enum RoadmapStatus: string
{
    case None = 'none';
    case ActiveAllYear = 'active_all_year';
    case Campaign = 'campaign';
    case Paused = 'paused';
    case Resumed = 'resumed';

    public function label(): string
    {
        return __('roadmap.status.'.$this->value);
    }

    public function icon(): string
    {
        return match ($this) {
            self::ActiveAllYear => 'ph-check-circle',
            self::Campaign => 'ph-megaphone',
            self::Paused => 'ph-pause-circle',
            self::Resumed => 'ph-play-circle',
            self::None => 'ph-minus',
        };
    }

    /** Warna latar sel. */
    public function color(): string
    {
        return match ($this) {
            self::ActiveAllYear => 'oklch(0.35 0.13 330)',
            self::Campaign => 'oklch(0.65 0.18 45)',
            self::Paused => 'oklch(0.42 0.02 260)',
            self::Resumed => 'oklch(0.55 0.15 150)',
            self::None => 'oklch(0.24 0.02 260)',
        };
    }

    /** Warna teks yang boleh dibaca atas warna latar di atas. */
    public function textColor(): string
    {
        return match ($this) {
            self::None => 'oklch(0.60 0.02 260)',
            default => 'oklch(0.99 0 0)',
        };
    }

    /**
     * Bulan yang menyumbang kepada sasaran jualan tahunan.
     *
     * Bulan yang dijeda dengan sengaja tidak sepatutnya membawa sasaran.
     * Mendarab sasaran bulanan dengan dua belas bagi setiap servis akan
     * menghasilkan angka tahunan yang tiada siapa pernah komited kepadanya
     * — dan angka itulah yang akan dibawa ke mesyuarat.
     */
    public function countsTowardTarget(): bool
    {
        return $this === self::ActiveAllYear
            || $this === self::Campaign
            || $this === self::Resumed;
    }

    /** Kitaran untuk klik dalam editor Admin. */
    public function next(): self
    {
        return match ($this) {
            self::None => self::ActiveAllYear,
            self::ActiveAllYear => self::Campaign,
            self::Campaign => self::Paused,
            self::Paused => self::Resumed,
            self::Resumed => self::None,
        };
    }
}
