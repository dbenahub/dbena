<?php

declare(strict_types=1);

use App\Enums\TaskMark;
use App\Enums\UserRole;
use App\Livewire\Admin\TaskDepartmentManager;
use App\Livewire\Dashboard\TaskPlanner;
use App\Models\MonthlyTask;
use App\Models\TaskBoardNote;
use App\Models\TaskDayMark;
use App\Models\TaskDepartment;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed();

    $this->admin = User::where('role', UserRole::Admin)->firstOrFail();
    $this->user = User::where('role', UserRole::User)->firstOrFail();

    $this->sales = TaskDepartment::where('name', 'Marketing Department')->firstOrFail();

    $this->year = (int) now()->year;
    $this->month = (int) now()->month;

    $this->task = MonthlyTask::create([
        'task_department_id' => $this->sales->id,
        'year' => $this->year, 'month' => $this->month,
        'title' => 'Buka car booth event Renovation',
        'action_by' => 'Zikri', 'monitor_by' => 'Nizam', 'sort_order' => 1,
    ]);
});

/*
|--------------------------------------------------------------------------
| Semua pengguna boleh mengemas kini
|--------------------------------------------------------------------------
*/

it('lets a plain user add a task', function (): void {
    // Papan dikemas kini secara langsung semasa mesyuarat mingguan.
    // Mengehadkannya kepada Admin bermakna mesyuarat berhenti setiap kali
    // orang yang salah sedang memegang papan kekunci.
    Livewire::actingAs($this->user)
        ->test(TaskPlanner::class)
        ->call('startAdd', $this->sales->id)
        ->set('newTitle', 'Joint booth 3 hari di Putrajaya')
        ->set('newActionBy', 'Zikri')
        ->call('addTask');

    expect(MonthlyTask::where('title', 'Joint booth 3 hari di Putrajaya')->exists())->toBeTrue();
});

it('keeps the add form open after saving', function (): void {
    // Menutupnya bermakna sepuluh klik tambahan untuk menaip sepuluh
    // tugasan, yang tepat apa yang berlaku dalam mesyuarat.
    $component = Livewire::actingAs($this->user)
        ->test(TaskPlanner::class)
        ->call('startAdd', $this->sales->id)
        ->set('newTitle', 'Tugasan A')
        ->call('addTask');

    expect($component->get('addingTo'))->toBe($this->sales->id)
        ->and($component->get('newTitle'))->toBe('');
});

it('refuses a task with no title', function (): void {
    // Baris kosong mengambil nombor BIL dan tidak boleh dicari.
    $sebelum = MonthlyTask::count();

    Livewire::actingAs($this->user)
        ->test(TaskPlanner::class)
        ->call('startAdd', $this->sales->id)
        ->set('newTitle', '   ')
        ->call('addTask');

    expect(MonthlyTask::count())->toBe($sebelum);
});

it('lets a plain user edit a task', function (): void {
    Livewire::actingAs($this->user)
        ->test(TaskPlanner::class)
        ->call('startEdit', $this->task->id)
        ->set('editTitle', 'Tajuk dibetulkan')
        ->set('editRemark', 'Task Complete')
        ->call('saveTask');

    expect($this->task->fresh()->title)->toBe('Tajuk dibetulkan')
        ->and($this->task->fresh()->remark)->toBe('Task Complete');
});

it('keeps deleting to admins only', function (): void {
    // Memadam ialah satu-satunya tindakan di sini yang tidak boleh dibuat
    // asal.
    Livewire::actingAs($this->user)
        ->test(TaskPlanner::class)
        ->call('deleteTask', $this->task->id)
        ->assertForbidden();

    expect(MonthlyTask::find($this->task->id))->not->toBeNull();
});

