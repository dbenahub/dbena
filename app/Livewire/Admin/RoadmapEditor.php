<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\RoadmapStatus;
use App\Models\RoadmapCell;
use App\Models\RoadmapPlan;
use App\Models\Service;
use App\Services\AuditLogger;
use App\Services\GoogleCalendarReader;
use App\Services\RoadmapService;
use App\Services\Sheets\ServiceAccountSheetReader;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Editor Roadmap Tahunan — Admin sahaja.
 *
 * Tidak seperti modul lain, aplikasi ialah PENULIS di sini. Itu bermakna
 * setiap kaedah yang menulis mesti menyemak kebenaran sendiri: menyorok
 * grid daripada pengguna tidak menghalang sesiapa daripada memanggil
 * kaedah Livewire secara terus.
 */
#[Layout('components.layouts.app')]
class RoadmapEditor extends Component
{
    public int $year;

    public string $title = '';

    public string $subtitle = '';

    public string $summaryText = '';

    public string $calendarId = '';

    public ?string $calendarResult = null;

    public bool $calendarOk = false;

    public function mount(): void
    {
        $this->authorize('manage-roadmap');

        $this->year = (int) now()->year;
        $this->load();
    }

    private function plan(): RoadmapPlan
    {
        return RoadmapPlan::forYear($this->year);
    }

    private function load(): void
    {
        $plan = $this->plan();

        $this->title = (string) ($plan->title ?? '');
        $this->subtitle = (string) ($plan->subtitle ?? '');
        $this->calendarId = (string) ($plan->calendar_id ?? '');
        $this->summaryText = implode("\n", $plan->summary ?? []);
    }

    public function changeYear(int $year): void
    {
        $this->authorize('manage-roadmap');

        $this->year = max(2023, min(2035, $year));
        $this->calendarResult = null;
        $this->load();
    }

    /**
     * Kitar status satu sel.
     *
     * Ditulis serta-merta. Grid enam puluh sel dengan butang Simpan
     * bermakna admin menukar lapan sel, tertutup tab, dan kehilangan
     * kelapan-lapannya tanpa amaran.
     */
    public function cycle(int $serviceId, int $month): void
    {
        $this->authorize('manage-roadmap');

        $cell = RoadmapCell::firstOrNew([
            'service_id' => $serviceId,
            'year' => $this->year,
            'month' => max(1, min(12, $month)),
        ]);

        $cell->status = ($cell->status ?? RoadmapStatus::None)->next();
        $cell->save();
    }

    /** Isi satu baris penuh dengan satu status — dua belas klik menjadi satu. */
    public function fillRow(int $serviceId, string $status): void
    {
        $this->authorize('manage-roadmap');

        $value = RoadmapStatus::tryFrom($status) ?? RoadmapStatus::None;

        foreach (range(1, 12) as $month) {
            RoadmapCell::updateOrCreate(
                ['service_id' => $serviceId, 'year' => $this->year, 'month' => $month],
                ['status' => $value]
            );
        }
    }

    /**
     * Salin roadmap tahun sebelumnya.
     *
     * Perancangan tahun hadapan hampir sentiasa bermula sebagai tahun ini
     * dengan beberapa pindaan. Bermula daripada enam puluh sel kosong
     * menjemput admin melangkau langkah itu sepenuhnya.
     */
    public function copyFromPreviousYear(AuditLogger $audit): void
    {
        $this->authorize('manage-roadmap');

        $sumber = $this->year - 1;
        $cells = RoadmapCell::where('year', $sumber)->get();

        if ($cells->isEmpty()) {
            $this->dispatch('dbena-toast',
                message: __('roadmap.admin.nothing_to_copy', ['year' => $sumber]),
                variant: 'error');

            return;
        }

        foreach ($cells as $cell) {
            RoadmapCell::updateOrCreate(
                ['service_id' => $cell->service_id, 'year' => $this->year, 'month' => $cell->month],
                ['status' => $cell->status, 'note' => $cell->note]
            );
        }

        $audit->log('roadmap.copied', $this->plan(), (string) $this->year);

        $this->dispatch('dbena-toast', message: __('roadmap.admin.copied', ['year' => $sumber]));
    }

