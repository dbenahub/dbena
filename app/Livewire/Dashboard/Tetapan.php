<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Tetapan — termasuk Kad Profil + upload avatar (keputusan D2).
 *
 * PEMBETULAN isu #8: prototaip `saveSettings()` hanya memaparkan toast tanpa
 * menyimpan apa-apa. Semua kaedah di sini menulis ke jadual `users`.
 *
 * PEMBETULAN isu #22: avatar disimpan sebagai FAIL SEBENAR pada storan awam,
 * bukan base64 runtime yang hilang selepas refresh.
 */
#[Layout('components.layouts.app')]
class Tetapan extends Component
{
    use WithFileUploads;

    // Keutamaan sistem
    public bool $notifEmail = true;
    public bool $notifWeekly = true;
    public bool $notifSound = false;

    // Profil
    #[Validate('required|string|max:120')]
    public string $name = '';

    #[Validate('nullable|string|max:120')]
    public string $position = '';

    #[Validate('required|email|max:190')]
    public string $email = '';

    #[Validate('nullable|string|max:30')]
    public string $phone = '';

    public ?TemporaryUploadedFile $avatar = null;

    // Kata laluan
    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';

    // Paparan
    public string $theme = 'dark';
    public string $locale = 'ms';

    public function mount(): void
    {
        $user = auth()->user();

        $this->notifEmail = $user->notif_email;
        $this->notifWeekly = $user->notif_weekly;
        $this->notifSound = $user->notif_sound;
        $this->name = $user->name;
        $this->position = (string) $user->position;
        $this->email = $user->email;
        $this->phone = (string) $user->phone;
        $this->theme = $user->theme;
        $this->locale = $user->locale;
    }

    /** Simpan keutamaan sistem — persist SEBENAR ke DB. */
    public function savePreferences(): void
    {
        auth()->user()->update([
            'notif_email' => $this->notifEmail,
            'notif_weekly' => $this->notifWeekly,
            'notif_sound' => $this->notifSound,
        ]);

        $this->dispatch('dbena-toast', message: __('tetapan.settings_saved'));
    }

    public function saveProfile(): void
    {
        $this->validate([
            'name' => 'required|string|max:120',
            'position' => 'nullable|string|max:120',
            'email' => 'required|email|max:190|unique:users,email,'.auth()->id(),
            'phone' => 'nullable|string|max:30',
        ]);

        auth()->user()->update([
            'name' => $this->name,
            'position' => $this->position ?: null,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
        ]);

        $this->dispatch('dbena-toast', message: __('tetapan.profile_saved'));
    }

    public function saveAvatar(): void
    {
        $this->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:'.config('dbena.avatar.max_kb'),
        ]);

        $user = auth()->user();
        $disk = config('dbena.avatar.disk');

        // Buang fail lama supaya storan tidak membengkak.
        if ($user->avatar_path) {
            Storage::disk($disk)->delete($user->avatar_path);
        }

        $path = $this->avatar->store(config('dbena.avatar.path'), $disk);
        $user->update(['avatar_path' => $path]);

        $this->avatar = null;
        $this->dispatch('dbena-toast', message: __('tetapan.avatar_saved'));
    }

    public function removeAvatar(): void
    {
        $user = auth()->user();

        if ($user->avatar_path) {
            Storage::disk(config('dbena.avatar.disk'))->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        $this->dispatch('dbena-toast', message: __('tetapan.avatar_removed'));
    }

    public function changePassword(): void
    {
        $this->validate([
            'currentPassword' => 'required',
            'newPassword' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], attributes: [
            'currentPassword' => __('tetapan.current_password'),
            'newPassword' => __('tetapan.new_password'),
        ]);

        if (! Hash::check($this->currentPassword, auth()->user()->password)) {
            $this->addError('currentPassword', __('tetapan.current_password_wrong'));

            return;
        }

        auth()->user()->update(['password' => $this->newPassword]);

        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->dispatch('dbena-toast', message: __('tetapan.password_changed'));
    }

    public function saveAppearance(): void
    {
        auth()->user()->update([
            'theme' => in_array($this->theme, ['dark', 'light'], true) ? $this->theme : 'dark',
            'locale' => in_array($this->locale, ['ms', 'en'], true) ? $this->locale : 'ms',
        ]);

        session()->put('locale', $this->locale);

        $this->redirect(route('tetapan'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.dashboard.tetapan', [
            'user' => auth()->user(),
        ])->layoutData([
            'pageTitle' => __('tetapan.page_title'),
            'pageSubtitle' => __('tetapan.page_subtitle'),
        ]);
    }
}
