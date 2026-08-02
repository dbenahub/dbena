<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OrgLink;
use App\Models\OrgNode;
use App\Support\OrgPalette;
use Illuminate\Support\Collection;

/**
 * Menukar kanvas carta organisasi kepada geometri halaman PDF.
 *
 * Dipisahkan daripada templat kerana ia matematik, bukan penanda. Templat
 * yang mengira skala dan segmen garisan di tengah-tengah HTML tidak boleh
 * diuji, dan satu tanda tolak yang salah menghasilkan PDF yang kelihatan
 * hampir betul.
 *
 * DUA KEKANGAN DomPDF membentuk kelas ini:
 *
 *  1. <svg> SEBARIS TIDAK DIPAPARKAN. DomPDF hanya membaca SVG melalui
 *     <img src="data:image/svg+xml">. Versi pertama melukis penyambung
 *     dengan <svg> sebaris, jadi PDF yang dieksport keluar tanpa SATU pun
 *     garisan — sekumpulan kotak terapung tanpa hierarki. Ia juga gagal
 *     secara senyap: fail dijana, ia dibuka, dan hanya orang yang tahu
 *     rupa carta itu akan perasan.
 *
 *     Jadi setiap garisan dipecahkan kepada segmen tegak dan mendatar
 *     yang dilukis sebagai <div> berkedudukan mutlak.
 *
 *  2. TIADA transform: scale(). Skala mesti dikira dalam PHP dan
 *     dikenakan pada setiap koordinat.
 */
class OrgChartPdfLayout
{
    /** Ketebalan garisan penyambung dalam titik. */
    public const STROKE = 0.9;

