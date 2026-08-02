<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ReportPeriod;
use App\Services\ComprehensiveReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ComprehensiveReportPdfController extends Controller
{
    public function __invoke(Request $request, ComprehensiveReportService $reports): Response
    {
        $period = ReportPeriod::tryFrom((string) $request->query('tempoh')) ?? ReportPeriod::Monthly;
        $year = max(2023, min(2035, (int) $request->integer('tahun', (int) now()->year)));
        $month = max(1, min(12, (int) $request->integer('bulan', (int) now()->month)));
        $week = $period->isWeekly() ? max(1, min(4, (int) $request->integer('minggu', 1))) : null;
        $serviceKey = $request->string('servis')->value() ?: null;

        try {
            $data = $reports->build($period, $year, $month, $week, $serviceKey);

            $pdf = Pdf::loadView('pdf.comprehensive-report', [
                'data' => $data,
                'logo' => $this->logo(),
            ])->setPaper('a4', 'portrait');
        } catch (Throwable $e) {
            /*
             * PDF yang gagal memberi 500 KOSONG, dan halaman kosong tidak
             * memberitahu apa-apa kepada sesiapa. Ralat sebenar dicetak
             * supaya masalah boleh dinamakan dan bukan diteka — pelajaran
             * yang sudah dibayar sekali dalam projek ini.
             */
            return response(
                '<pre style="font: 13px monospace; padding: 20px">'
                .e($e->getMessage())."\n\n".e($e->getFile()).':'.$e->getLine()
                .'</pre>',
                500
            );
        }

        $nama = sprintf(
            'laporan-menyeluruh-%s-%s.pdf',
            $period->value,
            $period->isYearly() ? $year : sprintf('%04d-%02d', $year, $month),
        );

        return $pdf->download($nama);
    }

    /** Logo sebagai data-URI — DomPDF menolak laluan di luar chroot. */
    private function logo(): ?string
    {
        $path = public_path('images/logo-dbena.png');

        if (! is_readable($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
    }
}
