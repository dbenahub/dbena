<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\View\View;
use Livewire\Component;

class ThemeToggle extends Component
{
    public string $theme = 'dark';

    public function mount(): void
    {
        $this->theme = auth()->user()?->theme ?? 'dark';
    }

    /** Persist ke users.theme — bukan hanya state runtime seperti prototaip. */
    public function toggle(): void
    {
        $this->theme = $this->theme === 'dark' ? 'light' : 'dark';

        auth()->user()?->update(['theme' => $this->theme]);

        $this->dispatch('theme-changed', theme: $this->theme);
    }

    public function render(): View
    {
        return view('livewire.theme-toggle');
    }
}
