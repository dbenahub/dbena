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

/*
|--------------------------------------------------------------------------
| Hantar ke Google Calendar
|--------------------------------------------------------------------------
*/

it('shows the page title from the right language file', function (): void {
    // Kunci yang salah tidak menghempaskan apa-apa — Laravel memaparkan
    // kunci itu sendiri, jadi tajuk halaman berbunyi "calendar.title" dan
    // hanya kelihatan kepada orang yang membuka halaman itu.
    expect(__('calendar_task.title'))->not->toBe('calendar_task.title')
        ->and(__('calendar_task.subtitle'))->not->toBe('calendar_task.subtitle');
});

it('refuses to push with no calendar id', function (): void {
    App\Models\RoadmapPlan::query()->update(['calendar_id' => null]);

    Livewire\Livewire::actingAs($this->user)
        ->test(TaskCalendar::class)
        ->call('pushToGoogle')
        ->assertDispatched('dbena-toast');
});

it('creates an event and remembers its id', function (): void {
    // Tanpa menyimpan ID, setiap sync mencipta acara BAHARU dan kalendar
    // dipenuhi salinan tugasan yang sama.
    $task = calTask($this->marketing->id, 'Site visit Klang', [12 => TaskMark::Planning]);

    Illuminate\Support\Facades\Http::fake([
        'oauth2.googleapis.com/*' => Illuminate\Support\Facades\Http::response(['access_token' => 'ujian']),
        'www.googleapis.com/calendar/*' => Illuminate\Support\Facades\Http::response(['id' => 'evt_123']),
    ]);

    $hasil = app(App\Services\GoogleCalendarWriter::class)
        ->syncMonth('dbenagroup@gmail.com', 2026, 8);

    expect($hasil['ok'])->toBeTrue()
        ->and($task->marks()->first()->google_event_id)->toBe('evt_123');
});

it('updates instead of duplicating on a second push', function (): void {
    $task = calTask($this->marketing->id, 'Site visit Klang', [12 => TaskMark::Planning]);
    $task->marks()->update(['google_event_id' => 'evt_lama']);

    Illuminate\Support\Facades\Http::fake([
        'oauth2.googleapis.com/*' => Illuminate\Support\Facades\Http::response(['access_token' => 'ujian']),
        'www.googleapis.com/calendar/*' => Illuminate\Support\Facades\Http::response(['id' => 'evt_lama']),
    ]);

    $hasil = app(App\Services\GoogleCalendarWriter::class)
        ->syncMonth('dbenagroup@gmail.com', 2026, 8);

    expect($hasil['created'])->toBe(0)
        ->and($hasil['updated'])->toBeGreaterThan(0);
});

it('removes a cancelled task from the calendar', function (): void {
    // Kalendar yang memaparkan acara yang dibatalkan bermakna orang masih
    // pergi ke mesyuarat yang tidak berlaku.
    $task = calTask($this->marketing->id, 'Event dibatalkan', [12 => TaskMark::Cancel]);
    $task->marks()->update(['google_event_id' => 'evt_batal']);

    Illuminate\Support\Facades\Http::fake([
        'oauth2.googleapis.com/*' => Illuminate\Support\Facades\Http::response(['access_token' => 'ujian']),
        'www.googleapis.com/calendar/*' => Illuminate\Support\Facades\Http::response([], 204),
    ]);

    $hasil = app(App\Services\GoogleCalendarWriter::class)
        ->syncMonth('dbenagroup@gmail.com', 2026, 8);

    expect($hasil['deleted'])->toBe(1)
        ->and($task->marks()->first()->google_event_id)->toBeNull();
});

it('names the write permission when Google refuses', function (): void {
    // Kegagalan paling biasa ialah menyangka perkongsian BACA sudah
    // memadai. Mesej yang hanya berkata "403" menghantar admin membetulkan
    // perkara yang sudah betul.
    calTask($this->marketing->id, 'Apa-apa', [12 => TaskMark::Planning]);

    Illuminate\Support\Facades\Http::fake([
        'oauth2.googleapis.com/*' => Illuminate\Support\Facades\Http::response(['access_token' => 'ujian']),
        'www.googleapis.com/calendar/*' => Illuminate\Support\Facades\Http::response([
            'error' => ['message' => 'Forbidden', 'errors' => [['reason' => 'forbidden']]],
        ], 403),
    ]);

    $hasil = app(App\Services\GoogleCalendarWriter::class)
        ->syncMonth('dbenagroup@gmail.com', 2026, 8);

    expect($hasil['ok'])->toBeFalse()
        ->and($hasil['message'])->toContain('Make changes to events');
});

