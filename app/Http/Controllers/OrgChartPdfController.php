<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\OrgLink;
use App\Models\OrgNode;
use App\Services\OrgChartPdfLayout;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class OrgChartPdfController extends Controller
{
    /** A3 landskap dalam titik. */
    private const PAGE_W = 1190.55;

    private const PAGE_H = 841.89;

    public function __invoke(OrgChartPdfLayout $layout): Response
    {
        // Carta ini membawa nama penuh setiap kakitangan. Butang yang
        // disorok bukan sekatan; gate disemak di sini.
        $this->authorize('manage-org-chart');

        $nodes = OrgNode::orderBy('sort_order')->get();
        $links = OrgLink::all();

        /*
         * Kawasan kandungan antara kepala dan kaki.
         *
         * Halaman bersaiz TETAP dan carta diskalakan untuk muat. Versi
         * pertama menetapkan halaman kepada saiz kanvas, menghasilkan PDF
         * bersaiz pelik yang setiap pencetak tafsir berbeza — sesetengah
         * memotong tepi tanpa amaran, dan pemotongan itu senyap.
         */
        $area = ['x' => 44.0, 'y' => 116.0, 'w' => 1102.0, 'h' => 664.0];

        $pdf = Pdf::loadView('pdf.org-chart', [
            'layout' => $layout->build($nodes, $links, $area),
            'logo' => $this->logo(),
            'effective' => now()->translatedFormat('F Y'),
        ])->setPaper([0, 0, self::PAGE_W, self::PAGE_H]);

        return $pdf->download('carta-organisasi-dbena-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * Logo sebagai data-URI.
     *
     * DomPDF menolak laluan fail di luar chroot dan gagal SENYAP: imej
     * hilang, tiada ralat dilaporkan, dan PDF kelihatan hampir betul
     * sehingga seseorang bertanya di mana logonya. Membenamkannya
     * membuang keseluruhan kelas kegagalan itu.
     */
    private function logo(): ?string
    {
        $path = public_path('images/logo-dbena.png');

        if (! is_readable($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
    }
}
