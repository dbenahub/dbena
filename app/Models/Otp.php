<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OtpType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Otp extends Model
{
    use Prunable;

    protected $fillable = [
        'user_id', 'code_hash', 'type', 'expires_at', 'consumed_at', 'attempts', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'type' => OtpType::class,
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUsable(Builder $query): Builder
    {
        return $query->whereNull('consumed_at')->where('expires_at', '>', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function hasAttemptsLeft(): bool
    {
        return $this->attempts < config('dbena.otp.max_attempts');
    }

    /** Buang OTP luput lebih 7 hari (dijadualkan setiap jam). */
    public function prunable(): Builder
    {
        return static::where('expires_at', '<', now()->subDays(7));
    }
}
