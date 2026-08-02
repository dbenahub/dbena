<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\OrgLink;
use App\Models\OrgNode;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class OrgChartPdfController extends Controller
{
    public function __invoke(): Response
    {
        // Carta ini membawa nama penuh setiap kakitangan. Butang yang
        // disorok bukan sekatan; gate disemak di sini.
        $this->authorize('manage-org-chart');

        $nodes = OrgNode::orderBy('sort_order')->get();
        $links = OrgLink::all();

        /*
         * Saiz halaman dikira daripada kanvas sebenar dan bukan ditetapkan
         * kepada A4 landskap.
         *
         * Carta organisasi berkembang ke sisi setiap kali jabatan ditambah.
         * Halaman tetap bermakna PDF mula memotong kotak di sebelah kanan
         * — dan pemotongan itu senyap: fail dijana, ia dibuka, dan hanya
         * orang yang tiada dalam cetakan akan perasan.
         */
        $lebar = max(1200, (int) $nodes->max(fn (OrgNode $n) => $n->x + $n->width) + 80);
        $tinggi = max(560, (int) $nodes->max(fn (OrgNode $n) => $n->bottomY()) + 120);

        $pdf = Pdf::loadView('pdf.org-chart', [
            'nodes' => $nodes,
            'links' => $links,
            'canvasWidth' => $lebar,
            'canvasHeight' => $tinggi,
        ])->setPaper([0, 0, $lebar * 0.75, $tinggi * 0.75]);

        return $pdf->download('carta-organisasi-dbena-'.now()->format('Y-m-d').'.pdf');
    }
}
