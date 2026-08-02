<?php

declare(strict_types=1);

use App\Enums\OrgLinkStyle;
use App\Enums\OrgNodeStyle;
use App\Models\OrgLink;
use App\Models\OrgNode;
use App\Services\OrgChartPdfLayout;

beforeEach(function (): void {
    $this->layout = new OrgChartPdfLayout;
    $this->area = ['x' => 44.0, 'y' => 116.0, 'w' => 1102.0, 'h' => 664.0];
});

/** Nod dalam ingatan — susun atur tidak menyentuh pangkalan data. */
function onode(int $id, int $x, int $y, int $w = 200, int $h = 52, ?string $color = '#4B2E83'): OrgNode
{
    $node = new OrgNode([
        'title' => 'Kotak '.$id, 'name' => 'NAMA PENUH', 'style' => OrgNodeStyle::Department->value,
        'color' => $color, 'x' => $x, 'y' => $y, 'width' => $w, 'height' => $h,
    ]);

    $node->id = $id;

    return $node;
}

function olink(int $from, int $to, string $style = 'solid'): OrgLink
{
    return new OrgLink(['from_node_id' => $from, 'to_node_id' => $to, 'style' => $style]);
}

/*
|--------------------------------------------------------------------------
| Garisan mesti WUJUD
|--------------------------------------------------------------------------
*/

it('turns every link into drawable segments', function (): void {
    // Versi pertama melukis penyambung dengan <svg> sebaris, yang DomPDF
    // tidak papar langsung. PDF keluar tanpa SATU pun garisan — sekumpulan
    // kotak terapung tanpa hierarki — dan ia gagal secara senyap.
    $out = $this->layout->build(
        collect([onode(1, 400, 40), onode(2, 100, 200), onode(3, 700, 200)]),
        collect([olink(1, 2), olink(1, 3)]),
        $this->area
    );

    expect($out['segments'])->not->toBeEmpty()
        ->and(count($out['segments']))->toBeGreaterThanOrEqual(4);
});

it('gives every segment a visible thickness', function (): void {
    // Segmen setebal sifar tidak dicetak langsung.
    $out = $this->layout->build(
        collect([onode(1, 400, 40), onode(2, 400, 200)]),
        collect([olink(1, 2)]),
        $this->area
    );

    foreach ($out['segments'] as $seg) {
        expect($seg['width'])->toBeGreaterThanOrEqual(OrgChartPdfLayout::STROKE)
            ->and($seg['height'])->toBeGreaterThanOrEqual(OrgChartPdfLayout::STROKE);
    }
});

it('drops a link whose node was deleted', function (): void {
    // Garisan menuding ke ruang kosong lebih teruk daripada tiada garisan.
    $out = $this->layout->build(
        collect([onode(1, 400, 40)]),
        collect([olink(1, 999)]),
        $this->area
    );

    expect($out['segments'])->toBe([]);
});

