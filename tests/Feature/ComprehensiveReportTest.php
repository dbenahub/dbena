<?php

declare(strict_types=1);

use App\Enums\ReportPeriod;
use App\Enums\UserRole;
use App\Livewire\Dashboard\Laporan;
use App\Enums\RoadmapStatus;
use App\Models\RoadmapCell;
use App\Models\Service;
use App\Models\User;
use App\Services\ComprehensiveReportService;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed();

    $this->user = User::where('role', UserRole::User)->firstOrFail();
    $this->admin = User::where('role', UserRole::Admin)->firstOrFail();

    $this->reports = app(ComprehensiveReportService::class);
    $this->year = 2026;
    $this->month = 8;
});

/*
|--------------------------------------------------------------------------
| Struktur laporan
|--------------------------------------------------------------------------
*/

it('answers every question a reader actually asks', function (): void {
    // Laporan yang menyenaraikan nombor tanpa memberitahu maksudnya
    // memindahkan kerja analisis kepada pembaca — dan pembaca ialah orang
    // yang meminta laporan itu kerana dia tiada masa untuk analisis.
    $data = $this->reports->build(ReportPeriod::Monthly, $this->year, $this->month);

    foreach (['summary', 'previous', 'trend', 'breakdown', 'funnel', 'causes', 'owners', 'actions'] as $seksyen) {
        expect($data)->toHaveKey($seksyen);
    }
});

it('builds a twelve-month trend regardless of the period chosen', function (): void {
    // Trend setahun ialah konteks. Laporan bulanan yang hanya menunjukkan
    // satu bulan tidak boleh menjawab "ke arah mana ia bergerak".
    $data = $this->reports->build(ReportPeriod::Weekly, $this->year, $this->month, 2);

    expect($data['trend']['series'])->toHaveCount(12);
});

it('scales the chart to include the target line', function (): void {
    // Menskala hanya kepada jualan sebenar bermakna bar sasaran keluar
    // dari carta pada bulan yang paling teruk.
    $data = $this->reports->build(ReportPeriod::Monthly, $this->year, $this->month);

    $sasaranTertinggi = max(array_column($data['trend']['series'], 'target'));

    expect($data['trend']['peak'])->toBeGreaterThanOrEqual($sasaranTertinggi);
});

/*
|--------------------------------------------------------------------------
| Tempoh
|--------------------------------------------------------------------------
*/

it('divides the monthly target by four for a weekly report', function (): void {
    // Membandingkan jualan seminggu dengan sasaran sebulan menghasilkan
    // 25% yang kelihatan seperti kegagalan pada minggu yang berjalan tepat
    // mengikut rancangan.
    $mingguan = $this->reports->build(ReportPeriod::Weekly, $this->year, $this->month, 1);
    $bulanan = $this->reports->build(ReportPeriod::Monthly, $this->year, $this->month);

    expect($mingguan['summary']['target'])
        ->toBeLessThan($bulanan['summary']['target'])
        ->and(round($mingguan['summary']['target'] * 4, 2))
        ->toBe(round($bulanan['summary']['target'], 2));
});

it('covers twelve months for a yearly report', function (): void {
    $tahunan = $this->reports->build(ReportPeriod::Yearly, $this->year, 1);
    $bulanan = $this->reports->build(ReportPeriod::Monthly, $this->year, 1);

    expect($tahunan['scope']['months'])->toHaveCount(12)
        ->and($tahunan['summary']['target'])->toBeGreaterThan($bulanan['summary']['target']);
});

it('compares a yearly report against last year, not last month', function (): void {
    $data = $this->reports->build(ReportPeriod::Yearly, $this->year, 1);

    expect($data['previous']['label'])->toBe((string) ($this->year - 1));
});

it('compares a monthly report against the previous month', function (): void {
    // RM450,000 ialah berita baik selepas RM300,000 dan berita buruk
    // selepas RM600,000; laporan yang tidak menyatakan yang mana
    // meninggalkan pembaca membuat kesimpulan yang salah dengan yakin.
    $data = $this->reports->build(ReportPeriod::Monthly, 2026, 8);

    expect($data['previous']['label'])->toContain('2026');
});

