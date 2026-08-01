<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ReportPeriod;
use App\Models\Owner;
use App\Models\Service;
use App\Services\ExecutiveReportService;
use App\Services\OwnerReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class OwnerReportPdfController extends Controller
{
    public function __invoke(
        Request $request,
        OwnerReportService $reports,
        ExecutiveReportService $executive,
    ): Response {
        $period = ReportPeriod::tryFrom((string) $request->query('tempoh')) ?? ReportPeriod::Monthly;
        $year = max(2000, min(2100, (int) $request->integer('tahun', (int) now()->year)));
        $month = max(1, min(12, (int) $request->integer('bulan', (int) now()->month)));
        $week = $period->isWeekly() ? max(1, min(4, (int) $request->integer('minggu', 1))) : null;

        $serviceKey = $request->string('servis')->value() ?: null;
        $service = $serviceKey ? Service::where('key', $serviceKey)->first() : null;

        $ownerId = $request->integer('pemilik') ?: null;
        $owner = $ownerId ? Owner::find($ownerId) : null;

        $report = $reports->build($period, $year, $month, $week, $service?->id, $owner?->id);

        /*
         * Nama pemilik masuk ke dalam nama fail. Laporan ini diserahkan
         * kepada orang perseorangan, dan lima fail bernama
         * "laporan-pemilik-monthly-ogos-2026.pdf" dalam satu folder muat
         * turun tidak dapat dibezakan langsung.
         */
        $filename = sprintf(
            'laporan-%s-%s-%s.pdf',
            $owner ? str($owner->name)->slug()->value() : 'semua-pemilik',
            $period->value,
            str($report['periodLabel'])->slug()->value()
        );

        /*
         * Kegagalan penjanaan PDF memberi skrin 500 kosong, dan mencari
         * sebabnya bermakna menggali log pelayan. Di sini ia dilaporkan
         * terus kepada orang yang menekan butang itu.
         *
         * Laluan ini sudah di sebalik auth, jadi butiran hanya dilihat oleh
         * pengguna yang telah log masuk — bukan orang awam. Jejak penuh
         * kekal dalam log; yang dipaparkan hanya baris yang menamakan
         * puncanya.
         */
        try {
            return Pdf::loadView('pdf.owner-report', [
                'report' => $report,
                'exec' => $executive->build($report),
                'user' => $request->user(),
            ])
                ->setPaper('a4', 'portrait')
                ->setOption(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans'])
                ->download($filename);
        } catch (Throwable $e) {
            report($e);

            return response(
                __('owner_report.pdf_failed')."\n\n"
                .$e->getMessage()."\n\n"
                .basename($e->getFile()).':'.$e->getLine(),
                500
            )->header('Content-Type', 'text/plain; charset=UTF-8');
        }
    }
}
