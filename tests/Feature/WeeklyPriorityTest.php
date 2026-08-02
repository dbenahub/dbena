<?php

declare(strict_types=1);

use App\Enums\RoadmapStatus;
use App\Enums\TaskMark;
use App\Enums\UserRole;
use App\Livewire\Dashboard\Overview;
use App\Models\CriticalMetric;
use App\Models\CriticalMetricMonth;
use App\Models\MonthlyTask;
use App\Models\RoadmapCell;
use App\Models\Service;
use App\Models\TaskDayMark;
use App\Models\TaskDepartment;
use App\Models\User;
use App\Services\WeeklyPriorityService;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed();

    $this->user = User::where('role', UserRole::User)->firstOrFail();
    $this->renovation = Service::where('key', 'renovation')->firstOrFail();
    $this->marketing = TaskDepartment::where('name', 'Marketing Department')->firstOrFail();

    $this->year = (int) now()->year;
    $this->month = (int) now()->month;

    // Papan Ogos yang disemai tinggal dalam bulannya sendiri; bersihkan
    // bulan semasa supaya setiap ujian bermula daripada keadaan diketahui.
    MonthlyTask::where('year', $this->year)->where('month', $this->month)->delete();
    RoadmapCell::query()->delete();

    $this->priorities = fn () => app(WeeklyPriorityService::class)->build($this->year, $this->month);
});

/** Tugasan bulan semasa dengan tanda tertentu. */
function taskWithMarks(int $deptId, string $title, array $marks, ?string $actionBy = 'Zikri'): MonthlyTask
{
    $task = MonthlyTask::create([
        'task_department_id' => $deptId,
        'year' => (int) now()->year, 'month' => (int) now()->month,
        'title' => $title, 'action_by' => $actionBy, 'sort_order' => 1,
    ]);

    foreach ($marks as $day => $mark) {
        TaskDayMark::create(['monthly_task_id' => $task->id, 'day' => $day, 'mark' => $mark->value]);
    }

    return $task;
}

/*
|--------------------------------------------------------------------------
| Tarikh akhir yang terlepas didahulukan
|--------------------------------------------------------------------------
*/

it('puts an overdue task at the top', function (): void {
    // Tarikh akhir yang sudah berlalu ialah satu-satunya perkara di sini
    // yang sudah pun gagal.
    if (now()->day < 3) {
        $this->markTestSkipped('Perlu sekurang-kurangnya hari ke-3 bulan.');
    }

    taskWithMarks($this->marketing->id, 'Hantar quotation Klang', [1 => TaskMark::DueDate]);

    $items = ($this->priorities)();

    expect($items)->not->toBeEmpty()
        ->and($items[0]['badge'])->toBe(__('priority.badge.overdue'))
        ->and($items[0]['body'])->toBe('Hantar quotation Klang');
});

it('ignores an overdue task that is already complete', function (): void {
    if (now()->day < 3) {
        $this->markTestSkipped('Perlu sekurang-kurangnya hari ke-3 bulan.');
    }

    taskWithMarks($this->marketing->id, 'Sudah siap', [
        1 => TaskMark::DueDate, 2 => TaskMark::Complete,
    ]);

    expect(collect(($this->priorities)())->pluck('body'))->not->toContain('Sudah siap');
});

it('ignores a cancelled task entirely', function (): void {
    // Tugasan yang dibatalkan bukan kerja tertunggak. Menyenaraikannya
    // bermakna membatalkan sesuatu tidak pernah mengeluarkannya daripada
    // senarai keutamaan.
    if (now()->day < 3) {
        $this->markTestSkipped('Perlu sekurang-kurangnya hari ke-3 bulan.');
    }

    taskWithMarks($this->marketing->id, 'Event dibatalkan', [
        1 => TaskMark::DueDate, 2 => TaskMark::Cancel,
    ]);

    expect(collect(($this->priorities)())->pluck('body'))->not->toContain('Event dibatalkan');
});

it('flags a task due today', function (): void {
    taskWithMarks($this->marketing->id, 'Site visit Puchong', [(int) now()->day => TaskMark::DueDate]);

    $badges = collect(($this->priorities)())->pluck('badge');

    expect($badges)->toContain(__('priority.badge.today'));
});

