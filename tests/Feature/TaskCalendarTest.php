<?php

declare(strict_types=1);

use App\Enums\TaskMark;
use App\Enums\UserRole;
use App\Livewire\Dashboard\TaskCalendar;
use App\Models\MonthlyTask;
use App\Models\TaskDayMark;
use App\Models\TaskDepartment;
use App\Models\User;
use App\Services\TaskCalendarService;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    $this->seed();

    $this->user = User::where('role', UserRole::User)->firstOrFail();
    $this->marketing = TaskDepartment::where('name', 'Marketing Department')->firstOrFail();

    $this->year = 2026;
    $this->month = 8;

    $this->calendar = app(TaskCalendarService::class);
});

/** Tugasan Ogos 2026 dengan tanda hari. */
function calTask(int $deptId, string $title, array $marks, ?string $pic = 'Zikri', ?string $time = null): MonthlyTask
{
    $task = MonthlyTask::create([
        'task_department_id' => $deptId, 'year' => 2026, 'month' => 8,
        'title' => $title, 'action_by' => $pic, 'sort_order' => 99,
    ]);

    foreach ($marks as $day => $mark) {
        TaskDayMark::create([
            'monthly_task_id' => $task->id, 'day' => $day,
            'mark' => $mark->value, 'start_time' => $time,
        ]);
    }

    return $task;
}

/*
|--------------------------------------------------------------------------
| Kalendar sebenar, bukan jadual
|--------------------------------------------------------------------------
*/

it('starts every week on Monday and keeps weeks whole', function (): void {
    // Grid yang bermula pada 1 haribulan tanpa mengira hari apa ia jatuh
    // bukan kalendar; ia jadual, dan tiada siapa boleh membaca hujung
    // minggu daripadanya.
    $grid = $this->calendar->build($this->year, $this->month)['grid'];

    foreach ($grid as $minggu) {
        expect($minggu)->toHaveCount(7)
            ->and($minggu[0]['date']->dayOfWeekIso)->toBe(1)
            ->and($minggu[6]['date']->dayOfWeekIso)->toBe(7);
    }
});

it('puts 1 August 2026 on a Saturday, where the real calendar puts it', function (): void {
    $grid = $this->calendar->build(2026, 8)['grid'];

    $satu = collect($grid)->flatten(1)
        ->first(fn (array $s) => $s['inMonth'] && $s['day'] === 1);

    expect($satu['date']->translatedFormat('D'))->toBe(Carbon::create(2026, 8, 1)->translatedFormat('D'))
        ->and($satu['date']->dayOfWeekIso)->toBe(6);
});

it('carries leading and trailing days from the neighbouring months', function (): void {
    $grid = $this->calendar->build(2026, 8)['grid'];
    $semua = collect($grid)->flatten(1);

    expect($semua->where('inMonth', false))->not->toBeEmpty()
        ->and($semua->where('inMonth', true))->toHaveCount(31);
});

it('handles February in a leap year', function (): void {
    // 2028 ialah tahun lompat: 29 hari.
    $semua = collect($this->calendar->build(2028, 2)['grid'])->flatten(1);

    expect($semua->where('inMonth', true))->toHaveCount(29);
});

/*
|--------------------------------------------------------------------------
| Acara datang daripada Task Planning
|--------------------------------------------------------------------------
*/

it('turns every day mark into a calendar event', function (): void {
    // Tiada jadual acara berasingan. Menyimpannya dua kali bermakna
    // menandakan hari pada papan tidak muncul dalam kalendar sehingga
    // seseorang menyalinnya, dan salinan itu tidak akan pernah dibuat.
    calTask($this->marketing->id, 'Site visit Klang', [
        4 => TaskMark::Planning, 5 => TaskMark::Planning,
    ]);

    $events = $this->calendar->build(2026, 8)['events']
        ->where('title', 'Site visit Klang');

    expect($events)->toHaveCount(2)
        ->and($events->pluck('day')->sort()->values()->all())->toBe([4, 5]);
});

it('places the event on the day it was marked', function (): void {
    calTask($this->marketing->id, 'Booth Putrajaya', [19 => TaskMark::Planning]);

    $sel = collect($this->calendar->build(2026, 8)['grid'])->flatten(1)
        ->first(fn (array $s) => $s['inMonth'] && $s['day'] === 19);

    expect($sel['events']->pluck('title'))->toContain('Booth Putrajaya');
});

