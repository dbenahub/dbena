<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Enums\TaskMark;
use App\Models\MonthlyTask;
use App\Models\Owner;
use App\Models\TaskBoardNote;
use App\Models\TaskDayMark;
use App\Models\TaskDepartment;
use App\Services\AuditLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Monthly Task Planning.
 *
 * Boleh disunting oleh SEMUA pengguna. Papan ini dikemas kini secara
 * langsung semasa mesyuarat mingguan — seorang menaip sambil semua
 * melihat — dan mengehadkannya kepada Admin bermakna mesyuarat berhenti
 * setiap kali orang yang salah sedang memegang papan kekunci.
 *
 * Memadam kekal Admin: ia satu-satunya tindakan di sini yang tidak boleh
 * dibuat asal.
 */
#[Layout('components.layouts.app')]
class TaskPlanner extends Component
{
    #[Url(as: 'tahun')]
    public int $year;

    #[Url(as: 'bulan')]
    public int $month;

    /** Tugasan yang sedang disunting dalam baris. */
    public ?int $editingId = null;

    public string $editTitle = '';

    public string $editActionBy = '';

    public string $editMonitorBy = '';

    public string $editRemark = '';

    /** Borang tambah tugasan, satu setiap jabatan. */
    public ?int $addingTo = null;

    public string $newTitle = '';

    public string $newActionBy = '';

    public string $newMonitorBy = '';

    // Panel bawah.
    public string $priorities = '';

    public string $notes = '';

    public string $preparedBy = '';

    public function mount(): void
    {
        $this->year = (int) now()->year;
        $this->month = (int) now()->month;
        $this->loadBoard();
    }

    private function board(): TaskBoardNote
    {
        return TaskBoardNote::firstOrCreate(['year' => $this->year, 'month' => $this->month]);
    }

    private function loadBoard(): void
    {
        $board = TaskBoardNote::where('year', $this->year)->where('month', $this->month)->first();

        $this->priorities = implode("\n", $board?->priorities ?? []);
        $this->notes = implode("\n", $board?->notes ?? []);
        $this->preparedBy = (string) ($board?->prepared_by ?? '');
    }

    public function goToMonth(int $year, int $month): void
    {
        // Julat dihadkan supaya URL yang dikarang tangan tidak menghasilkan
        // bulan ke-47 dan jadual kosong yang kelihatan seperti pepijat.
        $this->year = max(2023, min(2035, $year));
        $this->month = max(1, min(12, $month));

        $this->editingId = null;
        $this->addingTo = null;
        $this->loadBoard();
    }

    public function shiftMonth(int $delta): void
    {
        $tarikh = Carbon::create($this->year, $this->month, 1)->addMonths($delta);

        $this->goToMonth((int) $tarikh->year, (int) $tarikh->month);
    }

    /*
    |--------------------------------------------------------------------------
    | Tugasan
    |--------------------------------------------------------------------------
    */

    public function startAdd(int $departmentId): void
    {
        $this->addingTo = $departmentId;
        $this->newTitle = '';
        $this->newActionBy = '';
        $this->newMonitorBy = '';
    }

    public function cancelAdd(): void
    {
        $this->addingTo = null;
    }

    public function addTask(AuditLogger $audit): void
    {
        $tajuk = trim($this->newTitle);

        if ($this->addingTo === null || $tajuk === '') {
            // Tugasan tanpa tajuk ialah baris kosong yang mengambil nombor
            // BIL dan tidak boleh dicari. Ditolak secara senyap dan bukan
            // dicipta.
            return;
        }

        $task = MonthlyTask::create([
            'task_department_id' => $this->addingTo,
            'year' => $this->year,
            'month' => $this->month,
            'title' => $tajuk,
            'action_by' => trim($this->newActionBy) ?: null,
            'monitor_by' => trim($this->newMonitorBy) ?: null,
            'sort_order' => (int) MonthlyTask::where('task_department_id', $this->addingTo)
                ->where('year', $this->year)->where('month', $this->month)->max('sort_order') + 1,
            'created_by' => auth()->id(),
        ]);

        $audit->log('task.added', $task, $tajuk);

        // Borang kekal terbuka pada jabatan yang sama. Menutupnya selepas
        // setiap tugasan bermakna sepuluh klik tambahan untuk menaip
        // sepuluh tugasan, yang tepat apa yang berlaku dalam mesyuarat.
        $this->newTitle = '';
        $this->newActionBy = '';
        $this->newMonitorBy = '';
    }