    public function save(AuditLogger $audit): void
    {
        $this->authorize('manage-roadmap');

        $plan = $this->plan();
        $lamaKalendar = (string) ($plan->calendar_id ?? '');

        $plan->fill([
            'title' => trim($this->title) ?: null,
            'subtitle' => trim($this->subtitle) ?: null,
            'calendar_id' => RoadmapPlan::extractCalendarId($this->calendarId),
            'summary' => collect(preg_split('/\r?\n/', $this->summaryText))
                ->map(fn (string $line) => trim($line))
                ->filter()
                ->values()
                ->all(),
        ])->save();

        // Acara dicache lima belas minit. Tanpa membuang cache, menukar ID
        // kalendar kelihatan tidak berkesan sehingga cache tamat sendiri.
        if ($lamaKalendar !== '' && $lamaKalendar !== (string) $plan->calendar_id) {
            app(GoogleCalendarReader::class)->forget($lamaKalendar, $this->year);
        }

        $audit->log('roadmap.saved', $plan, (string) $this->year);

        $this->dispatch('dbena-toast', message: __('roadmap.admin.saved'));
    }

    /** Uji kalendar sekarang, dan katakan apa yang salah jika gagal. */
    public function testCalendar(GoogleCalendarReader $calendar): void
    {
        $this->authorize('manage-roadmap');

        $ditampal = trim($this->calendarId);

        if ($ditampal === '') {
            $this->calendarOk = false;
            $this->calendarResult = __('roadmap.calendar.not_connected');

            return;
        }

        $id = RoadmapPlan::extractCalendarId($ditampal);

        /*
         * Bentuk disemak SEBELUM memanggil Google.
         *
         * ID yang salah bentuk menghasilkan 403, dan 403 bermaksud "belum
         * dikongsi" — jadi admin dihantar membetulkan perkongsian yang
         * sudah betul sementara masalah sebenar ialah teks dalam kotak.
         */
        if (! RoadmapPlan::looksLikeCalendarId($id)) {
            $this->calendarOk = false;
            $this->calendarResult = __('roadmap.calendar.bad_id');

            return;
        }

        // Medan dikemas kini supaya admin NAMPAK apa yang diambil daripada
        // pautan mereka, dan bukan sekadar diberitahu ia berjaya.
        $dariPautan = $id !== $ditampal;
        $this->calendarId = $id;

        $calendar->forget($id, $this->year);
        $result = $calendar->test($id, $this->year);

        $this->calendarOk = $result['ok'];
        $this->calendarResult = $dariPautan
            ? __('roadmap.calendar.id_from_url', ['id' => $id]).' '.$result['message']
            : $result['message'];

        if ($result['ok']) {
            // Sambungan yang berjaya disimpan serta-merta. Meminta admin
            // menekan Simpan selepas ujian lulus menjemput mereka menutup
            // tab sambil menyangka kerja sudah selesai.
            $this->plan()->update(['calendar_id' => $id]);
        }
    }

    public function render(): View
    {
        $cells = RoadmapCell::where('year', $this->year)
            ->get()
            ->keyBy(fn (RoadmapCell $c) => $c->service_id.'-'.$c->month);

        return view('livewire.admin.roadmap-editor', [
            'services' => Service::orderBy('sort_order')->get(),
            'cells' => $cells,
            'statuses' => RoadmapStatus::cases(),
            'months' => __('calendar.months_short'),
            'serviceEmail' => ServiceAccountSheetReader::serviceAccountEmail(),
            'preview' => app(RoadmapService::class)->build($this->year),
        ])->layoutData([
            'pageTitle' => __('roadmap.admin.title'),
            'pageSubtitle' => (string) $this->year,
        ]);
    }
}
