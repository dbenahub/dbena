<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'username', 'email', 'password', 'role', 'phone', 'position',
        'avatar_path', 'locale', 'theme', 'notif_email', 'notif_weekly',
        'notif_sound', 'last_login_at', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'notif_email' => 'boolean',
            'notif_weekly' => 'boolean',
            'notif_sound' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function otps(): HasMany
    {
        return $this->hasMany(Otp::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isUser(): bool
    {
        return $this->role === UserRole::User;
    }

    /**
     * PEMBETULAN isu #22: prototaip menyimpan avatar sebagai base64 runtime
     * (FileReader) yang hilang bila refresh, dan menggunakan CDN luar
     * (dicebear/pravatar). Kini fail sebenar pada storan awam.
     */
    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk(config('dbena.avatar.disk'))->url($this->avatar_path) : null;
    }

    public function initials(): string
    {
        return collect(explode(' ', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $p) => mb_strtoupper(mb_substr($p, 0, 1)))
            ->implode('');
    }
}
