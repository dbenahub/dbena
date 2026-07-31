<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\View\View;
use Livewire\Component;

/**
 * Keputusan D1 — switcher sebenar BM ⇄ EN.
 *
 * Prototaip hanya memaparkan kedua-dua bahasa serentak ("Log Masuk / Sign In")
 * tanpa sebarang cara untuk memilih satu.
 */
class LanguageSwitcher extends Component
{
    public string $locale = 'ms';

    public function mount(): void
    {
        $this->locale = app()->getLocale();
    }

    public function switchTo(string $locale): void
    {
        if (! in_array($locale, ['ms', 'en'], true)) {
            return;
        }

        $this->locale = $locale;

        session()->put('locale', $locale);
        auth()->user()?->update(['locale' => $locale]);

        // Muat semula penuh supaya SEMUA string pada halaman bertukar.
        $this->redirect(request()->header('Referer') ?? route('dashboard'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.language-switcher');
    }
}
