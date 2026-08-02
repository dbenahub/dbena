<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrgNodeStyle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgNode extends Model
{
    /**
     * Tinggi lalai apabila kotak tidak menyatakan sendiri.
     *
     * Bukan lagi tetap: tiga gaya mempunyai bilangan baris yang berbeza,
     * dan tinggi tetap bermakna kotak dua baris membawa ruang kosong
     * sebanyak baris ketiga yang tidak pernah wujud.
     */
    public const HEIGHT = 66;

    protected $fillable = [
        'title', 'subtitle', 'name', 'icon', 'style',
        'x', 'y', 'width', 'height', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'style' => OrgNodeStyle::class,
            'x' => 'integer',
            'y' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
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

    public function boxHeight(): int
    {
        return $this->height > 0 ? $this->height : self::HEIGHT;
    }

    public function bottomY(): int
    {
        return $this->y + $this->boxHeight();
    }
}