    public function startEdit(int $taskId): void
    {
        $task = MonthlyTask::find($taskId);

        if ($task === null) {
            return;
        }

        $this->editingId = $task->id;
        $this->editTitle = (string) $task->title;
        $this->editActionBy = (string) $task->action_by;
        $this->editMonitorBy = (string) $task->monitor_by;
        $this->editRemark = (string) $task->remark;
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
    }

    public function saveTask(AuditLogger $audit): void
    {
        $task = $this->editingId ? MonthlyTask::find($this->editingId) : null;

        if ($task === null) {
            return;
        }

        $tajuk = trim($this->editTitle);

        if ($tajuk === '') {
            return;
        }

        $task->update([
            'title' => $tajuk,
            'action_by' => trim($this->editActionBy) ?: null,
            'monitor_by' => trim($this->editMonitorBy) ?: null,
            'remark' => trim($this->editRemark) ?: null,
        ]);

        $audit->log('task.updated', $task, $tajuk);

        $this->editingId = null;
    }

    public function deleteTask(int $taskId, AuditLogger $audit): void
    {
        // Memadam ialah satu-satunya tindakan di sini yang tidak boleh
        // dibuat asal, jadi ia kekal Admin.
        $this->authorize('delete-monthly-task');

        $task = MonthlyTask::find($taskId);

        if ($task === null) {
            return;
        }

        $tajuk = (string) $task->title;
        $task->delete();

        $audit->log('task.deleted', null, $tajuk);

        $this->editingId = null;
        $this->dispatch('dbena-toast', message: __('task.deleted'));
    }

    /*
    |--------------------------------------------------------------------------
    | Tanda hari
    |--------------------------------------------------------------------------
    */