/*
|--------------------------------------------------------------------------
| Penilaian dan naratif
|--------------------------------------------------------------------------
*/

it('gives every verdict a colour and a sentence key', function (): void {
    $data = $this->reports->build(ReportPeriod::Monthly, $this->year, $this->month);

    expect($data['summary']['status'])->toHaveKeys(['key', 'label', 'color'])
        ->and(__('report.summary.narrative_'.$data['summary']['status']['key']))
        ->not->toContain('narrative_');
});

it('never reports a negative gap', function (): void {
    // Jurang negatif bermakna "melebihi sasaran", dan menulisnya sebagai
    // jurang membaca seperti kekurangan.
    $data = $this->reports->build(ReportPeriod::Monthly, $this->year, $this->month);

    expect($data['summary']['gap'])->toBeGreaterThanOrEqual(0.0);

    foreach ($data['breakdown'] as $baris) {
        expect($baris['gap'])->toBeGreaterThanOrEqual(0.0);
    }
});

/*
|--------------------------------------------------------------------------
| Analisis
|--------------------------------------------------------------------------
*/

it('names an owner for every root cause', function (): void {
    // Punca tanpa nama ialah masalah yang setiap orang harap orang lain
    // uruskan.
    $data = $this->reports->build(ReportPeriod::Monthly, $this->year, $this->month);

    foreach ($data['causes'] as $punca) {
        expect($punca)->toHaveKeys(['service', 'stage', 'owner', 'reason', 'effect'])
            ->and($punca['reason'])->not->toBe('');
    }
});

it('sorts owners by red metrics, not alphabetically', function (): void {
    // Senarai mengikut abjad menyembunyikan orang yang memerlukan sokongan
    // paling banyak di tengah-tengah halaman.
    $owners = $this->reports->build(ReportPeriod::Monthly, $this->year, $this->month)['owners'];

    $merah = array_column($owners, 'red');

    expect($merah)->toBe(collect($merah)->sortDesc()->values()->all());
});

it('puts blocking actions before ongoing ones', function (): void {
    $tindakan = $this->reports->build(ReportPeriod::Monthly, $this->year, $this->month)['actions'];

    $keutamaan = array_column($tindakan, 'priority');

    expect($keutamaan)->toBe(collect($keutamaan)->sort()->values()->all());
});

it('gives every action a what, a why and a when', function (): void {
    // Cadangan tanpa sebab ialah arahan, dan arahan tanpa tarikh ialah
    // hasrat.
    foreach ($this->reports->build(ReportPeriod::Monthly, $this->year, $this->month)['actions'] as $t) {
        expect($t['what'])->not->toBe('')
            ->and($t['why'])->not->toBe('')
            ->and($t['when'])->not->toBe('');
    }
});

it('keeps a service filter to that service only', function (): void {
    $data = $this->reports->build(ReportPeriod::Monthly, $this->year, $this->month, null, 'renovation');

    expect($data['breakdown'])->toHaveCount(1)
        ->and($data['breakdown']->first()['service']->key)->toBe('renovation');
});

/*
|--------------------------------------------------------------------------
| Eksport
|--------------------------------------------------------------------------
*/