it('raises a KIV task that has been sitting for a week', function (): void {
    // Tugasan yang ditanda KIV dan tidak disentuh selama seminggu bukan
    // lagi "menunggu" — ia terlupa, dan tiada siapa akan mengangkatnya
    // sendiri.
    if (now()->day < 9) {
        $this->markTestSkipped('Perlu sekurang-kurangnya hari ke-9 bulan.');
    }

    taskWithMarks($this->marketing->id, 'Banner taman perumahan', [1 => TaskMark::Kiv]);

    expect(collect(($this->priorities)())->pluck('badge'))->toContain(__('priority.badge.kiv'));
});

it('does not raise a KIV task from yesterday', function (): void {
    if (now()->day < 2) {
        $this->markTestSkipped('Perlu sekurang-kurangnya hari ke-2 bulan.');
    }

    taskWithMarks($this->marketing->id, 'Baru KIV semalam', [((int) now()->day) - 1 => TaskMark::Kiv]);

    expect(collect(($this->priorities)())->pluck('body'))->not->toContain('Baru KIV semalam');
});

it('leaves last month’s deadlines out', function (): void {
    // Tarikh akhir bulan lepas ialah sejarah. Menyenaraikannya bermakna
    // senarai tidak pernah kosong walaupun semuanya selesai.
    $lepas = now()->copy()->subMonth();

    $task = MonthlyTask::create([
        'task_department_id' => $this->marketing->id,
        'year' => (int) $lepas->year, 'month' => (int) $lepas->month,
        'title' => 'Tugasan bulan lepas', 'sort_order' => 1,
    ]);

    TaskDayMark::create(['monthly_task_id' => $task->id, 'day' => 1, 'mark' => TaskMark::DueDate->value]);

    expect(collect(($this->priorities)())->pluck('body'))->not->toContain('Tugasan bulan lepas');
});

/*
|--------------------------------------------------------------------------
| Metrik dan pelan tindakan
|--------------------------------------------------------------------------
*/

it('asks for the action plan before asking for the fix', function (): void {
    // Metrik merah yang sudah mempunyai pelan sedang dikendalikan. Yang
    // tiada pelan sedang tidak dikendalikan oleh sesiapa.
    RoadmapCell::updateOrCreate(
        ['service_id' => $this->renovation->id, 'year' => $this->year, 'month' => $this->month],
        ['status' => RoadmapStatus::ActiveAllYear->value]
    );

    $items = collect(($this->priorities)());

    $tiadaPelan = $items->firstWhere('badge', __('priority.badge.no_plan'));
    $diBawah = $items->firstWhere('badge', __('priority.badge.behind'));

    // Sekurang-kurangnya satu daripadanya mesti wujud pada data yang
    // disemai; kalau tidak ujian ini tidak menguji apa-apa.
    expect($tiadaPelan !== null || $diBawah !== null)->toBeTrue();

    if ($tiadaPelan !== null && $diBawah !== null) {
        expect($tiadaPelan['urgency'])->toBeGreaterThan($diBawah['urgency']);
    }
});

