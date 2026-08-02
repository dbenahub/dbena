<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Tanda satu hari pada satu tugasan.
 *
 * Lima ini datang terus daripada petunjuk papan DBENA. Hurufnya penting:
 * papan dicetak dan dibaca dari seberang meja mesyuarat, di mana warna
 * sahaja tidak mencukupi — sesetengah orang tidak membezakan hijau
 * daripada kuning, dan mesin fotokopi hitam-putih memusnahkan kesemuanya.
 */
enum TaskMark: string
{
    case Planning = 'planning';
    case DueDate = 'due_date';
    case Complete = 'complete';
    case Kiv = 'kiv';
    case Cancel = 'cancel';

    /** Huruf yang muncul dalam petak hari. */
    public function letter(): string
    {
        return match ($this) {
            self::Planning => 'P',
            self::DueDate => 'DD',
            self::Complete => 'C',
            self::Kiv => 'K',
            self::Cancel => 'B',
        };
    }

    public function label(): string
    {
        return __('task.mark.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Planning => '#FFE81A',
            self::DueDate => '#F2A33C',
            self::Complete => '#3FC552',
            self::Kiv => '#F02FA8',
            self::Cancel => '#F02A22',
        };
    }

    /**
     * Warna teks dikira sekali di sini dan bukan diteka pada setiap
     * paparan. Kuning terang dengan teks putih ialah petak kosong.
     */
    public function textColor(): string
    {
        return match ($this) {
            self::Planning, self::DueDate, self::Complete => '#14110F',
            default => '#FFFFFF',
        };
    }

    /** Tugasan yang dikira SIAP dalam ringkasan bulanan. */
    public function isDone(): bool
    {
        return $this === self::Complete;
    }

    public function isCancelled(): bool
    {
        return $this === self::Cancel;
    }

    public function isPending(): bool
    {
        return $this === self::Kiv;
    }
}
