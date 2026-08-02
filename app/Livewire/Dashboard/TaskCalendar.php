<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Enums\TaskMark;
use App\Models\MonthlyTask;
use App\Models\Owner;
use App\Models\TaskDayMark;
use App\Models\TaskDepartment;
use App\Services\AuditLogger;
use App\Services\TaskCalendarService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Task Calendar.
 *
 * Membaca papan Task Planning yang sama — tiada jadual acara berasingan.
 * Menandakan hari pada papan muncul di sini serta-merta, dan menambah
 * tugasan di sini muncul pada papan.
 */
#[Layout('components.layouts.app')]
class TaskCalendar extends Component
{
    #[Url(as: 'tahun')]
    public int $year;

    #[Url(as: 'bulan')]
    public int $month;

    #[Url(as: 'pic', except: '')]
    public string $pic = '';

    /** month | week | day */
    #[Url(as: 'papar')]
    public string $view = 'month';

    /** Hari yang dipilih untuk paparan Week dan Day. */
    public ?int $focusDay = null;

    // Borang tambah tugasan.
    public bool $showAdd = false;

    public string $newTitle = '';

    public ?int $newDepartment = null;

    public string $newActionBy = '';

    public string $newMonitorBy = '';

    public int $newDay = 1;

    public string $newTime = '';

    public string $newMark = 'planning';

    public function mount(): void
    {
        $this->year = (int) now()->year;
        $this->month = (int) now()->month;
        $this->focusDay = (int) now()->day;
    }

    private function daysInMonth(): int
    {
        return (int) Carbon::create($this->year, $this->month, 1)->daysInMonth;
    }

    public function goToMonth(int $year, int $month): void
    {
        $this->year = max(2023, min(2035, $year));
        $this->month = max(1, min(12, $month));

        // Hari fokus mesti sah dalam bulan baharu. 31 Januari menjadi 31
        // Februari apabila bulan bertukar, dan Carbon menolaknya.
        $this->focusDay = min($this->focusDay ?? 1, $this->daysInMonth());
    }

    public function shiftMonth(int $delta): void
    {
        $t = Carbon::create($this->year, $this->month, 1)->addMonths($delta);

        $this->goToMonth((int) $t->year, (int) $t->month);
    }

    /** Anak panah bergerak mengikut paparan semasa, bukan sentiasa mengikut bulan. */
    public function shift(int $delta): void
    {
        if ($this->view === 'month') {
            $this->shiftMonth($delta);

            return;
        }

        $langkah = $this->view === 'week' ? 7 : 1;

        $t = Carbon::create($this->year, $this->month, $this->focusDay ?? 1)->addDays($delta * $langkah);

        $this->year = (int) $t->year;
        $this->month = (int) $t->month;
        $this->focusDay = (int) $t->day;
    }

    public function today(): void
    {
        $this->year = (int) now()->year;
        $this->month = (int) now()->month;
        $this->focusDay = (int) now()->day;
    }

    public function setView(string $view): void
    {
        $this->view = in_array($view, ['month', 'week', 'day'], true) ? $view : 'month';
    }

    public function selectDay(int $year, int $month, int $day): void
    {
        $this->goToMonth($year, $month);
        $this->focusDay = max(1, min($this->daysInMonth(), $day));
    }

    public function filterPic(string $name): void
    {
        // Klik pada PIC yang sudah dipilih mengosongkan penapis. Tanpa itu,
        // satu-satunya cara kembali kepada "All Team" ialah dropdown, dan
        // orang menganggap penapis itu tersekat.
        $this->pic = $this->pic === $name ? '' : $name;
    }

    /*
    |--------------------------------------------------------------------------
    | Tambah tugasan
    |--------------------------------------------------------------------------
    */

    public function openAdd(?int $day = null): void
    {
        $this->showAdd = true;
        $this->newDay = max(1, min($this->daysInMonth(), $day ?? $this->focusDay ?? 1));
        $this->newTitle = '';
        $this->newTime = '';
        $this->newMark = TaskMark::Planning->value;
        $this->newDepartment ??= TaskDepartment::where('active', true)->orderBy('sort_order')->value('id');
    }

    public function closeAdd(): void
    {
        $this->showAdd = false;
    }

    public function addTask(AuditLogger $audit): void
    {
        $tajuk = trim($this->newTitle);

        if ($tajuk === '' || $this->newDepartment === null) {
            return;
        }

        $task = MonthlyTask::create([
            'task_department_id' => $this->newDepartment,
            'year' => $this->year,
            'month' => $this->month,
            'title' => $tajuk,
            'action_by' => trim($this->newActionBy) ?: null,
            'monitor_by' => trim($this->newMonitorBy) ?: null,
            'sort_order' => (int) MonthlyTask::where('task_department_id', $this->newDepartment)
                ->where('year', $this->year)->where('month', $this->month)->max('sort_order') + 1,
            'created_by' => auth()->id(),
        ]);

        TaskDayMark::create([
            'monthly_task_id' => $task->id,
            'day' => max(1, min($this->daysInMonth(), $this->newDay)),
            'mark' => (TaskMark::tryFrom($this->newMark) ?? TaskMark::Planning)->value,
            'start_time' => trim($this->newTime) !== '' ? $this->newTime.':00' : null,
        ]);

        $audit->log('task.added_from_calendar', $task, $tajuk);

        $this->showAdd = false;
        $this->newTitle = '';
        $this->dispatch('dbena-toast', message: __('calendar.added'));
    }

    public function render(TaskCalendarService $calendar): View
    {
        $data = $calendar->build($this->year, $this->month, $this->pic ?: null);

        $fokus = Carbon::create($this->year, $this->month, $this->focusDay ?? 1);

        return view('livewire.dashboard.task-calendar', [
            'cal' => $data,
            'marks' => TaskMark::cases(),
            'departments' => TaskDepartment::where('active', true)->orderBy('sort_order')->get(),
            'owners' => Owner::orderBy('name')->pluck('name'),
            'focusDate' => $fokus,
            'weekDays' => $this->weekRange($fokus),
            'miniGrid' => $data['grid'],
        ])->layoutData([
            'pageTitle' => __('calendar.title'),
            'pageSubtitle' => __('calendar.subtitle'),
        ]);
    }

    /**
     * Julat minggu bagi hari fokus — Isnin hingga Ahad.
     *
     * @return array<int, Carbon>
     */
    private function weekRange(Carbon $fokus): array
    {
        $mula = $fokus->copy()->startOfWeek(Carbon::MONDAY);

        return collect(range(0, 6))->map(fn (int $i) => $mula->copy()->addDays($i))->all();
    }
}
