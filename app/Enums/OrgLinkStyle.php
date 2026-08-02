<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Jenis garisan antara dua kotak.
 *
 * Pepejal bermaksud pelaporan langsung. Putus-putus bermaksud hubungan
 * sokongan atau kontrak — freelancer dalam carta DBENA disambung begitu.
 * Melukis kedua-duanya sama bermakna carta mendakwa freelancer melapor
 * secara langsung, yang mengubah maksud carta itu.
 */
enum OrgLinkStyle: string
{
    case Solid = 'solid';
    case Dashed = 'dashed';

    public function label(): string
    {
        return __('org.link.'.$this->value);
    }

    public function dashArray(): ?string
    {
        return $this === self::Dashed ? '5 4' : null;
    }
}
