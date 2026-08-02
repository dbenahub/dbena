<?php

declare(strict_types=1);

use App\Enums\RoadmapStatus;
use App\Enums\UserRole;
use App\Livewire\Admin\RoadmapEditor;
use App\Livewire\Dashboard\Overview;
use App\Models\RoadmapCell;
use App\Models\RoadmapPlan;
use App\Models\Service;
use App\Models\User;
use App\Services\GoogleCalendarReader;
use App\Services\RoadmapService;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed();

    $this->admin = User::where('role', UserRole::Admin)->firstOrFail();
    $this->user = User::where('role', UserRole::User)->firstOrFail();

    $this->renovation = Service::where('key', 'renovation')->firstOrFail();
    $this->divider = Service::where('key', 'divider')->firstOrFail();

    $this->tahun = (int) now()->year;

    // Renovation aktif sepanjang tahun; Divider mengikut corak kempen.
    foreach (range(1, 12) as $m) {
        RoadmapCell::create([
            'service_id' => $this->renovation->id, 'year' => $this->tahun,
            'month' => $m, 'status' => RoadmapStatus::ActiveAllYear,
        ]);

        RoadmapCell::create([
            'service_id' => $this->divider->id, 'year' => $this->tahun, 'month' => $m,
            'status' => match (true) {
                $m <= 3 => RoadmapStatus::Campaign,
                $m <= 8 => RoadmapStatus::Paused,
                default => RoadmapStatus::Resumed,
            },
        ]);
    }
});

/*
|--------------------------------------------------------------------------
| Sasaran dikira daripada bulan aktif
|--------------------------------------------------------------------------
*/

it('counts only active months toward the annual target', function (): void {
    // Mendarab sasaran bulanan dengan dua belas menghasilkan angka
    // tahunan yang tiada siapa pernah komited kepadanya — dan angka
    // itulah yang akan dibawa ke mesyuarat.
    $roadmap = app(RoadmapService::class)->build($this->tahun);
    $divider = collect($roadmap['rows'])->firstWhere('service.id', $this->divider->id);

    // Jan–Mac kempen + Sep–Dis sambung semula = 7 bulan aktif.
    expect($divider['activeMonths'])->toBe(7)
        ->and($divider['annualTarget'])->toBe($divider['monthlyTarget'] * 7);
});

it('gives a paused month no target at all', function (): void {
    $roadmap = app(RoadmapService::class)->build($this->tahun);
    $divider = collect($roadmap['rows'])->firstWhere('service.id', $this->divider->id);

    expect($divider['months'][5]['target'])->toBe(0.0)
        ->and($divider['months'][2]['target'])->toBe($divider['monthlyTarget']);
});

it('counts a full year of activity as twelve months', function (): void {
    $roadmap = app(RoadmapService::class)->build($this->tahun);
    $renovation = collect($roadmap['rows'])->firstWhere('service.id', $this->renovation->id);

    expect($renovation['activeMonths'])->toBe(12)
        ->and($renovation['allYear'])->toBeTrue();
});

it('does not merge a row that is only mostly active', function (): void {
    // Bar gabungan menyampaikan satu keputusan. Melukisnya untuk baris
    // yang sebenarnya berubah menyembunyikan perubahan itu.
    RoadmapCell::where('service_id', $this->renovation->id)
        ->where('month', 6)
        ->update(['status' => RoadmapStatus::Paused]);

    $roadmap = app(RoadmapService::class)->build($this->tahun);
    $renovation = collect($roadmap['rows'])->firstWhere('service.id', $this->renovation->id);

    expect($renovation['allYear'])->toBeFalse();
});

it('adds the services up into a company annual target', function (): void {
    $roadmap = app(RoadmapService::class)->build($this->tahun);

    $jumlah = collect($roadmap['rows'])->sum('annualTarget');

    expect((float) $roadmap['annualTarget'])->toBe((float) $jumlah);
});

it('splits the year into four quarters', function (): void {
    $roadmap = app(RoadmapService::class)->build($this->tahun);

    expect($roadmap['quarters'])->toHaveCount(4)
        ->and($roadmap['quarters'][0]['months'])->toBe([1, 2, 3])
        ->and($roadmap['quarters'][3]['months'])->toBe([10, 11, 12]);
});

it('shows an untouched year as empty rather than failing', function (): void {
    $roadmap = app(RoadmapService::class)->build(2099);

    expect($roadmap['annualTarget'])->toBe(0.0)
        ->and($roadmap['rows'])->toHaveCount(Service::count());
});

/*
|--------------------------------------------------------------------------
| Paparan pada Dashboard Utama
|--------------------------------------------------------------------------
*/

