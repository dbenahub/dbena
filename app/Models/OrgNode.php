<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrgNodeStyle;
use App\Support\OrgPalette;
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
        'title', 'subtitle', 'name', 'icon', 'style', 'color',
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

    /**
     * Warna paparan kotak.
     *
     * NULL bermakna "ikut gaya". Menyalin warna gaya ke dalam baris pada
     * masa simpan akan membekukannya: menukar warna gaya kemudian tidak
     * akan menyentuh mana-mana kotak sedia ada, dan tiada siapa dapat tahu
     * sebabnya.
     *
     * @return array<string, string>
     */
    public function palette(): array
    {
        $hex = OrgPalette::clean($this->color);

        if ($hex === null) {
            return [
                'background' => $this->style->background(),
                'border' => $this->style->border(),
                'title' => $this->style->titleColor(),
                'subtitle' => $this->style->subtitleColor(),
                'name' => $this->style->nameColor(),
                'badge' => $this->style->badge(),
                'badgeRing' => $this->style->badgeRing(),
                'badgeIcon' => $this->style->badgeIcon(),
            ];
        }

        // Warna teks DIKIRA, tidak pernah dipilih. Membiarkan kedua-duanya
        // bebas bermakna seseorang akhirnya menyimpan teks kelabu di atas
        // latar kelabu dan hanya perasan apabila orang lain bertanya
        // kenapa satu kotak kelihatan kosong.
        $teks = OrgPalette::textOn($hex);

        return [
            'background' => $hex,
            'border' => '1px solid '.OrgPalette::borderOn($hex),
            'title' => $teks,
            'subtitle' => OrgPalette::mutedTextOn($hex),
            'name' => $teks,
            'badge' => OrgPalette::badgeOn($hex),
            'badgeRing' => OrgPalette::borderOn($hex),
            'badgeIcon' => $teks,
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
