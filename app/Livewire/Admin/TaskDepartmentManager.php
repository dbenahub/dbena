<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\MonthlyTask;
use App\Models\TaskDepartment;
use App\Services\AuditLogger;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Jabatan papan tugasan bulanan — Admin sahaja.
 */
#[Layout('components.layouts.app')]
class TaskDepartmentManager extends Component
{
    public string $newName = '';

    public string $newIcon = 'ph-megaphone';

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public function mount(): void
    {
        $this->authorize('manage-task-departments');
        $this->load();
    }

    private function load(): void
    {
        $this->rows = TaskDepartment::orderBy('sort_order')->get()
            ->map(fn (TaskDepartment $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'icon' => $d->icon,
                'sort_order' => $d->sort_order,
                'active' => $d->active,
            ])->all();
    }

    public function add(AuditLogger $audit): void
    {
        $this->authorize('manage-task-departments');

        $nama = trim($this->newName);

        if ($nama === '') {
            return;
        }

        $dept = TaskDepartment::create([
            'name' => $nama,
            'icon' => trim($this->newIcon) ?: 'ph-megaphone',
            'sort_order' => (int) TaskDepartment::max('sort_order') + 1,
        ]);

        $audit->log('task_department.added', $dept, $nama);

        $this->newName = '';
        $this->load();
        $this->dispatch('dbena-toast', message: __('task.admin.added'));
    }

    public function save(AuditLogger $audit): void
    {
        $this->authorize('manage-task-departments');

        foreach ($this->rows as $row) {
            $dept = TaskDepartment::find($row['id'] ?? 0);

            if ($dept === null) {
                continue;
            }

            $nama = trim((string) ($row['name'] ?? ''));

            // Jabatan tanpa nama menjadi tajuk bahagian kosong yang tiada
            // siapa dapat kaitkan dengan apa-apa.
            if ($nama === '') {
                continue;
            }

            $dept->update([
                'name' => $nama,
                'icon' => trim((string) ($row['icon'] ?? '')) ?: 'ph-megaphone',
                'sort_order' => max(0, (int) ($row['sort_order'] ?? 0)),
                'active' => (bool) ($row['active'] ?? true),
            ]);
        }

        $audit->log('task_department.saved', null, (string) count($this->rows));

        $this->load();
        $this->dispatch('dbena-toast', message: __('task.admin.saved'));
    }

    /**
     * Buang satu jabatan.
     *
     * DITOLAK apabila ia masih mempunyai tugasan. Kekunci asing akan
     * menggugurkan setiap tugasan bersamanya — termasuk bulan lepas — dan
     * rekod mesyuarat yang hilang tidak boleh dipulihkan daripada apa-apa
     * pada skrin ini. Menyahaktifkan menyembunyikannya tanpa memusnahkan
     * sejarah.
     */
    public function remove(int $id, AuditLogger $audit): void
    {
        $this->authorize('manage-task-departments');

        $dept = TaskDepartment::find($id);

        if ($dept === null) {
            return;
        }

        if (MonthlyTask::where('task_department_id', $id)->exists()) {
            $this->dispatch('dbena-toast',
                message: __('task.admin.delete_blocked'), variant: 'error');

            return;
        }

        $nama = $dept->name;
        $dept->delete();

        $audit->log('task_department.removed', null, $nama);

        $this->load();
        $this->dispatch('dbena-toast', message: __('task.admin.removed'));
    }

    public function render(): View
    {
        return view('livewire.admin.task-department-manager', [
            'counts' => MonthlyTask::selectRaw('task_department_id, count(*) as jumlah')
                ->groupBy('task_department_id')
                ->pluck('jumlah', 'task_department_id'),
        ])->layoutData([
            'pageTitle' => __('task.admin.title'),
            'pageSubtitle' => __('task.admin.note'),
        ]);
    }
}