it('shows the roadmap on the main dashboard', function (): void {
    Livewire::actingAs($this->user)
        ->test(Overview::class)
        ->assertSee(__('roadmap.status.active_all_year'))
        ->assertSee(__('roadmap.legend'));
});

it('lets any role change the roadmap year', function (): void {
    // Melihat data dari sudut lain bukan suntingan. Mengunci pengguna
    // kepada satu tahun bermakna mereka bertanya kepada Admin untuk
    // soalan yang sepatutnya dijawab di skrin.
    $component = Livewire::actingAs($this->user)
        ->test(Overview::class)
        ->call('showRoadmapYear', $this->tahun + 1);

    expect($component->viewData('roadmap')['year'])->toBe($this->tahun + 1);
});

it('keeps the roadmap year apart from the dashboard year', function (): void {
    // Roadmap ialah dokumen perancangan. Mengikatnya kepada penapis tahun
    // dashboard bermakna menukar rancangan turut menukar setiap nombor
    // prestasi di halaman.
    $component = Livewire::actingAs($this->user)
        ->test(Overview::class)
        ->call('showRoadmapYear', $this->tahun + 2);

    expect($component->get('year'))->toBe($this->tahun)
        ->and($component->get('roadmapYear'))->toBe($this->tahun + 2);
});

it('refuses a hand-typed year far outside the range', function (): void {
    // Tahun 9999 menghasilkan dua belas sel kosong yang kelihatan seperti
    // bug dan bukan seperti input yang tidak sah.
    $component = Livewire::actingAs($this->user)
        ->test(Overview::class)
        ->call('showRoadmapYear', 9999);

    expect($component->get('roadmapYear'))->toBe(2035);
});

/*
|--------------------------------------------------------------------------
| Suntingan — Admin sahaja
|--------------------------------------------------------------------------
*/

it('keeps a plain user out of the roadmap editor', function (): void {
    $this->actingAs($this->user)->get('/admin/roadmap')->assertForbidden();
});

it('lets an admin into the roadmap editor', function (): void {
    $this->actingAs($this->admin)->get('/admin/roadmap')->assertOk();
});

it('refuses a plain user who calls the write method directly', function (): void {
    // Menyorok grid tidak menghalang panggilan Livewire terus. Aplikasi
    // ialah penulis di sini, jadi setiap kaedah penulis mesti menyemak
    // sendiri.
    Livewire::actingAs($this->user)
        ->test(RoadmapEditor::class)
        ->assertForbidden();
});

it('cycles a cell through the statuses in order', function (): void {
    $editor = Livewire::actingAs($this->admin)->test(RoadmapEditor::class);

    $editor->call('cycle', $this->divider->id, 5);

    // Paused → Resumed
    expect(RoadmapCell::where('service_id', $this->divider->id)
        ->where('year', $this->tahun)->where('month', 5)->firstOrFail()->status)
        ->toBe(RoadmapStatus::Resumed);
});

it('saves a cell immediately rather than waiting for a Save button', function (): void {
    // Grid enam puluh sel dengan butang Simpan bermakna admin menukar
    // lapan sel, tertutup tab, dan kehilangan kelapan-lapannya.
    Livewire::actingAs($this->admin)
        ->test(RoadmapEditor::class)
        ->call('cycle', $this->divider->id, 4);

    expect(RoadmapCell::where('service_id', $this->divider->id)
        ->where('month', 4)->where('year', $this->tahun)->exists())->toBeTrue();
});

it('does not create a duplicate cell on a fast double click', function (): void {
    $editor = Livewire::actingAs($this->admin)->test(RoadmapEditor::class);

    $editor->call('cycle', $this->divider->id, 6);
    $editor->call('cycle', $this->divider->id, 6);

    expect(RoadmapCell::where('service_id', $this->divider->id)
        ->where('year', $this->tahun)->where('month', 6)->count())->toBe(1);
});

it('fills a whole row in one action', function (): void {
    Livewire::actingAs($this->admin)
        ->test(RoadmapEditor::class)
        ->call('fillRow', $this->divider->id, RoadmapStatus::Campaign->value);

    expect(RoadmapCell::where('service_id', $this->divider->id)
        ->where('year', $this->tahun)
        ->where('status', RoadmapStatus::Campaign->value)
        ->count())->toBe(12);
});

it('copies last year as the starting point for next year', function (): void {
    // Perancangan tahun hadapan hampir sentiasa bermula sebagai tahun ini
    // dengan beberapa pindaan.
    Livewire::actingAs($this->admin)
        ->test(RoadmapEditor::class)
        ->call('changeYear', $this->tahun + 1)
        ->call('copyFromPreviousYear');

    expect(RoadmapCell::where('year', $this->tahun + 1)->count())
        ->toBe(RoadmapCell::where('year', $this->tahun)->count());
});