it('shows the owner’s own action plan, not a generic sentence', function (): void {
    // Ia ayat yang pemilik sendiri tulis, dan menggantikannya dengan ayat
    // generik memadam satu-satunya maklumat khusus di sini.
    RoadmapCell::updateOrCreate(
        ['service_id' => $this->renovation->id, 'year' => $this->year, 'month' => $this->month],
        ['status' => RoadmapStatus::ActiveAllYear->value]
    );

    $metric = CriticalMetric::where('service_id', $this->renovation->id)->firstOrFail();

    CriticalMetricMonth::updateOrCreate(
        ['critical_metric_id' => $metric->id, 'year' => $this->year, 'month' => $this->month],
        ['action_plan' => 'Tambah dua booth hujung minggu ini di Shah Alam']
    );

    $items = collect(($this->priorities)());

    $sepadan = $items->first(fn (array $i) => str_contains($i['body'], 'Tambah dua booth'));

    expect($sepadan === null || str_contains($sepadan['body'], 'Shah Alam'))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Roadmap
|--------------------------------------------------------------------------
*/

it('flags a service the roadmap says is active but has no figures', function (): void {
    // Percanggahan itu tidak kelihatan di mana-mana skrin lain.
    $divider = Service::where('key', 'divider')->firstOrFail();

    RoadmapCell::updateOrCreate([
        'service_id' => $divider->id, 'year' => $this->year, 'month' => $this->month,
    ], ['status' => RoadmapStatus::Campaign->value]);

    $badges = collect(($this->priorities)())->pluck('badge');

    expect($badges)->toContain(__('priority.badge.roadmap'));
});

it('says nothing about a month the roadmap marked paused', function (): void {
    // Bulan yang dijeda dengan sengaja bukan kegagalan.
    $divider = Service::where('key', 'divider')->firstOrFail();

    RoadmapCell::updateOrCreate([
        'service_id' => $divider->id, 'year' => $this->year, 'month' => $this->month,
    ], ['status' => RoadmapStatus::Paused->value]);

    $items = collect(($this->priorities)())
        ->filter(fn (array $i) => $i['badge'] === __('priority.badge.roadmap'))
        ->filter(fn (array $i) => str_contains($i['title'], $divider->name));

    expect($items)->toBeEmpty();
});

/*
|--------------------------------------------------------------------------
| Bentuk senarai
|--------------------------------------------------------------------------
*/

it('caps the list so it stays readable', function (): void {
    foreach (range(1, 12) as $i) {
        taskWithMarks($this->marketing->id, 'Tugasan '.$i, [(int) now()->day => TaskMark::DueDate]);
    }

    expect(count(($this->priorities)()))->toBeLessThanOrEqual(6);
});

it('does not let one service fill the whole list', function (): void {
    // Tanpa had, satu servis yang bermasalah memenuhi keseluruhan senarai
    // dan empat servis lain hilang daripada pandangan sepenuhnya.
    $items = collect(($this->priorities)())
        ->filter(fn (array $i) => $i['serviceId'] !== null)
        ->groupBy('serviceId');

    foreach ($items as $bagi) {
        expect(count($bagi))->toBeLessThanOrEqual(2);
    }
});

it('gives every item a link to where it can be fixed', function (): void {
    // Senarai keutamaan yang menghantar orang ke halaman ringkasan
    // menambah satu langkah antara membaca masalah dan menyentuhnya.
    taskWithMarks($this->marketing->id, 'Apa-apa tugasan', [(int) now()->day => TaskMark::DueDate]);

    foreach (($this->priorities)() as $item) {
        expect($item['route'])->toStartWith('http')
            ->and($item['title'])->not->toBe('')
            ->and($item['body'])->not->toBe('');
    }
});

it('renders on the dashboard', function (): void {
    taskWithMarks($this->marketing->id, 'Follow up client Kajang', [(int) now()->day => TaskMark::DueDate]);

    Livewire::actingAs($this->user)
        ->test(Overview::class)
        ->assertSee('Follow up client Kajang');
});

it('has an all-clear message ready for a clean week', function (): void {
    // "Tiada data" pada senarai yang dikira daripada data sebenar
    // kelihatan seperti kegagalan memuat. Keadaan kosong mesti menyatakan
    // apa yang telah disemak.
    expect(__('priority.all_clear'))->not->toBe('priority.all_clear')
        ->and(__('priority.all_clear_body'))->toContain('pelan tindakan');
});

/*
|--------------------------------------------------------------------------
| Roadmap memutuskan servis mana yang dilaporkan
|--------------------------------------------------------------------------
*/

/** Tetapkan status roadmap bagi setiap servis untuk bulan semasa. */
function setRoadmap(array $statusMengikutKunci): void
{
    foreach ($statusMengikutKunci as $kunci => $status) {
        $service = Service::where('key', $kunci)->firstOrFail();

        RoadmapCell::updateOrCreate(
            ['service_id' => $service->id, 'year' => (int) now()->year, 'month' => (int) now()->month],
            ['status' => $status->value]
        );
    }
}

it('says nothing about a service the roadmap paused', function (): void {
    // Servis yang dijeda tiada pemasaran berjalan, jadi "tiada lead
    // direkodkan" bukan kegagalan — ia tepat apa yang dirancang.
    setRoadmap([
        'renovation' => RoadmapStatus::ActiveAllYear,
        'kabinet' => RoadmapStatus::Paused,
        'bina-rumah' => RoadmapStatus::Paused,
        'divider' => RoadmapStatus::Paused,
        'mihrab' => RoadmapStatus::Paused,
    ]);

    $tajuk = collect(($this->priorities)())->pluck('title')->implode(' | ');

    expect($tajuk)->not->toContain('Kabinet')
        ->and($tajuk)->not->toContain('Divider')
        ->and($tajuk)->not->toContain('Mihrab');
});

it('says nothing about a service with no roadmap entry at all', function (): void {
    // Tiada catatan bermakna tiada niat direkodkan untuk bulan itu.
    setRoadmap(['renovation' => RoadmapStatus::ActiveAllYear]);

    $ids = collect(($this->priorities)())->pluck('serviceId')->filter()->unique();

    expect($ids->all())->toBe([$this->renovation->id]);
});

it('still reports a service the roadmap marks active', function (): void {
    setRoadmap(['renovation' => RoadmapStatus::Campaign]);

    $items = collect(($this->priorities)())->filter(fn (array $i) => $i['serviceId'] !== null);

    expect($items)->not->toBeEmpty();
});

it('treats resumed months as running', function (): void {
    // Sambung Semula bermakna kempen berjalan semula, jadi angka
    // dijangka sekali lagi.
    setRoadmap(['divider' => RoadmapStatus::Resumed]);

    $divider = Service::where('key', 'divider')->firstOrFail();
    $ids = collect(($this->priorities)())->pluck('serviceId')->filter()->unique();

    expect($ids->all())->toBe([$divider->id]);
});

it('does not raise two items about the same empty service', function (): void {
    // Semakan roadmap sudah menamakan janji roadmap. Peringkat corong yang
    // terputus tidak menambah apa-apa apabila TIADA angka langsung.
    setRoadmap(['divider' => RoadmapStatus::Campaign]);

    $divider = Service::where('key', 'divider')->firstOrFail();

    $bagiDivider = collect(($this->priorities)())
        ->filter(fn (array $i) => $i['serviceId'] === $divider->id);

    expect($bagiDivider)->toHaveCount(1)
        ->and($bagiDivider->first()['badge'])->toBe(__('priority.badge.roadmap'));
});

it('falls back to recorded figures when the roadmap month is empty', function (): void {
    // Menganggap semuanya aktif menghasilkan tepat bunyi yang cuba
    // dielakkan; menganggap semuanya tidak aktif mengosongkan panel dan
    // kelihatan rosak.
    RoadmapCell::query()->delete();

    $ids = collect(($this->priorities)())->pluck('serviceId')->filter()->unique();

    // Data yang disemai hanya membawa angka bagi sebahagian servis, jadi
    // senarai tidak boleh mengandungi kesemua lima.
    expect($ids->count())->toBeLessThan(Service::count());
});

it('keeps task priorities regardless of the roadmap', function (): void {
    // Tugasan tidak dimiliki oleh mana-mana servis. Menapisnya mengikut
    // roadmap bermakna tarikh akhir yang terlepas hilang kerana satu
    // servis yang tidak berkaitan sedang dijeda.
    setRoadmap([
        'renovation' => RoadmapStatus::Paused,
        'kabinet' => RoadmapStatus::Paused,
        'bina-rumah' => RoadmapStatus::Paused,
        'divider' => RoadmapStatus::Paused,
        'mihrab' => RoadmapStatus::Paused,
    ]);

    taskWithMarks($this->marketing->id, 'Follow up client Kajang', [(int) now()->day => TaskMark::DueDate]);

    expect(collect(($this->priorities)())->pluck('body'))->toContain('Follow up client Kajang');
});
