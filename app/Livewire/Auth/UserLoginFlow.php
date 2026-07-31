<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Enums\UserRole;
use App\Livewire\Concerns\HandlesOtpFlow;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Log Masuk — DBENA')]
class UserLoginFlow extends Component
{
    use HandlesOtpFlow;

    /** Kedua-dua role boleh log masuk melalui /login. */
    protected function allowedRole(): ?UserRole
    {
        return null;
    }

    protected function redirectAfterLogin(): string
    {
        return route('dashboard');
    }

    public function render(): View
    {
        return view('livewire.auth.user-login-flow');
    }
}