it('names the disabled API instead of blaming sharing', function (): void {
    calTask($this->marketing->id, 'Apa-apa', [12 => TaskMark::Planning]);

    Illuminate\Support\Facades\Http::fake([
        'oauth2.googleapis.com/*' => Illuminate\Support\Facades\Http::response(['access_token' => 'ujian']),
        'www.googleapis.com/calendar/*' => Illuminate\Support\Facades\Http::response([
            'error' => [
                'message' => 'Google Calendar API has not been used in project 1 before or it is disabled.',
                'errors' => [['reason' => 'accessNotConfigured']],
            ],
        ], 403),
    ]);

    $hasil = app(App\Services\GoogleCalendarWriter::class)
        ->syncMonth('dbenagroup@gmail.com', 2026, 8);

    expect($hasil['message'])->toContain('ENABLE')
        ->and($hasil['message'])->not->toContain('Make changes to events');
});

it('sends an all-day event ending the next day', function (): void {
    // Google menganggap julat sepanjang hari sebagai separa terbuka, jadi
    // tarikh tamat yang sama menghasilkan acara sifar panjang yang tidak
    // muncul langsung dalam paparan bulan.
    calTask($this->marketing->id, 'Sepanjang hari', [12 => TaskMark::Planning], 'Zikri', null);

    Illuminate\Support\Facades\Http::fake([
        'oauth2.googleapis.com/*' => Illuminate\Support\Facades\Http::response(['access_token' => 'ujian']),
        'www.googleapis.com/calendar/*' => Illuminate\Support\Facades\Http::response(['id' => 'evt_1']),
    ]);

    app(App\Services\GoogleCalendarWriter::class)->syncMonth('dbenagroup@gmail.com', 2026, 8);

    Illuminate\Support\Facades\Http::assertSent(function ($request) {
        if (! str_contains($request->url(), '/events')) {
            return true;
        }

        $data = $request->data();

        return ($data['start']['date'] ?? null) !== ($data['end']['date'] ?? null);
    });
});

it('carries the status letter into the event title', function (): void {
    // Kalendar Google tidak memaparkan warna kepada semua peranti, dan
    // huruf itu berfungsi di mana-mana.
    calTask($this->marketing->id, 'Site visit', [12 => TaskMark::Complete]);

    Illuminate\Support\Facades\Http::fake([
        'oauth2.googleapis.com/*' => Illuminate\Support\Facades\Http::response(['access_token' => 'ujian']),
        'www.googleapis.com/calendar/*' => Illuminate\Support\Facades\Http::response(['id' => 'evt_1']),
    ]);

    app(App\Services\GoogleCalendarWriter::class)->syncMonth('dbenagroup@gmail.com', 2026, 8);

    Illuminate\Support\Facades\Http::assertSent(function ($request) {
        if (! str_contains($request->url(), '/events')) {
            return true;
        }

        return str_starts_with($request->data()['summary'] ?? '', '[C] ');
    });
});

it('asks for the write scope, not the read one', function (): void {
    // Token Google terikat kepada skop yang dimintanya. Token baca yang
    // digunakan untuk menulis ditolak dengan 403 yang kelihatan seperti
    // masalah perkongsian.
    $sumber = file_get_contents(app_path('Services/GoogleCalendarWriter.php'));

    // Skop penuh, bukan calendar.events: yang itu membenarkan penulisan
    // acara tetapi TIDAK membenarkan calendarList, dan tanpa calendarList
    // kita tidak boleh bertanya kepada Google apa kebenaran sebenar robot.
    expect($sumber)->toContain("auth/calendar'")
        ->and($sumber)->not->toContain('calendar.readonly');
});

it('derives the token cache key from the scope', function (): void {
    // Token Google terikat kepada skop yang dimintanya. Kunci TETAP
    // bermakna menukar skop dalam kod tidak membuang token lama — ia kekal
    // dalam cache sehingga tamat tempoh, dan setiap panggilan sehingga itu
    // gagal dengan "insufficient authentication scopes" terhadap kod yang
    // sebenarnya betul.
    foreach (['GoogleCalendarReader', 'GoogleCalendarWriter'] as $kelas) {
        $sumber = file_get_contents(app_path("Services/{$kelas}.php"));

        expect($sumber)->toContain('sha1(self::SCOPE)')
            ->and($sumber)->not->toContain('TOKEN_CACHE_KEY');
    }
});