it('drops a mark on a day the month does not have', function (): void {
    // Februari tiada 30 haribulan. Membiarkannya menghempaskan Carbon.
    $task = MonthlyTask::create([
        'task_department_id' => $this->marketing->id, 'year' => 2027, 'month' => 2,
        'title' => 'Hari mustahil', 'sort_order' => 1,
    ]);

    TaskDayMark::create(['monthly_task_id' => $task->id, 'day' => 30, 'mark' => TaskMark::Planning->value]);

    expect($this->calendar->build(2027, 2)['events'])->toBeEmpty();
});

it('sorts all-day events before timed ones', function (): void {
    // Acara tanpa masa ialah "sepanjang hari"; meletakkannya selepas acara
    // 5 petang membaca seolah-olah ia berlaku lewat malam.
    calTask($this->marketing->id, 'Bermasa', [10 => TaskMark::Planning], 'Zikri', '17:00:00');
    calTask($this->marketing->id, 'Sepanjang hari', [10 => TaskMark::Planning], 'Zikri', null);

    $hari10 = $this->calendar->build(2026, 8)['events']->where('day', 10)->values();

    expect($hari10->first()['title'])->toBe('Sepanjang hari');
});

/*
|--------------------------------------------------------------------------
| Penapis pasukan
|--------------------------------------------------------------------------
*/

it('filters events down to one PIC', function (): void {
    calTask($this->marketing->id, 'Kerja Zikri', [6 => TaskMark::Planning], 'Zikri');
    calTask($this->marketing->id, 'Kerja Azhari', [6 => TaskMark::Planning], 'Azhari');

    $tajuk = $this->calendar->build(2026, 8, 'Azhari')['events']->pluck('title');

    expect($tajuk)->toContain('Kerja Azhari')
        ->and($tajuk)->not->toContain('Kerja Zikri');
});

it('keeps the full team list even when filtered', function (): void {
    // Penapis yang mengecilkan senarainya sendiri tidak boleh dibuka
    // semula tanpa memuat semula halaman.
    calTask($this->marketing->id, 'Kerja Zikri', [6 => TaskMark::Planning], 'Zikri');
    calTask($this->marketing->id, 'Kerja Azhari', [6 => TaskMark::Planning], 'Azhari');

    $team = collect($this->calendar->build(2026, 8, 'Azhari')['team'])->pluck('name');

    expect($team)->toContain('Zikri')->and($team)->toContain('Azhari');
});

it('gives the same person the same colour every time', function (): void {
    // Mengikut kedudukan bermakna Zikri bertukar warna sebaik seseorang
    // ditambah di atasnya, dan seluruh gunanya hilang.
    expect($this->calendar->picColor('Zikri'))->toBe($this->calendar->picColor('zikri  '))
        ->and($this->calendar->picColor('Zikri'))->not->toBe($this->calendar->picColor('Azhari'));
});

/*
|--------------------------------------------------------------------------
| Akan datang
|--------------------------------------------------------------------------
*/

it('never lists a past day under Upcoming', function (): void {
    // Menunjukkan acara lampau di bawah tajuk "Upcoming" ialah pembohongan
    // kecil yang menjadikan seluruh panel tidak boleh dipercayai.
    $lepas = now()->copy()->subMonth();

    $task = MonthlyTask::create([
        'task_department_id' => $this->marketing->id,
        'year' => (int) $lepas->year, 'month' => (int) $lepas->month,
        'title' => 'Sudah berlalu', 'sort_order' => 1,
    ]);

    TaskDayMark::create(['monthly_task_id' => $task->id, 'day' => 1, 'mark' => TaskMark::Planning->value]);

    $upcoming = $this->calendar->build((int) $lepas->year, (int) $lepas->month)['upcoming'];

    expect(collect($upcoming)->pluck('title'))->not->toContain('Sudah berlalu');
});

/*
|--------------------------------------------------------------------------
| Komponen
|--------------------------------------------------------------------------
*/

