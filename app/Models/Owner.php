<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OwnerStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Owner extends Model
{
    use HasFactory;

    /** Palet warna PIC — satu sumber kebenaran (PEMBETULAN isu #12). */
    public const PALETTE = [
        'oklch(0.6 0.15 250)',
        'oklch(0.7 0.12 85)',
        'oklch(0.6 0.16 350)',
        'oklch(0.6 0.15 145)',
        'oklch(0.62 0.15 30)',
        'oklch(0.62 0.15 190)',
        'oklch(0.62 0.15 100)',
        'oklch(0.62 0.15 320)',
    ];

    protected $fillable = [
        'name', 'color_token', 'is_core', 'is_system', 'status',
        'created_by', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'is_core' => 'boolean',
            'is_system' => 'boolean',
            'status' => OwnerStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function criticalMetricMonths(): HasMany
    {
        return $this->hasMany(CriticalMetricMonth::class);
    }

    public function defaultForMetrics(): HasMany
    {
        return $this->hasMany(CriticalMetric::class, 'default_owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', OwnerStatus::Active);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', OwnerStatus::PendingApproval);
    }

    /** PIC sebenar sahaja — tidak termasuk label sistem seperti INFO. */
    public function scopeScorable(Builder $query): Builder
    {
        return $query->active()->where('is_system', false);
    }

    /**
     * PEMBETULAN isu #10: prototaip membenarkan removeOwner() tanpa sebarang
     * guard backend. PIC teras, PIC sistem, dan PIC yang masih memegang data
     * aktif tidak boleh dibuang.
     */
    public function isRemovable(): bool
    {
        return ! $this->is_core && ! $this->is_system && ! $this->hasActiveData();
    }

    public function hasActiveData(): bool
    {
        return $this->criticalMetricMonths()->exists() || $this->defaultForMetrics()->exists();
    }

    /** Warna seterusnya dalam palet, berdasarkan bilangan rekod sedia ada. */
    public static function nextColor(): string
    {
        return self::PALETTE[static::count() % count(self::PALETTE)];
    }
}