it('lets an admin delete a task', function (): void {
    Livewire::actingAs($this->admin)
        ->test(TaskPlanner::class)
        ->call('deleteTask', $this->task->id);

    expect(MonthlyTask::find($this->task->id))->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Tanda hari
|--------------------------------------------------------------------------
*/

it('marks a day', function (): void {
    Livewire::actingAs($this->user)
        ->test(TaskPlanner::class)
        ->call('setMark', $this->task->id, 7, TaskMark::Complete->value);

    expect(TaskDayMark::where('monthly_task_id', $this->task->id)->where('day', 7)->firstOrFail()->mark)
        ->toBe(TaskMark::Complete);
});

it('replaces a mark instead of stacking a second one', function (): void {
    // Dua baris untuk hari yang sama menyebabkan petak berkelip antara dua
    // warna, dan tiada siapa dapat tahu yang mana benar.
    $component = Livewire::actingAs($this->user)->test(TaskPlanner::class);

    $component->call('setMark', $this->task->id, 7, TaskMark::Planning->value);
    $component->call('setMark', $this->task->id, 7, TaskMark::Complete->value);

    expect(TaskDayMark::where('monthly_task_id', $this->task->id)->where('day', 7)->count())->toBe(1)
        ->and(TaskDayMark::where('monthly_task_id', $this->task->id)->where('day', 7)->first()->mark)
        ->toBe(TaskMark::Complete);
});

it('clears a mark with an empty value', function (): void {
    // Menyimpan "kosong" sebagai nilai enum palsu bermakna papan tidak
    // dapat membezakan "belum dirancang" daripada "dirancang kemudian
    // dibatalkan".
    $component = Livewire::actingAs($this->user)->test(TaskPlanner::class);

    $component->call('setMark', $this->task->id, 7, TaskMark::Planning->value);
    $component->call('setMark', $this->task->id, 7, '');

    expect(TaskDayMark::where('monthly_task_id', $this->task->id)->where('day', 7)->exists())->toBeFalse();
});

it('refuses a day outside the month', function (): void {
    // Februari tiada 31 haribulan. Menyimpannya menghasilkan tanda yang
    // tidak pernah dipaparkan dan tidak boleh dibuang.
    Livewire::actingAs($this->user)
        ->test(TaskPlanner::class)
        ->call('setMark', $this->task->id, 45, TaskMark::Planning->value);

    expect(TaskDayMark::where('monthly_task_id', $this->task->id)->count())->toBe(0);
});

it('refuses an unknown mark', function (): void {
    Livewire::actingAs($this->user)
        ->test(TaskPlanner::class)
        ->call('setMark', $this->task->id, 5, 'bukan-tanda');

    expect(TaskDayMark::where('monthly_task_id', $this->task->id)->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Ringkasan bulanan
|--------------------------------------------------------------------------
*/

it('counts each task once, not once per mark', function (): void {
    // Mengira setiap tanda secara berasingan menghasilkan jumlah melebihi
    // bilangan tugasan sebenar — nombor yang mustahil dan yang tiada siapa
    // percaya lagi selepas melihatnya sekali.
    foreach ([3, 4, 5] as $hari) {
        TaskDayMark::create([
            'monthly_task_id' => $this->task->id, 'day' => $hari,
            'mark' => TaskMark::Complete->value,
        ]);
    }

    $summary = Livewire::actingAs($this->user)->test(TaskPlanner::class)->viewData('summary');

    expect($summary['completed'])->toBe(1)
        ->and($summary['total'])->toBe(1);
});

it('treats a cancelled task as cancelled even if it was once complete', function (): void {
    TaskDayMark::create(['monthly_task_id' => $this->task->id, 'day' => 3, 'mark' => TaskMark::Complete->value]);
    TaskDayMark::create(['monthly_task_id' => $this->task->id, 'day' => 9, 'mark' => TaskMark::Cancel->value]);

    $summary = Livewire::actingAs($this->user)->test(TaskPlanner::class)->viewData('summary');

    expect($summary['cancelled'])->toBe(1)
        ->and($summary['completed'])->toBe(0);
});

it('leaves cancelled tasks out of the focus percentage', function (): void {
    // Membiarkannya bermakna membatalkan tugasan menurunkan peratusan
    // pasukan, yang menghukum keputusan yang betul.
    $batal = MonthlyTask::create([
        'task_department_id' => $this->sales->id, 'year' => $this->year,
        'month' => $this->month, 'title' => 'Event dibatalkan', 'sort_order' => 2,
    ]);

    TaskDayMark::create(['monthly_task_id' => $this->task->id, 'day' => 3, 'mark' => TaskMark::Complete->value]);
    TaskDayMark::create(['monthly_task_id' => $batal->id, 'day' => 4, 'mark' => TaskMark::Cancel->value]);

    $summary = Livewire::actingAs($this->user)->test(TaskPlanner::class)->viewData('summary');

    // 1 siap daripada 1 yang dikira = 100%, bukan 50%.
    expect($summary['focus'])->toBe(100);
});

it('does not divide by zero on an empty month', function (): void {
    MonthlyTask::query()->delete();

    $summary = Livewire::actingAs($this->user)->test(TaskPlanner::class)->viewData('summary');

    expect($summary['focus'])->toBe(0)
        ->and($summary['total'])->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Bulan
|--------------------------------------------------------------------------
*/

it('moves to the next month', function (): void {
    $component = Livewire::actingAs($this->user)
        ->test(TaskPlanner::class)
        ->call('shiftMonth', 1);

    $jangka = now()->copy()->addMonth();

    expect($component->get('month'))->toBe((int) $jangka->month)
        ->and($component->get('year'))->toBe((int) $jangka->year);
});

it('rolls the year over in December', function (): void {
    $component = Livewire::actingAs($this->user)
        ->test(TaskPlanner::class)
        ->call('goToMonth', 2026, 12)
        ->call('shiftMonth', 1);

    expect($component->get('year'))->toBe(2027)
        ->and($component->get('month'))->toBe(1);
});

it('refuses a hand-typed month outside the range', function (): void {
    // Bulan ke-47 menghasilkan jadual kosong yang kelihatan seperti
    // pepijat dan bukan seperti input yang tidak sah.
    $component = Livewire::actingAs($this->user)
        ->test(TaskPlanner::class)
        ->call('goToMonth', 9999, 47);

    expect($component->get('year'))->toBe(2035)
        ->and($component->get('month'))->toBe(12);
});

it('shows only the chosen month', function (): void {
    MonthlyTask::create([
        'task_department_id' => $this->sales->id,
        'year' => $this->year, 'month' => $this->month === 12 ? 1 : $this->month + 1,
        'title' => 'Tugasan bulan lain', 'sort_order' => 1,
    ]);

    Livewire::actingAs($this->user)
        ->test(TaskPlanner::class)
        ->assertSee('Buka car booth event Renovation')
        ->assertDontSee('Tugasan bulan lain');
});

it('copies last month without carrying the marks', function (): void {
    // Membawa tanda bersamanya bermakna papan baharu dibuka dengan
    // tugasan yang sudah bertanda Complete pada hari yang belum tiba.
    TaskDayMark::create(['monthly_task_id' => $this->task->id, 'day' => 5, 'mark' => TaskMark::Complete->value]);

    $depan = now()->copy()->addMonth();

    Livewire::actingAs($this->user)
        ->test(TaskPlanner::class)
        ->call('goToMonth', (int) $depan->year, (int) $depan->month)
        ->call('copyPreviousMonth');

    $salinan = MonthlyTask::where('year', $depan->year)->where('month', $depan->month)->firstOrFail();

    expect($salinan->title)->toBe($this->task->title)
        ->and($salinan->marks()->count())->toBe(0);
});

it('says so when there is nothing to copy', function (): void {
    $component = Livewire::actingAs($this->user)
        ->test(TaskPlanner::class)
        ->call('goToMonth', 2035, 6)
        ->call('copyPreviousMonth')
        ->assertDispatched('dbena-toast');

    expect(MonthlyTask::where('year', 2035)->where('month', 6)->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Panel papan
|--------------------------------------------------------------------------
*/

it('saves the priorities and notes, dropping blank lines', function (): void {
    // Poin bernombor kosong kelihatan seperti data yang hilang.
    Livewire::actingAs($this->user)
        ->test(TaskPlanner::class)
        ->set('priorities', "Increase leads\n\nConvert quotation\n")
        ->set('notes', "Kemas kini setiap hari\n")
        ->set('preparedBy', 'NIZAM')
        ->call('saveBoard');

    $board = TaskBoardNote::where('year', $this->year)->where('month', $this->month)->firstOrFail();

    expect($board->priorities)->toBe(['Increase leads', 'Convert quotation'])
        ->and($board->notes)->toBe(['Kemas kini setiap hari'])
        ->and($board->prepared_by)->toBe('NIZAM');
});

/*
|--------------------------------------------------------------------------
| Jabatan — Admin sahaja
|--------------------------------------------------------------------------
*/

it('keeps a plain user out of the department manager', function (): void {
    $this->actingAs($this->user)->get('/admin/task-departments')->assertForbidden();
});

it('lets an admin add a department', function (): void {
    Livewire::actingAs($this->admin)
        ->test(TaskDepartmentManager::class)
        ->set('newName', 'FINANCE DEPARTMENT')
        ->call('add');

    expect(TaskDepartment::where('name', 'FINANCE DEPARTMENT')->exists())->toBeTrue();
});

it('refuses to delete a department that still has tasks', function (): void {
    // Kekunci asing akan menggugurkan setiap tugasan bersamanya, termasuk
    // bulan lepas, dan rekod mesyuarat yang hilang tidak boleh dipulihkan.
    Livewire::actingAs($this->admin)
        ->test(TaskDepartmentManager::class)
        ->call('remove', $this->sales->id)
        ->assertDispatched('dbena-toast');

    expect(TaskDepartment::find($this->sales->id))->not->toBeNull()
        ->and(MonthlyTask::find($this->task->id))->not->toBeNull();
});

it('deletes an empty department', function (): void {
    $kosong = TaskDepartment::create(['name' => 'SEMENTARA', 'sort_order' => 9]);

    Livewire::actingAs($this->admin)
        ->test(TaskDepartmentManager::class)
        ->call('remove', $kosong->id);

    expect(TaskDepartment::find($kosong->id))->toBeNull();
});

it('hides an inactive department from the board', function (): void {
    $this->sales->update(['active' => false]);

    $departments = Livewire::actingAs($this->user)
        ->test(TaskPlanner::class)
        ->viewData('departments');

    expect($departments->pluck('id'))->not->toContain($this->sales->id);
});

/*
|--------------------------------------------------------------------------
| Eksport PDF
|--------------------------------------------------------------------------
*/

it('lets any signed-in user export the PDF', function (): void {
    // Papan itu sendiri boleh disunting oleh semua, jadi mencetaknya tidak
    // mendedahkan apa-apa yang mereka belum nampak.
    $response = $this->actingAs($this->user)
        ->get(route('task-planning.pdf', ['tahun' => $this->year, 'bulan' => $this->month]));

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('turns a guest away from the PDF', function (): void {
    $this->get(route('task-planning.pdf'))->assertRedirect(route('login'));
});

it('exports a February without overflowing the page', function (): void {
    // Lebar lajur dikira daripada bilangan hari sebenar. Lebar tetap
    // bermakna Februari meninggalkan jalur kosong dan bulan 31 hari
    // melimpah keluar halaman.
    $this->actingAs($this->user)
        ->get(route('task-planning.pdf', ['tahun' => 2027, 'bulan' => 2]))
        ->assertOk();
});

/*
|--------------------------------------------------------------------------
| Lima jabatan sebenar
|--------------------------------------------------------------------------
*/

it('seeds every department, including the empty ones', function (): void {
    // Jabatan yang hilang daripada papan bermakna tiada tempat untuk butang
    // "tambah tugasan", jadi tugasan pertama Design atau Contract tidak
    // boleh dimasukkan langsung — dan orang menulisnya di tempat lain.
    $nama = TaskDepartment::orderBy('sort_order')->pluck('name')->all();

    expect($nama)->toBe([
        'Marketing Department',
        'Design Department',
        'Management Department',
        'Contract Department',
        'Operation Department',
    ]);
});

it('shows a department with no tasks and still offers Add', function (): void {
    $design = TaskDepartment::where('name', 'Design Department')->firstOrFail();

    Livewire::actingAs($this->user)
        ->test(TaskPlanner::class)
        ->assertSee('Design Department')
        ->assertSee(__('task.add_task'))
        ->call('startAdd', $design->id)
        ->set('newTitle', 'Siapkan 3D view rumah Klang')
        ->call('addTask');

    expect(MonthlyTask::where('task_department_id', $design->id)->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Papan Ogos 2026 yang disemai
|--------------------------------------------------------------------------
*/

it('seeds the real August board', function (): void {
    // Menaip semula tujuh tugasan dan dua puluh tanda sebelum ciri ini
    // boleh dinilai ialah halangan yang menyebabkan ia tidak pernah
    // dinilai.
    $ogos = MonthlyTask::where('year', 2026)->where('month', 8)->get();

    expect($ogos)->toHaveCount(7)
        ->and($ogos->pluck('title'))->toContain('Join event nextworking BNI')
        ->and(TaskDayMark::whereIn('monthly_task_id', $ogos->pluck('id'))->count())
        ->toBeGreaterThan(15);
});

it('places the marks on the days the sheet shows', function (): void {
    $task = MonthlyTask::where('title', 'Joint booth 3 hari di Putrajaya')->firstOrFail();

    $hari = $task->marks->pluck('mark', 'day');

    expect($hari[18])->toBe(TaskMark::Planning)
        ->and($hari[19])->toBe(TaskMark::Planning)
        ->and($hari[20])->toBe(TaskMark::Planning);
});

it('does not duplicate the board when seeded twice', function (): void {
    // Menjalankan seeder dua kali tidak boleh menggandakan papan yang
    // sedang digunakan.
    $sebelum = MonthlyTask::where('year', 2026)->where('month', 8)->count();

    $this->seed(Database\Seeders\TaskPlanningExampleSeeder::class);

    expect(MonthlyTask::where('year', 2026)->where('month', 8)->count())->toBe($sebelum);
});

it('fills the priority and notes panels from the sheet', function (): void {
    $board = TaskBoardNote::where('year', 2026)->where('month', 8)->firstOrFail();

    expect($board->prepared_by)->toBe('NIZAM')
        ->and($board->priorities)->toContain('Increase leads & site visit')
        ->and($board->notes)->toHaveCount(3);
});

it('summarises the August board the way the sheet does', function (): void {
    // Enam tugasan Marketing + satu Operation. Satu dibatalkan, tiga siap,
    // satu KIV, dua dalam perancangan.
    $summary = Livewire::actingAs($this->user)
        ->test(TaskPlanner::class)
        ->call('goToMonth', 2026, 8)
        ->viewData('summary');

    expect($summary['total'])->toBe(7)
        ->and($summary['cancelled'])->toBe(1)
        ->and($summary['completed'])->toBe(3)
        ->and($summary['pending'])->toBe(1)
        ->and($summary['inProgress'])->toBe(2);
});