    /**
     * @param  Collection<int, OrgNode>  $nodes
     * @param  Collection<int, OrgLink>  $links
     * @param  array{x: float, y: float, w: float, h: float}  $area Kawasan kandungan dalam titik
     * @return array<string, mixed>
     */
    public function build(Collection $nodes, Collection $links, array $area): array
    {
        if ($nodes->isEmpty()) {
            return ['scale' => 1.0, 'boxes' => [], 'segments' => []];
        }

        $canvasW = max(1, (int) $nodes->max(fn (OrgNode $n) => $n->x + $n->width));
        $canvasH = max(1, (int) $nodes->max(fn (OrgNode $n) => $n->bottomY()));

        /*
         * Muat-untuk-isi, dan TIDAK PERNAH membesar melebihi 1:1.
         *
         * Carta kecil yang diregangkan untuk memenuhi A3 menghasilkan
         * teks 20pt dalam kotak yang direka untuk 8pt — ia kelihatan
         * seperti kesilapan, bukan seperti reka bentuk.
         */
        $scale = min($area['w'] / $canvasW, $area['h'] / $canvasH, 1.0);

        // Berpusat dalam kawasan kandungan. Carta yang melekat pada sudut
        // kiri atas kelihatan seperti pemaparan yang tidak selesai.
        $offsetX = $area['x'] + ($area['w'] - $canvasW * $scale) / 2;
        $offsetY = $area['y'] + ($area['h'] - $canvasH * $scale) / 2;

        $boxes = $nodes->map(fn (OrgNode $node) => $this->box($node, $scale, $offsetX, $offsetY))->values()->all();

        return [
            'scale' => $scale,
            'canvasWidth' => $canvasW,
            'canvasHeight' => $canvasH,
            'boxes' => $boxes,
            'segments' => $this->segments($nodes, $links, $scale, $offsetX, $offsetY),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function box(OrgNode $node, float $scale, float $ox, float $oy): array
    {
        $warna = OrgPalette::clean($node->color) ?? $this->styleHex($node);
        $teks = OrgPalette::textOn($warna);

        /*
         * Saiz fon mempunyai LANTAI.
         *
         * Skala di bawah 0.6 akan menghasilkan teks 5pt yang tepat secara
         * matematik dan tidak boleh dibaca dalam cetakan. Nama menjadi
         * lebih penting daripada penjajaran yang sempurna pada saiz itu.
         */
        $fon = fn (float $asas) => round(max(4.6, $asas * $scale), 2);

        $titleSize = $fon(9.5);
        $subtitleSize = $fon(7.6);
        $nameSize = $fon(9.0);

        /*
         * Teks dipusatkan MENEGAK dengan mengira ketinggiannya.
         *
         * Padding atas tetap membuat teks duduk tinggi dalam kotak dua
         * baris dan melimpah dalam kotak tiga baris. Perbezaan itu kecil
         * pada satu kotak dan jelas apabila enam belas kotak berbaris
         * bersebelahan — mata melihat ketidakseimbangan sebelum ia dapat
         * menamakannya.
         */
        $tinggiTeks = 0.0;
        $tinggiTeks += filled($node->title) ? $titleSize * 1.18 : 0;
        $tinggiTeks += filled($node->subtitle) ? $subtitleSize * 1.18 : 0;
        $tinggiTeks += filled($node->name) ? $nameSize * 1.24 : 0;

        $tinggiKotak = $node->boxHeight() * $scale;

        return [
            'id' => $node->id,
            'padTop' => round(max(2.0, ($tinggiKotak - $tinggiTeks) / 2), 2),
            'left' => round($ox + $node->x * $scale, 2),
            'top' => round($oy + $node->y * $scale, 2),
            'width' => round($node->width * $scale, 2),
            'height' => round($node->boxHeight() * $scale, 2),
            'radius' => round(max(1.5, 6 * $scale), 2),

            'background' => $warna,
            'border' => OrgPalette::borderOn($warna),
            'badge' => OrgPalette::badgeOn($warna),

            'title' => $node->title,
            'subtitle' => $node->subtitle,
            'name' => $node->name,

            'titleColor' => $teks,
            'subtitleColor' => OrgPalette::mutedTextOn($warna),
            'nameColor' => $teks,

            'titleSize' => $titleSize,
            'subtitleSize' => $subtitleSize,
            'nameSize' => $nameSize,

            'hasBadge' => filled($node->icon),
            'badgeSize' => round(max(7.0, 17 * $scale), 2),
        ];
    }

    /**
     * Setiap penyambung sebagai segmen tegak dan mendatar.
     *
     * Bentuk siku, bukan pepenjuru: carta organisasi dibaca sebagai
     * hierarki, dan pepenjuru mencadangkan hubungan sisi.
     *
     * @param  Collection<int, OrgNode>  $nodes
     * @param  Collection<int, OrgLink>  $links
     * @return array<int, array<string, mixed>>
     */
    private function segments(Collection $nodes, Collection $links, float $scale, float $ox, float $oy): array
    {
        $byId = $nodes->keyBy('id');
        $segments = [];

        foreach ($links as $link) {
            $a = $byId->get($link->from_node_id);
            $b = $byId->get($link->to_node_id);

            if ($a === null || $b === null) {
                continue;
            }

            $x1 = $a->centerX();
            $y1 = $a->bottomY();
            $x2 = $b->centerX();
            $y2 = $b->y;

            // Apabila sasaran berada DI ATAS puncanya, siku dilukis
            // sedikit di bawah punca supaya garisan tidak berundur ke
            // dalam kotak dan hilang.
            $mid = $y2 > $y1 ? $y1 + ($y2 - $y1) / 2 : $y1 + 18;

            $putus = $link->style->dashArray() !== null;

            $tambah = function (float $ax, float $ay, float $bx, float $by) use (
                &$segments, $scale, $ox, $oy, $putus
            ): void {
                $lebar = abs($bx - $ax) * $scale;
                $tinggi = abs($by - $ay) * $scale;

                // Segmen sifar-panjang menjadi titik hodoh dalam cetakan.
                if ($lebar < 0.4 && $tinggi < 0.4) {
                    return;
                }

                $segments[] = [
                    'left' => round($ox + min($ax, $bx) * $scale, 2),
                    'top' => round($oy + min($ay, $by) * $scale, 2),
                    'width' => round(max($lebar, self::STROKE), 2),
                    'height' => round(max($tinggi, self::STROKE), 2),
                    'dashed' => $putus,
                ];
            };

            $tambah($x1, $y1, $x1, $mid);        // tegak dari punca
            $tambah($x1, $mid, $x2, $mid);       // mendatar
            $tambah($x2, $mid, $x2, $y2);        // tegak ke sasaran
        }

        return $segments;
    }

    /** Warna sandaran apabila kotak tiada warna sendiri. */
    private function styleHex(OrgNode $node): string
    {
        return match ($node->style->value) {
            'executive' => '#4A1236',
            'support' => '#F4F1F6',
            default => '#4B2E83',
        };
    }
}
