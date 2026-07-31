<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\SheetIntegration;

interface SheetReader
{
    /**
     * Baca satu tab sheet sebagai grid mentah.
     *
     * @return array<int, array<int, string>> baris → sel (indeks 0-based)
     *
     * @throws \App\Exceptions\SheetReadException
     */
    public function read(SheetIntegration $integration): array;

    /** Nama boleh-baca-manusia untuk driver ini (dipapar dalam UI). */
    public function label(): string;
}