it('says so when there is nothing to copy', function (): void {
    Livewire::actingAs($this->admin)
        ->test(RoadmapEditor::class)
        ->call('changeYear', 2030)
        ->call('copyFromPreviousYear')
        ->assertDispatched('dbena-toast');

    expect(RoadmapCell::where('year', 2030)->count())->toBe(0);
});

it('saves the board text and strategy summary', function (): void {
    Livewire::actingAs($this->admin)
        ->test(RoadmapEditor::class)
        ->set('title', 'ROADMAP TAHUNAN SERVIS')
        ->set('summaryText', "Fokus utama: Renovation\n\nDivider fokus Jan–Mac\n")
        ->call('save');

    $plan = RoadmapPlan::where('year', $this->tahun)->firstOrFail();

    // Baris kosong dibuang — poin bernombor kosong kelihatan seperti data
    // yang hilang.
    expect($plan->title)->toBe('ROADMAP TAHUNAN SERVIS')
        ->and($plan->summary)->toBe(['Fokus utama: Renovation', 'Divider fokus Jan–Mac']);
});

/*
|--------------------------------------------------------------------------
| Google Calendar
|--------------------------------------------------------------------------
*/

it('shows the roadmap even when the calendar cannot be read', function (): void {
    // Grid ialah kandungan utama dan ia hidup dalam pangkalan data kita.
    // Membiarkan ralat Google menaik ke atas bermakna Dashboard Utama
    // memaparkan halaman ralat kerana seseorang membatalkan perkongsian.
    RoadmapPlan::forYear($this->tahun)->update(['calendar_id' => 'tiada@group.calendar.google.com']);

    $this->mock(GoogleCalendarReader::class)
        ->shouldReceive('eventsByMonth')
        ->andThrow(new RuntimeException('403 not shared'));

    $roadmap = app(RoadmapService::class)->build($this->tahun);

    expect($roadmap['calendarError'])->toContain('403')
        ->and($roadmap['rows'])->not->toBeEmpty();
});

it('does not call Google at all when no calendar is set', function (): void {
    $this->mock(GoogleCalendarReader::class)
        ->shouldNotReceive('eventsByMonth');

    $roadmap = app(RoadmapService::class)->build($this->tahun);

    expect($roadmap['events'])->toBe([])
        ->and($roadmap['eventCount'])->toBe(0);
});

it('groups calendar events by month', function (): void {
    RoadmapPlan::forYear($this->tahun)->update(['calendar_id' => 'dbena@group.calendar.google.com']);

    $this->mock(GoogleCalendarReader::class)
        ->shouldReceive('eventsByMonth')
        ->andReturn([
            3 => [['title' => 'Kempen Divider', 'start' => now(), 'allDay' => true, 'location' => null, 'url' => null]],
            9 => [['title' => 'Sambung Semula', 'start' => now(), 'allDay' => true, 'location' => null, 'url' => null]],
        ]);

    $roadmap = app(RoadmapService::class)->build($this->tahun);

    expect($roadmap['eventCount'])->toBe(2)
        ->and($roadmap['events'][3][0]['title'])->toBe('Kempen Divider');
});

it('reports a bad calendar id from the editor instead of failing quietly', function (): void {
    $this->mock(GoogleCalendarReader::class)
        ->shouldReceive('forget')->andReturnNull()
        ->shouldReceive('test')
        ->andReturn(['ok' => false, 'message' => 'Calendar not shared', 'count' => 0])
        ->shouldReceive('eventsByMonth')->andReturn([]);

    $component = Livewire::actingAs($this->admin)
        ->test(RoadmapEditor::class)
        ->set('calendarId', 'salah@group.calendar.google.com')
        ->call('testCalendar');

    expect($component->get('calendarOk'))->toBeFalse()
        ->and($component->get('calendarResult'))->toContain('not shared');
});

it('does not create a plan row just because someone looked at a year', function (): void {
    // Menatal ke tahun 2034 tidak sepatutnya meninggalkan pelan kosong di
    // belakangnya. Senarai tahun akan menjadi rekod tempat orang pernah
    // menekan butang dan bukan tahun yang benar-benar dirancang.
    Livewire::actingAs($this->user)
        ->test(Overview::class)
        ->call('showRoadmapYear', 2034);

    expect(RoadmapPlan::where('year', 2034)->exists())->toBeFalse();
});

it('does not blow up when the embedded preview has no year nav', function (): void {
    // wire:click diselesaikan terhadap komponen hos, dan editor tiada
    // showRoadmapYear.
    Livewire::actingAs($this->admin)
        ->test(RoadmapEditor::class)
        ->assertOk()
        ->assertDontSee('showRoadmapYear');
});