it('gives the reader and the writer different token keys', function (): void {
    // Skop berbeza, jadi kunci yang diterbitkan mesti berbeza — kalau
    // tidak token baca digunakan untuk menulis.
    $kunci = [];

    foreach (['GoogleCalendarReader', 'GoogleCalendarWriter'] as $kelas) {
        $sumber = file_get_contents(app_path("Services/{$kelas}.php"));

        preg_match("/SCOPE = '([^']+)'/", $sumber, $m);

        $kunci[] = substr(sha1($m[1]), 0, 16);
    }

    expect($kunci[0])->not->toBe($kunci[1]);
});

it('retries once with a fresh token when the scope is rejected', function (): void {
    // Kegagalan ini sembuh sendiri selepas lima puluh minit, iaitu tepat
    // cukup lama untuk seseorang menghabiskan petang membetulkan
    // perkongsian kalendar yang tidak pernah rosak.
    $panggilan = 0;

    Illuminate\Support\Facades\Http::fake(function ($request) use (&$panggilan) {
        if (str_contains($request->url(), 'oauth2.googleapis.com')) {
            return Illuminate\Support\Facades\Http::response(['access_token' => 'ujian']);
        }

        if ($request->method() !== 'POST') {
            return Illuminate\Support\Facades\Http::response(['items' => []]);
        }

        $panggilan++;

        // Panggilan pertama menolak skop; kedua berjaya.
        if ($panggilan === 1) {
            return Illuminate\Support\Facades\Http::response([
                'error' => [
                    'message' => 'Request had insufficient authentication scopes.',
                    'errors' => [['reason' => 'insufficientPermissions']],
                ],
            ], 403);
        }

        return Illuminate\Support\Facades\Http::response([
            'id' => 'dbenagroup@gmail.com', 'summary' => 'DBENA', 'accessRole' => 'writer',
        ]);
    });

    $hasil = app(App\Services\GoogleCalendarWriter::class)->diagnose('dbenagroup@gmail.com');

    expect($panggilan)->toBe(2)
        ->and($hasil['reason'])->toBe('ready');
});

it('does not blame sharing for a scope problem', function (): void {
    // Bukan masalah perkongsian langsung. Token itu sendiri salah, dan
    // menyuruh admin membetulkan perkongsian membuang petang mereka.
    expect(__('calendar_task.google.bad_scope'))
        ->not->toContain('Make changes to events');
});

/*
|--------------------------------------------------------------------------
| Semakan kebenaran — fakta, bukan tekaan
|--------------------------------------------------------------------------
*/

/**
 * Palsukan calendarList.insert — panggilan yang menjawab "adakah robot
 * ini boleh menulis ke kalendar itu".
 */
function fakeRegister(array $body, int $status = 200, array $senarai = []): void
{
    Illuminate\Support\Facades\Http::fake(function ($request) use ($body, $status, $senarai) {
        if (str_contains($request->url(), 'oauth2.googleapis.com')) {
            return Illuminate\Support\Facades\Http::response(['access_token' => 'ujian']);
        }

        if ($request->method() === 'POST') {
            return Illuminate\Support\Facades\Http::response($body, $status);
        }

        return Illuminate\Support\Facades\Http::response(['items' => $senarai]);
    });
}

it('says no id when none is configured', function (): void {
    // Tiada panggilan Google langsung — tiada ID untuk diuji.
    Illuminate\Support\Facades\Http::fake();

    expect(app(App\Services\GoogleCalendarWriter::class)->diagnose('')['reason'])->toBe('no_id');
});

it('separates a disabled API from a permission problem', function (): void {
    Illuminate\Support\Facades\Http::fake([
        'oauth2.googleapis.com/*' => Illuminate\Support\Facades\Http::response(['access_token' => 'ujian']),
        '*users/me/calendarList*' => Illuminate\Support\Facades\Http::response([
            'error' => [
                'message' => 'Google Calendar API has not been used in project 1 before or it is disabled.',
                'errors' => [['reason' => 'accessNotConfigured']],
            ],
        ], 403),
    ]);

    expect(app(App\Services\GoogleCalendarWriter::class)->diagnose('dbenagroup@gmail.com')['reason'])
        ->toBe('api_disabled');
});

