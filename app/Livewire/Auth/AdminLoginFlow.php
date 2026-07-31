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
#[Title('Log Masuk Admin — DBENA')]
class AdminLoginFlow extends Component
{
    use HandlesOtpFlow;

    /** Hanya akaun admin diterima di /admin/login. */
    protected function allowedRole(): ?UserRole
    {
        return UserRole::Admin;
    }

    protected function redirectAfterLogin(): string
    {
        return route('admin.panel');
    }

    public function render(): View
    {
        return view('livewire.auth.admin-login-flow');
    }
}