it('renders the calendar for a signed-in user', function (): void {
    Livewire\Livewire::actingAs($this->user)
        ->test(TaskCalendar::class)
        ->assertOk()
        ->assertSee(__('calendar_task.upcoming'));
});

it('switches between month, week and day', function (): void {
    $component = Livewire\Livewire::actingAs($this->user)->test(TaskCalendar::class);

    foreach (['week', 'day', 'month'] as $mod) {
        $component->call('setView', $mod)->assertSet('view', $mod);
    }
});

it('refuses an unknown view instead of rendering nothing', function (): void {
    // Nama paparan yang tidak sah menjadi @include yang tiada, iaitu ralat
    // maut dan bukan halaman kosong.
    Livewire\Livewire::actingAs($this->user)
        ->test(TaskCalendar::class)
        ->call('setView', 'tahun')
        ->assertSet('view', 'month');
});

it('moves by month, week or day depending on the view', function (): void {
    $component = Livewire\Livewire::actingAs($this->user)
        ->test(TaskCalendar::class)
        ->call('goToMonth', 2026, 8)
        ->call('selectDay', 2026, 8, 10);

    $component->call('setView', 'day')->call('shift', 1);
    expect($component->get('focusDay'))->toBe(11);

    $component->call('setView', 'week')->call('shift', 1);
    expect($component->get('focusDay'))->toBe(18);

    $component->call('setView', 'month')->call('shift', 1);
    expect($component->get('month'))->toBe(9);
});

it('keeps the focus day valid when the month changes', function (): void {
    // 31 Januari menjadi 31 Februari apabila bulan bertukar, dan Carbon
    // menolaknya.
    $component = Livewire\Livewire::actingAs($this->user)
        ->test(TaskCalendar::class)
        ->call('goToMonth', 2027, 1)
        ->call('selectDay', 2027, 1, 31)
        ->call('goToMonth', 2027, 2);

    expect($component->get('focusDay'))->toBe(28);
});

it('clears the filter when the same PIC is clicked twice', function (): void {
    // Tanpa itu, satu-satunya jalan kembali ialah dropdown, dan orang
    // menganggap penapis tersekat.
    $component = Livewire\Livewire::actingAs($this->user)
        ->test(TaskCalendar::class)
        ->call('filterPic', 'Zikri');

    expect($component->get('pic'))->toBe('Zikri');

    $component->call('filterPic', 'Zikri');

    expect($component->get('pic'))->toBe('');
});

it('adds a task from the calendar and it lands on the board', function (): void {
    // Menambah di sini muncul pada papan; ia data yang sama.
    Livewire\Livewire::actingAs($this->user)
        ->test(TaskCalendar::class)
        ->call('goToMonth', 2026, 8)
        ->call('openAdd', 12)
        ->set('newTitle', 'Meeting kontraktor')
        ->set('newDepartment', $this->marketing->id)
        ->set('newTime', '14:30')
        ->call('addTask');

    $task = MonthlyTask::where('title', 'Meeting kontraktor')->firstOrFail();

    expect($task->year)->toBe(2026)
        ->and($task->month)->toBe(8)
        ->and($task->marks->first()->day)->toBe(12)
        ->and($task->marks->first()->start_time)->toContain('14:30');
});

it('refuses a task with no title', function (): void {
    $sebelum = MonthlyTask::count();

    Livewire\Livewire::actingAs($this->user)
        ->test(TaskCalendar::class)
        ->call('openAdd')
        ->set('newTitle', '  ')
        ->call('addTask');

    expect(MonthlyTask::count())->toBe($sebelum);
});

/*
|--------------------------------------------------------------------------
| Eksport PDF
|--------------------------------------------------------------------------
*/

it('exports the calendar as a PDF', function (): void {
    $response = $this->actingAs($this->user)
        ->get(route('task-calendar.pdf', ['tahun' => 2026, 'bulan' => 8]));

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('exports a filtered calendar', function (): void {
    $this->actingAs($this->user)
        ->get(route('task-calendar.pdf', ['tahun' => 2026, 'bulan' => 8, 'pic' => 'Zikri']))
        ->assertOk();
});

it('turns a guest away', function (): void {
    $this->get(route('task-calendar'))->assertRedirect(route('login'));
    $this->get(route('task-calendar.pdf'))->assertRedirect(route('login'));
});