it('exports a monthly PDF', function (): void {
    $response = $this->actingAs($this->user)
        ->get(route('laporan.pdf', ['tempoh' => 'monthly', 'tahun' => 2026, 'bulan' => 8]));

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('exports a weekly PDF', function (): void {
    $this->actingAs($this->user)
        ->get(route('laporan.pdf', ['tempoh' => 'weekly', 'tahun' => 2026, 'bulan' => 8, 'minggu' => 3]))
        ->assertOk();
});

it('exports a yearly PDF', function (): void {
    $this->actingAs($this->user)
        ->get(route('laporan.pdf', ['tempoh' => 'yearly', 'tahun' => 2026]))
        ->assertOk();
});

it('exports a single service', function (): void {
    $this->actingAs($this->user)
        ->get(route('laporan.pdf', ['tempoh' => 'monthly', 'tahun' => 2026, 'bulan' => 8, 'servis' => 'kabinet']))
        ->assertOk();
});

it('names the file after the period so downloads stay apart', function (): void {
    // Lima fail bernama "laporan.pdf" dalam satu folder muat turun tidak
    // dapat dibezakan langsung.
    $response = $this->actingAs($this->user)
        ->get(route('laporan.pdf', ['tempoh' => 'yearly', 'tahun' => 2026]));

    expect($response->headers->get('content-disposition'))
        ->toContain('yearly')
        ->toContain('2026');
});

it('falls back to monthly for an unknown period', function (): void {
    // Tempoh yang tidak sah menghasilkan laporan kosong yang kelihatan
    // seperti tiada data.
    $this->actingAs($this->user)
        ->get(route('laporan.pdf', ['tempoh' => 'harian', 'tahun' => 2026, 'bulan' => 8]))
        ->assertOk();
});

it('turns a guest away', function (): void {
    $this->get(route('laporan.pdf'))->assertRedirect(route('login'));
});

it('offers the PDF as the only export', function (): void {
    // CSV ialah senarai nombor tanpa analisis. Mengekalkannya di sebelah
    // laporan sebenar hanya menjemput orang memilih yang salah, dan
    // kemudian bertanya kenapa laporan itu tidak lengkap.
    expect(\Illuminate\Support\Facades\Route::has('laporan.export'))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Halaman Reports
|--------------------------------------------------------------------------
*/

it('offers all three periods on the page', function (): void {
    $component = Livewire::actingAs($this->user)->test(Laporan::class);

    foreach (ReportPeriod::cases() as $p) {
        $component->call('selectPeriod', $p->value)->assertSet('period', $p->value);
    }
});

it('refuses an invalid period from the page', function (): void {
    Livewire::actingAs($this->user)
        ->test(Laporan::class)
        ->call('selectPeriod', 'harian')
        ->assertSet('period', 'monthly');
});

it('clamps the week to the four a month has', function (): void {
    Livewire::actingAs($this->user)
        ->test(Laporan::class)
        ->call('selectWeek', 9)
        ->assertSet('week', 4);
});

it('shows only the PDF button', function (): void {
    Livewire::actingAs($this->user)
        ->test(Laporan::class)
        ->assertSee(__('report.export_pdf'))
        ->assertDontSee('CSV');
});

/*
|--------------------------------------------------------------------------
| Bahagian B — analisis setiap servis
|--------------------------------------------------------------------------
*/

it('gives every service its own section', function (): void {
    // Ringkasan syarikat menjawab "adakah kita pada landasan"; ia tidak
    // boleh menjawab "kenapa Kabinet tersasar sedangkan Renovation tidak".
    $data = $this->reports->build(ReportPeriod::Monthly, $this->year, $this->month);

    expect($data['perService'])->toHaveCount(Service::count());

    foreach ($data['perService'] as $bahagian) {
        expect($bahagian)->toHaveKeys(['service', 'summary', 'roadmap', 'narrative', 'funnel', 'causes', 'owners', 'actions'])
            ->and($bahagian['narrative'])->not->toBe('');
    }
});

it('writes a different sentence for each verdict', function (): void {
    // Menggabungkan lima servis ke dalam satu ulasan menghasilkan ayat
    // yang benar untuk purata dan salah untuk setiap servis.
    $data = $this->reports->build(ReportPeriod::Monthly, $this->year, $this->month);

    $ulasan = collect($data['perService'])->pluck('narrative');

    foreach ($ulasan as $u) {
        expect($u)->not->toContain('narrative_');
    }
});

it('names the service in its own commentary', function (): void {
    $data = $this->reports->build(ReportPeriod::Monthly, $this->year, $this->month);

    foreach ($data['perService'] as $bahagian) {
        expect($bahagian['narrative'])->toContain($bahagian['service']->name);
    }
});

/*
|--------------------------------------------------------------------------
| Kaitan roadmap
|--------------------------------------------------------------------------
*/

it('treats a paused service as planned, not failed', function (): void {
    // Bulan di bawah sasaran ketika tiada kempen berjalan bukan kegagalan,
    // ia rancangan. Menghakimi kedua-duanya dengan ayat yang sama
    // menghukum keputusan yang betul.
    $divider = Service::where('key', 'divider')->firstOrFail();

    RoadmapCell::updateOrCreate(
        ['service_id' => $divider->id, 'year' => $this->year, 'month' => $this->month],
        ['status' => RoadmapStatus::Paused->value]
    );

    $bahagian = collect($this->reports->build(ReportPeriod::Monthly, $this->year, $this->month)['perService'])
        ->firstWhere('service.id', $divider->id);

    expect($bahagian['roadmap']['active'])->toBeFalse()
        ->and($bahagian['narrative'])->toContain($divider->name)
        ->and($bahagian['actions'])->toBe([])
        ->and($bahagian['causes'])->toBe([]);
});

it('still analyses a service the roadmap marks running', function (): void {
    $renovation = Service::where('key', 'renovation')->firstOrFail();

    RoadmapCell::updateOrCreate(
        ['service_id' => $renovation->id, 'year' => $this->year, 'month' => $this->month],
        ['status' => RoadmapStatus::Campaign->value]
    );

    $bahagian = collect($this->reports->build(ReportPeriod::Monthly, $this->year, $this->month)['perService'])
        ->firstWhere('service.id', $renovation->id);

    expect($bahagian['roadmap']['active'])->toBeTrue()
        ->and($bahagian['roadmap']['label'])->toBe(RoadmapStatus::Campaign->label());
});

it('says so when the roadmap was never set', function (): void {
    // Melaporkannya sebagai "dijeda" akan menyenyapkan analisis untuk
    // servis yang mungkin sedang berjalan; melaporkannya sebagai "aktif"
    // mendakwa niat yang tiada siapa rekodkan.
    RoadmapCell::query()->delete();

    $bahagian = collect($this->reports->build(ReportPeriod::Monthly, $this->year, $this->month)['perService'])->first();

    expect($bahagian['roadmap']['known'])->toBeFalse()
        ->and($bahagian['roadmap']['active'])->toBeTrue()
        ->and($bahagian['roadmap']['label'])->toBe(__('report.roadmap.unknown'));
});

it('counts how many services are running this month', function (): void {
    // Tanpa ini, pembaca menganggap setiap servis di bawah sasaran ialah
    // kegagalan — termasuk yang memang dijeda dengan sengaja.
    RoadmapCell::query()->delete();

    foreach (['renovation' => RoadmapStatus::Campaign, 'kabinet' => RoadmapStatus::Paused] as $kunci => $status) {
        $s = Service::where('key', $kunci)->firstOrFail();

        RoadmapCell::create([
            'service_id' => $s->id, 'year' => $this->year,
            'month' => $this->month, 'status' => $status->value,
        ]);
    }

    $roadmap = $this->reports->build(ReportPeriod::Monthly, $this->year, $this->month)['roadmap'];

    expect($roadmap['total'])->toBe(Service::count())
        ->and($roadmap['active'])->toContain('Renovation')
        ->and($roadmap['paused'])->toContain('Kabinet');
});

it('exports a PDF with the per-service part', function (): void {
    $this->actingAs($this->user)
        ->get(route('laporan.pdf', ['tempoh' => 'monthly', 'tahun' => 2026, 'bulan' => 8]))
        ->assertOk();
});

it('exports a PDF when one service is paused', function (): void {
    $divider = Service::where('key', 'divider')->firstOrFail();

    RoadmapCell::updateOrCreate(
        ['service_id' => $divider->id, 'year' => 2026, 'month' => 8],
        ['status' => RoadmapStatus::Paused->value]
    );

    $this->actingAs($this->user)
        ->get(route('laporan.pdf', ['tempoh' => 'monthly', 'tahun' => 2026, 'bulan' => 8]))
        ->assertOk();
});