it('runs the check automatically when a push fails', function (): void {
    // Mesej yang meneka menghantar admin membetulkan perkara yang sudah
    // betul; jawapan Google disertakan tanpa perlu ditanya.
    calTask($this->marketing->id, 'Apa-apa', [12 => TaskMark::Planning]);

    Illuminate\Support\Facades\Http::fake([
        'oauth2.googleapis.com/*' => Illuminate\Support\Facades\Http::response(['access_token' => 'ujian']),
        '*users/me/calendarList*' => Illuminate\Support\Facades\Http::response([
            'id' => 'dbenagroup@gmail.com', 'summary' => 'DBENA', 'accessRole' => 'reader',
        ]),
        '*/events*' => Illuminate\Support\Facades\Http::response([
            'error' => ['message' => 'Forbidden', 'errors' => [['reason' => 'forbidden']]],
        ], 403),
    ]);

    $hasil = app(App\Services\GoogleCalendarWriter::class)
        ->syncMonth('dbenagroup@gmail.com', 2026, 8);

    expect($hasil['ok'])->toBeFalse()
        ->and($hasil['diagnosis']['reason'])->toBe('read_only');
});

it('shows the check panel on the page', function (): void {
    fakeRegister(['id' => 'dbenagroup@gmail.com', 'summary' => 'DBENA', 'accessRole' => 'reader']);

    App\Models\RoadmapPlan::forYear(2026)->update(['calendar_id' => 'dbenagroup@gmail.com']);

    Livewire\Livewire::actingAs($this->user)
        ->test(TaskCalendar::class)
        ->call('checkGoogle')
        ->assertSee(__('calendar_task.google.check_title'))
        ->assertSee(__('calendar_task.google.role_reader'));
});

it('tests the calendar directly instead of searching a list', function (): void {
    // Berkongsi kalendar dengan service account TIDAK menambahkannya ke
    // dalam senarai robot. Pengguna biasa menerima jemputan dan
    // menerimanya; robot tidak pernah menerima e-mel, jadi senarainya
    // kekal kosong walaupun akses sudah diberikan.
    //
    // Diagnosis pertama saya menganggap senarai kosong bermakna "belum
    // dikongsi", dan menghantar seseorang mengongsi semula kalendar yang
    // sudah dikongsi dengan betul.
    fakeRegister(['id' => 'dbenagroup@gmail.com', 'summary' => 'DBENA', 'accessRole' => 'writer'], 200, []);

    $hasil = app(App\Services\GoogleCalendarWriter::class)->diagnose('dbenagroup@gmail.com');

    expect($hasil['reason'])->toBe('ready')
        ->and($hasil['calendars'])->toBe([]);
});

it('says read only when Google reports the reader role', function (): void {
    fakeRegister(['id' => 'dbenagroup@gmail.com', 'summary' => 'DBENA', 'accessRole' => 'reader']);

    $hasil = app(App\Services\GoogleCalendarWriter::class)->diagnose('dbenagroup@gmail.com');

    expect($hasil['reason'])->toBe('read_only')
        ->and($hasil['target']['canWrite'])->toBeFalse();
});

it('treats owner as able to write', function (): void {
    fakeRegister(['id' => 'x@gmail.com', 'summary' => 'X', 'accessRole' => 'owner']);

    expect(app(App\Services\GoogleCalendarWriter::class)->diagnose('x@gmail.com')['reason'])->toBe('ready');
});

it('reports not shared only when Google returns 404', function (): void {
    fakeRegister(['error' => ['message' => 'Not Found']], 404);

    $hasil = app(App\Services\GoogleCalendarWriter::class)->diagnose('salah@gmail.com');

    expect($hasil['reason'])->toBe('not_shared');
});

it('names the configured id in the not-shared message', function (): void {
    // Dalam cabang "tidak dijumpai", sasaran adalah null secara takrifan —
    // jadi mesej memaparkan "—" dan bukan ID yang sebenarnya perlu
    // dibetulkan.
    fakeRegister(['error' => ['message' => 'Not Found']], 404);

    $hasil = app(App\Services\GoogleCalendarWriter::class)->diagnose('salah@gmail.com');

    expect($hasil['wanted'])->toBe('salah@gmail.com')
        ->and(__('calendar_task.google.reason.not_shared', ['id' => $hasil['wanted']]))
        ->toContain('salah@gmail.com');
});

it('registers the calendar so later checks are cheaper', function (): void {
    // calendarList.insert menjawab kedua-dua soalan sekali gus dan tidak
    // mencipta apa-apa dalam kalendar itu sendiri.
    fakeRegister(['id' => 'dbenagroup@gmail.com', 'summary' => 'DBENA', 'accessRole' => 'writer']);

    app(App\Services\GoogleCalendarWriter::class)->diagnose('dbenagroup@gmail.com');

    Illuminate\Support\Facades\Http::assertSent(
        fn ($request) => $request->method() !== 'POST'
            || ! str_contains($request->url(), 'calendarList')
            || ($request->data()['id'] ?? null) === 'dbenagroup@gmail.com'
    );
});
