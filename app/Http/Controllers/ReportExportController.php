<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Service;
use App\Services\DashboardMetricsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * PEMBETULAN isu #9 — prototaip hanya memaparkan toast palsu
 * ("Laporan sedang dieksport...") tanpa menjana sebarang fail.
 *
 * Di sini CSV sebenar dialirkan, dengan BOM UTF-8 supaya aksara
 * Bahasa Malaysia dipaparkan betul dalam Microsoft Excel.
 */
class ReportExportController extends Controller
{
    public function __invoke(Request $request, DashboardMetricsService $metrics): StreamedResponse
    {
        $year = (int) $request->integer('tahun', (int) now()->year);
        $month = (int) $request->integer('bulan', (int) now()->month);
        $serviceKey = $request->string('servis')->value() ?: null;

        $selected = $serviceKey ? Service::where('key', $serviceKey)->first() : null;
        $services = $selected ? collect([$selected]) : Service::orderBy('sort_order')->get();

        $yearFactor = $metrics->yearFactor($year);
        $monthName = __('calendar.months_full')[$month - 1];
        $filename = sprintf('%s-%d-%02d.csv', __('laporan.csv.filename'), $year, $month);

        return response()->streamDownload(function () use ($services, $metrics, $year, $month, $yearFactor, $monthName) {
            $out = fopen('php://output', 'wb');

            // BOM UTF-8 — wajib untuk Excel memaparkan aksara BM dengan betul.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [__('laporan.csv.period'), "{$monthName} {$year}"]);
            fputcsv($out, [__('laporan.csv.generated'), now()->translatedFormat('d M Y, H:i')]);
            fputcsv($out, [__('laporan.csv.generated_by'), auth()->user()?->name ?? '—']);
            fputcsv($out, []);

            fputcsv($out, [
                __('laporan.csv.service'),
                __('laporan.csv.sales'),
                __('laporan.csv.target'),
                __('laporan.csv.achievement'),
                __('laporan.csv.status'),
            ]);

            $totalActual = 0.0;
            $totalTarget = 0.0;

            foreach ($services as $service) {
                $actual = $metrics->sumMetricActual(['revenue_sales'], $year, $month, $service->id) * $yearFactor;
                $target = (float) $service->monthly_target * $yearFactor;
                $pct = $target > 0 ? $actual / $target * 100 : 0.0;

                $totalActual += $actual;
                $totalTarget += $target;

                fputcsv($out, [
                    $service->name,
                    number_format($actual, 2, '.', ''),
                    number_format($target, 2, '.', ''),
                    number_format($pct, 1, '.', ''),
                    $metrics->calculateServiceStatus($pct)->label(),
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, [
                'TOTAL',
                number_format($totalActual, 2, '.', ''),
                number_format($totalTarget, 2, '.', ''),
                $totalTarget > 0 ? number_format($totalActual / $totalTarget * 100, 1, '.', '') : '0.0',
                '',
            ]);

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
