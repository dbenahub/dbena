<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Rupa satu kotak dalam carta organisasi.
 *
 * Gaya ini BUKAN hiasan. Dalam carta DBENA, kotak ungu pekat bermaksud
 * jawatan eksekutif, kotak putih bermaksud jabatan, dan kotak bersempadan
 * nipis bermaksud sokongan atau freelancer. Membaca carta itu bergantung
 * pada perbezaan tersebut, jadi ia disimpan sebagai maksud dan bukan
 * sebagai kod warna yang boleh ditaip berbeza setiap kali.
 */
enum OrgNodeStyle: string
{
    case Executive = 'executive';
    case Department = 'department';
    case Support = 'support';

    public function label(): string
    {
        return __('org.style.'.$this->value);
    }

    /** Latar kotak. */
    public function background(): string
    {
        return match ($this) {
            self::Executive => 'linear-gradient(135deg, oklch(0.30 0.13 340), oklch(0.24 0.10 320))',
            self::Department => 'var(--hover-bg2)',
            self::Support => 'var(--card-bg)',
        };
    }

    public function border(): string
    {
        return match ($this) {
            self::Executive => '1px solid oklch(0.48 0.14 335)',
            self::Department => '1px solid var(--border2)',
            self::Support => '1px dashed var(--border2)',
        };
    }

    /** Warna teks tajuk (baris atas). */
    public function titleColor(): string
    {
        return match ($this) {
            self::Executive => 'oklch(0.99 0 0)',
            default => 'var(--t70)',
        };
    }

    /** Warna nama orang (baris bawah, lebih menonjol). */
    public function nameColor(): string
    {
        return match ($this) {
            self::Executive => 'oklch(0.99 0 0)',
            default => 'var(--t94)',
        };
    }

    /** Warna baris tengah kecil ("Head of Dept."). */
    public function subtitleColor(): string
    {
        return match ($this) {
            self::Executive => 'oklch(0.99 0 0 / 0.72)',
            default => 'var(--t60)',
        };
    }

    /** Latar lencana ikon yang duduk di atas tepi kotak. */
    public function badge(): string
    {
        return match ($this) {
            self::Executive => 'oklch(0.42 0.15 340)',
            self::Department => 'oklch(0.36 0.13 335)',
            self::Support => 'var(--hover-bg2)',
        };
    }

    public function badgeRing(): string
    {
        return match ($this) {
            self::Support => 'var(--border2)',
            default => 'oklch(0.62 0.15 340)',
        };
    }

    public function badgeIcon(): string
    {
        return match ($this) {
            self::Support => 'var(--t70)',
            default => 'oklch(0.99 0 0)',
        };
    }

    public function accent(): string
    {
        return match ($this) {
            self::Executive => 'oklch(0.72 0.16 340)',
            self::Department => 'oklch(0.62 0.14 330)',
            self::Support => 'var(--t60)',
        };
    }
}
