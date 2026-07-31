<?php

declare(strict_types=1);

namespace App\Models\Concerns;

/**
 * Accessor dwibahasa (keputusan D1).
 *
 * Prototaip memaparkan kedua-dua bahasa serentak ("Log Masuk / Sign In").
 * Di sini kita simpan lajur _ms/_en berasingan dan pulangkan satu sahaja
 * mengikut locale semasa.
 */
trait HasBilingualAttributes
{
    /** Pulangkan nilai lajur mengikut locale, dengan fallback ke BM. */
    protected function localized(string $base): string
    {
        $suffix = app()->getLocale() === 'en' ? '_en' : '_ms';
        $value = $this->getAttribute($base.$suffix);

        if (blank($value)) {
            $value = $this->getAttribute($base.'_ms') ?: $this->getAttribute($base.'_en');
        }

        return (string) $value;
    }
}
