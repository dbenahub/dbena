<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TaskCalendarService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TaskCalendarPdfController extends Controller
{
    /** A3 landskap dalam titik. */
    private const PAGE_W = 1190.55;

    private const PAGE_H = 841.89;

    public function __invoke(Request $request, TaskCalendarService $calendar): Response
    {
        $year = max(2023, min(2035, (int) $request->integer('tahun', (int) now()->year)));
        $month = max(1, min(12, (int) $request->integer('bulan', (int) now()->month)));
        $pic = $request->string('pic')->value() ?: null;

        $data = $calendar->build($year, $month, $pic);

        $pdf = Pdf::loadView('pdf.task-calendar', [
            'cal' => $data,
            'pic' => $pic,
            'logo' => $this->logo(),
        ])->setPaper([0, 0, self::PAGE_W, self::PAGE_H]);

        return $pdf->download(sprintf('task-calendar-%04d-%02d.pdf', $year, $month));
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
