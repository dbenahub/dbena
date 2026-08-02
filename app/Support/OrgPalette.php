<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Warna kotak carta organisasi.
 *
 * Warna TEKS tidak pernah dipilih oleh pengguna — ia dikira daripada warna
 * latar. Membiarkan kedua-duanya bebas bermakna seseorang akhirnya
 * memilih teks kelabu di atas latar kelabu, menyimpannya, dan hanya
 * perasan apabila orang lain bertanya kenapa satu kotak kelihatan kosong.
 *
 * Palet ditetapkan dan bukan pemilih bebas atas sebab yang sama: dua belas
 * warna yang telah disemak lebih berguna daripada 16 juta warna yang
 * separuh daripadanya tidak boleh dibaca.
 */
final class OrgPalette
{
    /** @var array<string, string> hex => kunci nama */
    public const COLORS = [
        '#6B1F47' => 'marun',
        '#8E2A5F' => 'merah_jambu',
        '#5B2E86' => 'ungu',
        '#1F4E79' => 'biru',
        '#10656B' => 'teal',
        '#1E6B4A' => 'hijau',
        '#8A6A12' => 'kuning',
        '#9A4A17' => 'oren',
        '#8C2230' => 'merah',
        '#2A2E3A' => 'kelabu',
        '#4A4F5E' => 'kelabu_cerah',
        '#EDEAF2' => 'putih',
    ];

    /**
     * Bersihkan input hex.
     *
     * Hanya #RRGGBB diterima. Bentuk pendek dan nama CSS dibiarkan gagal
     * di sini dan bukan di dalam penyemak imbas, di mana ia menjadi kotak
     * lutsinar tanpa sebarang ralat untuk dikesan.
     */
    public static function clean(?string $raw): ?string
    {
        $hex = strtoupper(trim((string) $raw));

        if ($hex === '') {
            return null;
        }

        if (! str_starts_with($hex, '#')) {
            $hex = '#'.$hex;
        }

        return preg_match('/^#[0-9A-F]{6}$/', $hex) === 1 ? $hex : null;
    }

    /** Luminans relatif (0 gelap – 1 terang) mengikut formula WCAG. */
    public static function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');

        $channel = static function (float $c): float {
            $c /= 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };

        return 0.2126 * $channel((float) hexdec(substr($hex, 0, 2)))
            + 0.7152 * $channel((float) hexdec(substr($hex, 2, 2)))
            + 0.0722 * $channel((float) hexdec(substr($hex, 4, 2)));
    }

    /** Teks putih atau gelap — mana yang lebih boleh dibaca atas warna ini. */
    public static function textOn(string $hex): string
    {
        $l = self::luminance($hex);

        $atasPutih = 1.05 / ($l + 0.05);
        $atasGelap = ($l + 0.05) / 0.05;

        return $atasPutih >= $atasGelap ? '#FFFFFF' : '#1A1420';
    }

    /** Warna baris tengah — sama tetapi lebih lembut. */
    public static function mutedTextOn(string $hex): string
    {
        return self::textOn($hex) === '#FFFFFF'
            ? 'rgba(255,255,255,0.72)'
            : 'rgba(26,20,32,0.62)';
    }

    /** Sempadan: lebih cerah bagi warna gelap, lebih gelap bagi warna terang. */
    public static function borderOn(string $hex): string
    {
        return self::shift($hex, self::luminance($hex) < 0.4 ? 34 : -34);
    }

    /** Latar lencana ikon. */
    public static function badgeOn(string $hex): string
    {
        return self::shift($hex, self::luminance($hex) < 0.4 ? 22 : -22);
    }

    /** Cerahkan atau gelapkan setiap saluran sebanyak $amount. */
    private static function shift(string $hex, int $amount): string
    {
        $hex = ltrim($hex, '#');
        $keluar = '#';

        foreach ([0, 2, 4] as $offset) {
            $value = (int) hexdec(substr($hex, $offset, 2)) + $amount;
            $keluar .= str_pad(dechex(max(0, min(255, $value))), 2, '0', STR_PAD_LEFT);
        }

        return strtoupper($keluar);
    }
}
