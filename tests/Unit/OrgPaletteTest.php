<?php

declare(strict_types=1);

use App\Support\OrgPalette;

/*
|--------------------------------------------------------------------------
| Teks dikira, tidak pernah dipilih
|--------------------------------------------------------------------------
*/

it('puts white text on a dark box', function (): void {
    expect(OrgPalette::textOn('#6B1F47'))->toBe('#FFFFFF')
        ->and(OrgPalette::textOn('#1F4E79'))->toBe('#FFFFFF')
        ->and(OrgPalette::textOn('#2A2E3A'))->toBe('#FFFFFF');
});

it('puts dark text on a light box', function (): void {
    // Membiarkan pengguna memilih kedua-dua warna bermakna seseorang
    // akhirnya menyimpan teks putih di atas latar putih dan hanya perasan
    // apabila orang lain bertanya kenapa satu kotak kelihatan kosong.
    expect(OrgPalette::textOn('#EDEAF2'))->toBe('#1A1420')
        ->and(OrgPalette::textOn('#FFFFFF'))->toBe('#1A1420');
});

it('keeps every palette colour readable', function (): void {
    foreach (array_keys(OrgPalette::COLORS) as $hex) {
        $teks = OrgPalette::textOn($hex);

        $a = OrgPalette::luminance($hex);
        $b = OrgPalette::luminance($teks);

        $nisbah = (max($a, $b) + 0.05) / (min($a, $b) + 0.05);

        expect($nisbah)->toBeGreaterThan(4.5, "Warna {$hex} gagal WCAG AA");
    }
});

/*
|--------------------------------------------------------------------------
| Input yang rosak mesti ditolak di sini
|--------------------------------------------------------------------------
*/

it('accepts a hex code with or without the hash', function (): void {
    expect(OrgPalette::clean('#6b1f47'))->toBe('#6B1F47')
        ->and(OrgPalette::clean('6b1f47'))->toBe('#6B1F47')
        ->and(OrgPalette::clean('  #6B1F47  '))->toBe('#6B1F47');
});

it('rejects anything that is not a full hex code', function (): void {
    // Nilai rosak dalam lajur ini menghasilkan kotak lutsinar dalam
    // penyemak imbas tanpa sebarang ralat untuk dikesan.
    foreach (['#FFF', 'red', 'rgb(1,2,3)', '#12345', '#GGGGGG', 'javascript:x', ''] as $buruk) {
        expect(OrgPalette::clean($buruk))->toBeNull("'{$buruk}' sepatutnya ditolak");
    }
});

it('treats an empty value as follow-the-style', function (): void {
    expect(OrgPalette::clean(null))->toBeNull()
        ->and(OrgPalette::clean('   '))->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Sempadan dan lencana
|--------------------------------------------------------------------------
*/

it('brightens the border on a dark box and darkens it on a light one', function (): void {
    expect(OrgPalette::luminance(OrgPalette::borderOn('#6B1F47')))
        ->toBeGreaterThan(OrgPalette::luminance('#6B1F47'))
        ->and(OrgPalette::luminance(OrgPalette::borderOn('#EDEAF2')))
        ->toBeLessThan(OrgPalette::luminance('#EDEAF2'));
});

it('never runs a channel past the ends of the range', function (): void {
    // Saluran melebihi 255 atau di bawah 0 menghasilkan hex tiga aksara
    // yang penyemak imbas abaikan secara senyap.
    foreach (['#000000', '#FFFFFF'] as $hujung) {
        expect(OrgPalette::borderOn($hujung))->toMatch('/^#[0-9A-F]{6}$/')
            ->and(OrgPalette::badgeOn($hujung))->toMatch('/^#[0-9A-F]{6}$/');
    }
});