    /**
     * Tetapkan atau kosongkan satu petak hari.
     *
     * Rentetan kosong bermakna kosongkan. Menyimpannya sebagai nilai enum
     * palsu bermakna papan tidak boleh membezakan "belum dirancang"
     * daripada "dirancang kemudian dibatalkan".
     */
    public function setMark(int $taskId, int $day, string $mark): void
    {
        $task = MonthlyTask::find($taskId);

        if ($task === null || $day < 1 || $day > $this->daysInMonth()) {
            return;
        }

        if ($mark === '') {
            TaskDayMark::where('monthly_task_id', $taskId)->where('day', $day)->delete();

            return;
        }

        $nilai = TaskMark::tryFrom($mark);

        if ($nilai === null) {
            return;
        }

        TaskDayMark::updateOrCreate(
            ['monthly_task_id' => $taskId, 'day' => $day],
            ['mark' => $nilai->value]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Panel bawah & salin bulan
    |--------------------------------------------------------------------------
    */

    public function saveBoard(): void
    {
        $baris = fn (string $teks) => collect(preg_split('/\r?\n/', $teks))
            ->map(fn (string $l) => trim($l))
            ->filter()
            ->values()
            ->all();

        $this->board()->update([
            'prepared_by' => trim($this->preparedBy) ?: null,
            'prepared_on' => now()->toDateString(),
            'priorities' => $baris($this->priorities),
            'notes' => $baris($this->notes),
        ]);

        $this->dispatch('dbena-toast', message: __('task.board_saved'));
    }

    /**
     * Salin tugasan bulan lepas TANPA tanda hari.
     *
     * Mesyuarat bulanan hampir sentiasa bermula daripada senarai bulan
     * lepas dengan beberapa pindaan. Membawa tanda bersamanya bermakna
     * papan baharu dibuka dengan tugasan yang sudah bertanda "Complete"
     * pada hari yang belum tiba.
     */
    public function copyPreviousMonth(AuditLogger $audit): void
    {
        $lepas = Carbon::create($this->year, $this->month, 1)->subMonth();

        $sumber = MonthlyTask::where('year', $lepas->year)
            ->where('month', $lepas->month)
            ->orderBy('sort_order')
            ->get();

        if ($sumber->isEmpty()) {
            $this->dispatch('dbena-toast',
                message: __('task.nothing_to_copy', ['month' => $lepas->translatedFormat('F Y')]),
                variant: 'error');

            return;
        }

        DB::transaction(function () use ($sumber): void {
            foreach ($sumber as $task) {
                MonthlyTask::create([
                    'task_department_id' => $task->task_department_id,
                    'year' => $this->year,
                    'month' => $this->month,
                    'title' => $task->title,
                    'action_by' => $task->action_by,
                    'monitor_by' => $task->monitor_by,
                    'sort_order' => $task->sort_order,
                    'created_by' => auth()->id(),
                ]);
            }
        });

        $audit->log('task.copied_month', null, $lepas->format('Y-m'));

        $this->dispatch('dbena-toast', message: __('task.copied', [
            'count' => $sumber->count(),
            'month' => $lepas->translatedFormat('F Y'),
        ]));
    }

    private function daysInMonth(): int
    {
        return (int) Carbon::create($this->year, $this->month, 1)->daysInMonth;
    }

    public function render(): View
    {
        $tasks = MonthlyTask::with('marks')
            ->where('year', $this->year)
            ->where('month', $this->month)
            ->orderBy('sort_order')
            ->get();

        $byDepartment = $tasks->groupBy('task_department_id');

        /*
         * Ringkasan dikira daripada tanda, bukan disimpan.
         *
         * Kaunter yang disimpan menyimpang daripada papan sebaik sahaja
         * satu petak diubah tanpa melalui laluan yang mengemas kininya —
         * dan nombor yang bercanggah dengan jadual di bawahnya lebih
         * teruk daripada tiada nombor.
         */
        $ringkasan = $this->summary($tasks);

        return view('livewire.dashboard.task-planner', [
            'departments' => TaskDepartment::where('active', true)->orderBy('sort_order')->get(),
            'tasksByDepartment' => $byDepartment,
            'days' => range(1, $this->daysInMonth()),
            'marks' => TaskMark::cases(),
            'summary' => $ringkasan,
            'monthLabel' => Carbon::create($this->year, $this->month, 1)->translatedFormat('F Y'),
            'today' => now()->year === $this->year && now()->month === $this->month
                ? (int) now()->day
                : null,
            'weekDays' => $this->currentWeekDays(),
            'board' => TaskBoardNote::where('year', $this->year)->where('month', $this->month)->first(),
            'owners' => Owner::orderBy('name')->pluck('name'),
        ])->layoutData([
            'pageTitle' => __('task.title'),
            'pageSubtitle' => Carbon::create($this->year, $this->month, 1)->translatedFormat('F Y'),
        ]);
    }

    /**
     * Hari dalam minggu semasa — disorot untuk mesyuarat mingguan.
     *
     * Papan mempunyai tiga puluh satu lajur sempit. Tanpa sorotan, mencari
     * "minggu ini" bermakna mengira ke seberang meja mesyuarat, dan
     * seseorang sentiasa tersalah lajur.
     *
     * @return array<int, int>
     */
    private function currentWeekDays(): array
    {
        if (now()->year !== $this->year || now()->month !== $this->month) {
            return [];
        }

        $mula = now()->startOfWeek();
        $tamat = now()->endOfWeek();
        $hari = [];

        for ($d = $mula->copy(); $d->lte($tamat); $d->addDay()) {
            if ($d->month === $this->month && $d->year === $this->year) {
                $hari[] = (int) $d->day;
            }
        }

        return $hari;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, MonthlyTask>  $tasks
     * @return array<string, mixed>
     */
    private function summary(\Illuminate\Support\Collection $tasks): array
    {
        $jumlah = $tasks->count();
        $siap = 0;
        $batal = 0;
        $tunggu = 0;
        $berjalan = 0;

        foreach ($tasks as $task) {
            $tanda = $task->marks->pluck('mark');

            // Keutamaan penting: satu tugasan yang dibatalkan tetapi
            // pernah ditanda Complete ialah dibatalkan. Mengira setiap
            // tanda secara berasingan menghasilkan jumlah melebihi
            // bilangan tugasan sebenar.
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

        $dikira = $jumlah - $batal;

        return [
            'total' => $jumlah,
            'inProgress' => $berjalan,
            'cancelled' => $batal,
            'completed' => $siap,
            'pending' => $tunggu,
            // Tugasan yang dibatalkan dikeluarkan daripada penyebut.
            // Membiarkannya bermakna membatalkan tugasan menurunkan
            // peratusan pasukan, yang menghukum keputusan yang betul.
            'focus' => $dikira > 0 ? (int) round($siap / $dikira * 100) : 0,
        ];
    }
}