it('marks a dashed link so it stays distinguishable', function (): void {
    $out = $this->layout->build(
        collect([onode(1, 400, 40), onode(2, 400, 200)]),
        collect([olink(1, 2, OrgLinkStyle::Dashed->value)]),
        $this->area
    );

    expect(collect($out['segments'])->every(fn (array $s) => $s['dashed'] === true))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Skala
|--------------------------------------------------------------------------
*/

it('scales a wide chart down to fit the page', function (): void {
    $out = $this->layout->build(
        collect([onode(1, 0, 0), onode(2, 2000, 0)]),
        collect([]),
        $this->area
    );

    expect($out['scale'])->toBeLessThan(1.0);

    $kanan = collect($out['boxes'])->max(fn (array $b) => $b['left'] + $b['width']);

    expect($kanan)->toBeLessThanOrEqual($this->area['x'] + $this->area['w'] + 0.5);
});

it('never blows a small chart up past its real size', function (): void {
    // Carta kecil yang diregangkan untuk memenuhi A3 menghasilkan teks
    // 20pt dalam kotak yang direka untuk 8pt — ia kelihatan seperti
    // kesilapan, bukan seperti reka bentuk.
    $out = $this->layout->build(
        collect([onode(1, 0, 0, 100, 40)]),
        collect([]),
        $this->area
    );

    expect($out['scale'])->toBe(1.0);
});

it('centres the chart in the content area', function (): void {
    // Carta yang melekat pada sudut kiri atas kelihatan seperti pemaparan
    // yang tidak selesai.
    $out = $this->layout->build(
        collect([onode(1, 0, 0, 200, 52)]),
        collect([]),
        $this->area
    );

    $box = $out['boxes'][0];
    $jurangKiri = $box['left'] - $this->area['x'];
    $jurangKanan = ($this->area['x'] + $this->area['w']) - ($box['left'] + $box['width']);

    expect(abs($jurangKiri - $jurangKanan))->toBeLessThan(1.0);
});

it('keeps every box inside the page', function (): void {
    // Pemotongan tepi dalam PDF adalah senyap: fail dijana, ia dibuka, dan
    // hanya orang yang tiada dalam cetakan akan perasan.
    $nodes = collect(range(0, 15))->map(fn (int $i) => onode($i + 1, $i * 120, ($i % 4) * 150));

    $out = $this->layout->build($nodes, collect([]), $this->area);

    foreach ($out['boxes'] as $box) {
        expect($box['left'])->toBeGreaterThanOrEqual($this->area['x'] - 0.5)
            ->and($box['left'] + $box['width'])
            ->toBeLessThanOrEqual($this->area['x'] + $this->area['w'] + 0.5)
            ->and($box['top'] + $box['height'])
            ->toBeLessThanOrEqual($this->area['y'] + $this->area['h'] + 0.5);
    }
});

/*
|--------------------------------------------------------------------------
| Teks mesti boleh dibaca dalam cetakan
|--------------------------------------------------------------------------
*/

it('never prints text below the legibility floor', function (): void {
    // Skala kecil menghasilkan teks yang tepat secara matematik dan tidak
    // boleh dibaca di atas kertas.
    $out = $this->layout->build(
        collect([onode(1, 0, 0), onode(2, 9000, 0)]),
        collect([]),
        $this->area
    );

    foreach ($out['boxes'] as $box) {
        expect($box['titleSize'])->toBeGreaterThanOrEqual(4.6)
            ->and($box['nameSize'])->toBeGreaterThanOrEqual(4.6);
    }
});

it('fits the text inside the box it sits in', function (): void {
    $out = $this->layout->build(
        collect([
            new OrgNode([
                'title' => 'CONTRACT DEPARTMENT', 'subtitle' => 'Head of Dept.',
                'name' => 'MOHD HAFIZAN BIN ABDUL MAJID',
                'style' => OrgNodeStyle::Department->value, 'color' => '#4B2E83',
                'x' => 0, 'y' => 0, 'width' => 200, 'height' => 62,
            ]),
        ]),
        collect([]),
        $this->area
    );

    $box = $out['boxes'][0];

    $tinggiTeks = $box['titleSize'] * 1.18 + $box['subtitleSize'] * 1.18 + $box['nameSize'] * 1.24;

    expect($tinggiTeks)->toBeLessThan($box['height'])
        ->and($box['padTop'])->toBeGreaterThanOrEqual(2.0);
});

it('keeps every box colour readable in print', function (): void {
    $out = $this->layout->build(
        collect([onode(1, 0, 0, 200, 52, '#3D0F2B'), onode(2, 300, 0, 200, 52, '#EDEAF2')]),
        collect([]),
        $this->area
    );

    expect($out['boxes'][0]['titleColor'])->toBe('#FFFFFF')
        ->and($out['boxes'][1]['titleColor'])->toBe('#1A1420');
});

it('handles an empty chart without dividing by zero', function (): void {
    $out = $this->layout->build(collect([]), collect([]), $this->area);

    expect($out['boxes'])->toBe([])
        ->and($out['segments'])->toBe([])
        ->and($out['scale'])->toBe(1.0);
});
