<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TaskMark;
use App\Models\MonthlyTask;
use App\Models\TaskBoardNote;
use App\Models\TaskDepartment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class TaskPlannerPdfController extends Controller
{
    /** A3 landskap dalam titik. */
    private const PAGE_W = 1190.55;

    private const PAGE_H = 841.89;

    public function __invoke(Request $request): Response
    {
        $year = max(2023, min(2035, (int) $request->integer('tahun', (int) now()->year)));
        $month = max(1, min(12, (int) $request->integer('bulan', (int) now()->month)));

        $tarikh = Carbon::create($year, $month, 1);
        $days = range(1, (int) $tarikh->daysInMonth);

        $tasks = MonthlyTask::with('marks')
            ->where('year', $year)->where('month', $month)
            ->orderBy('sort_order')->get();

        /*
         * Lebar lajur hari dikira daripada bilangan hari SEBENAR bulan itu.
         *
         * Februari mempunyai 28 lajur dan Ogos 31. Lebar tetap bermakna
         * Februari meninggalkan jalur kosong di tepi kanan, dan bulan 31
         * hari melimpah keluar halaman — pemotongan yang senyap dan hanya
         * kelihatan kepada orang yang tugasannya jatuh pada 30 dan 31.
         */
        $lebarTeks = 300.0;   // BIL + TASK
        $lebarPic = 116.0;    // Action By + Monitor By
        $lebarRemark = 128.0;
        $lebarHari = (1102.0 - $lebarTeks - $lebarPic - $lebarRemark) / count($days);

        $pdf = Pdf::loadView('pdf.task-planner', [
            'departments' => TaskDepartment::where('active', true)->orderBy('sort_order')->get(),
            'tasksByDepartment' => $tasks->groupBy('task_department_id'),
            'days' => $days,
            'dayWidth' => round($lebarHari, 2),
            'marks' => TaskMark::cases(),
            'monthLabel' => mb_strtoupper($tarikh->translatedFormat('F Y')),
            'board' => TaskBoardNote::where('year', $year)->where('month', $month)->first(),
            'summary' => $this->summary($tasks),
            'logo' => $this->logo(),
        ])->setPaper([0, 0, self::PAGE_W, self::PAGE_H]);

        return $pdf->download(sprintf('task-planning-%04d-%02d.pdf', $year, $month));
    }

    /**
     * Ringkasan dikira di sini juga, daripada tanda yang sama.
     *
     * @param  Collection<int, MonthlyTask>  $tasks
     * @return array<string, int>
     */
    private function summary(Collection $tasks): array
    {
        $siap = $batal = $tunggu = $berjalan = 0;

        foreach ($tasks as $task) {
            $tanda = $task->marks->pluck('mark');

            // Keutamaan sama seperti skrin. Mengira setiap tanda secara
            // berasingan menghasilkan jumlah melebihi bilangan tugasan.
            if ($tanda->contains(fn (TaskMark $m) => $m->isCancelled())) {
                $batal++;
            } elseif ($tanda->contains(fn (TaskMark $m) => $m->isDone())) {
                $siap++;
            } elseif ($tanda->contains(fn (TaskMark $m) => $m->isPending())) {
                $tunggu++;
            } elseif ($tanda->isNotEmpty()) {
                $berjalan++;
            }
        }

        $dikira = $tasks->count() - $batal;

        return [
            'total' => $tasks->count(),
            'inProgress' => $berjalan,
            'cancelled' => $batal,
            'completed' => $siap,
            'pending' => $tunggu,
            'focus' => $dikira > 0 ? (int) round($siap / $dikira * 100) : 0,
        ];
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
