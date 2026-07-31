<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasBilingualAttributes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Priority extends Model
{
    use HasBilingualAttributes, HasFactory;

    protected $fillable = [
        'service_id', 'title_ms', 'title_en', 'desc_ms', 'desc_en',
        'owner_name', 'owner_id', 'avatar_seed', 'icon_class', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected function description(): Attribute
    {
        return Attribute::get(fn (): string => $this->localized('desc'));
    }

    protected function title(): Attribute
    {
        return Attribute::get(fn (): string => $this->localized('title'));
    }

    /** Inisial pemilik untuk avatar (menggantikan CDN pravatar.cc). */
    public function ownerInitials(): string
    {
        return collect(explode(' ', trim((string) $this->owner_name)))
            ->filter()->take(2)
            ->map(fn (string $p) => mb_strtoupper(mb_substr($p, 0, 1)))
            ->implode('');
    }
}
