<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrgNodeStyle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgNode extends Model
{
    /** Tinggi tetap satu kotak — garisan dikira daripadanya. */
    public const HEIGHT = 62;

    protected $fillable = ['title', 'name', 'icon', 'style', 'x', 'y', 'width', 'sort_order'];

    protected function casts(): array
    {
        return [
            'style' => OrgNodeStyle::class,
            'x' => 'integer',
            'y' => 'integer',
            'width' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function linksFrom(): HasMany
    {
        return $this->hasMany(OrgLink::class, 'from_node_id');
    }

    public function linksTo(): HasMany
    {
        return $this->hasMany(OrgLink::class, 'to_node_id');
    }

    public function centerX(): int
    {
        return $this->x + (int) round($this->width / 2);
    }

    public function bottomY(): int
    {
        return $this->y + self::HEIGHT;
    }
}
