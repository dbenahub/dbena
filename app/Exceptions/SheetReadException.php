<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class SheetReadException extends RuntimeException
{
    /**
     * HTTP 403 daripada Google.
     *
     * Maksudnya berbeza sepenuhnya mengikut pemacu, dan mesej lama hanya
     * betul untuk satu daripadanya:
     *
     *   pemacu 'link'    — sheet tidak dikongsi secara awam
     *   pemacu 'service' — sheet tidak dikongsi dengan SATU emel robot
     *
     * Memberitahu pemilik sheet SULIT untuk "kongsi secara awam" ialah
     * nasihat yang salah dan berbahaya. Perkongsian juga per-FAIL, jadi
     * fail kedua memerlukan langkah yang sama walaupun yang pertama sudah
     * berfungsi.
     */
    public static function notShared(?string $serviceAccount = null): self
    {
        return new self(filled($serviceAccount)
            ? __('sheets.error.not_shared_service', ['email' => $serviceAccount])
            : __('sheets.error.not_shared'));
    }

    /**
     * Kalendar yang tidak dikongsi.
     *
     * BERASINGAN daripada notShared(). Mesej itu menyuruh admin membuka
     * fail dalam Google Sheets dan menetapkan General access — arahan yang
     * tiada makna untuk kalendar, dan yang menghantar mereka ke aplikasi
     * yang salah sepenuhnya untuk mencari tetapan yang tidak wujud di sana.
     */
    /**
     * Google menolak permintaan kalendar — dengan sebabnya sendiri.
     *
     * 403 bermaksud SAMA ADA "belum dikongsi" ATAU "Calendar API belum
     * diaktifkan dalam projek ini". Dua masalah, dua pembetulan yang
     * berlainan sepenuhnya. Memetakan kedua-duanya kepada satu mesej
     * menghantar admin membetulkan perkongsian berulang kali sementara
     * punca sebenar tidak pernah disentuh.
     */
    public static function calendarRefused(
        ?string $serviceAccount,
        string $googleMessage = '',
        string $reason = '',
        ?string $enableUrl = null,
    ): self {
        $apiMati = $reason === 'accessNotConfigured'
            || str_contains($googleMessage, 'has not been used in project');

        if ($apiMati) {
            return new self(__('roadmap.calendar.api_disabled', ['url' => $enableUrl ?? '—']));
        }

        $asas = filled($serviceAccount)
            ? __('roadmap.calendar.not_shared_service', ['email' => $serviceAccount])
            : __('roadmap.calendar.not_shared');

        // Ayat Google disertakan mentah. Ia kadangkala menyebut perkara
        // yang tidak pernah kita jangka, dan menyembunyikannya bermakna
        // menyembunyikan satu-satunya petunjuk yang ada.
        return new self($googleMessage !== ''
            ? $asas.' '.__('roadmap.calendar.google_said', ['message' => $googleMessage])
            : $asas);
    }

    public static function calendarNotShared(?string $serviceAccount = null): self
    {
        return new self(filled($serviceAccount)
            ? __('roadmap.calendar.not_shared_service', ['email' => $serviceAccount])
            : __('roadmap.calendar.not_shared'));
    }

    public static function notFound(): self
    {
        return new self(__('sheets.error.not_found'));
    }

    public static function tooLarge(int $bytes): self
    {
        return new self(__('sheets.error.too_large', ['mb' => round($bytes / 1048576, 1)]));
    }

    public static function empty(): self
    {
        return new self(__('sheets.error.empty'));
    }

    public static function network(string $reason): self
    {
        return new self(__('sheets.error.network', ['reason' => $reason]));
    }

    public static function missingCredentials(string $path): self
    {
        return new self(__('sheets.error.missing_credentials', ['path' => $path]));
    }

    public static function badCredentials(string $reason): self
    {
        return new self($reason);
    }
}
