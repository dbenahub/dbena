<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MetricStatus;
use App\Enums\RoadmapStatus;
use App\Enums\TaskMark;
use App\Models\MonthlyTask;
use App\Models\RoadmapCell;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Keutamaan minggu ini — dikira daripada data sebenar.
 *
 * Sebelum ini senarai ini datang daripada jadual yang disemai dengan
 * tangan. Ia tidak pernah berubah, jadi ia kekal betul selama seminggu
 * dan salah selepas itu — dan senarai keutamaan yang salah lebih teruk
 * daripada tiada senarai, kerana orang bertindak mengikutnya.
 *
 * EMPAT SUMBER, disusun mengikut apa yang menghalang perkara lain:
 *
 *   1. Tugasan yang LEWAT tarikh akhir dalam Task Planning. Tarikh akhir
 *      yang sudah berlalu ialah satu-satunya perkara di sini yang sudah
 *      pun gagal.
 *   2. Peringkat corong yang TERPUTUS. Ia menghalang setiap peringkat di
 *      hilirnya, jadi membetulkan apa-apa yang lain minggu ini adalah
 *      sia-sia.
 *   3. Metrik merah TANPA pelan tindakan. Menulis pelan itu ialah langkah
 *      yang menyekat; metrik merah DENGAN pelan sekurang-kurangnya sedang
 *      dikendalikan.
 *   4. Servis yang roadmap katakan aktif tetapi tiada apa-apa berlaku.
 *
 * Had dua item setiap servis. Tanpa had, satu servis yang bermasalah
 * memenuhi keseluruhan senarai dan empat servis lain hilang daripada
 * pandangan sepenuhnya.
 */
class WeeklyPriorityService
{
    private const MAX_ITEMS = 6;

    private const MAX_PER_SERVICE = 2;

    /** KIV yang lebih lama daripada ini menjadi keutamaan sendiri. */
    private const KIV_STALE_DAYS = 7;

    public function __construct(
        private readonly CriticalDataService $critical,
        private readonly SalesJourneyService $journey,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function build(int $year, int $month): array
    {
        $services = Service::orderBy('sort_order')->get();

        $items = collect()
            ->concat($this->fromTasks($year, $month))
            ->concat($this->fromServices($services, $year, $month))
            ->concat($this->fromRoadmap($services, $year, $month));

        return $items
            ->sortByDesc('urgency')
            ->values()
            ->pipe(fn (Collection $all) => $this->capPerService($all))
            ->take(self::MAX_ITEMS)
            ->values()
            ->all();
    }

    /**
     * Tugasan lewat, jatuh tempo hari ini, dan KIV yang tersadai.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fromTasks(int $year, int $month): array
    {
        // Hanya bulan semasa. Tarikh akhir bulan lepas bukan lagi
        // keutamaan minggu ini — ia sejarah, dan menyenaraikannya
        // bermakna senarai tidak pernah kosong walaupun semuanya selesai.
        if ((int) now()->year !== $year || (int) now()->month !== $month) {
            return [];
        }

        $hariIni = (int) now()->day;
        $keluar = [];

        $tasks = MonthlyTask::with(['marks', 'department'])
            ->where('year', $year)->where('month', $month)
            ->get();

        foreach ($tasks as $task) {
            $tanda = $task->marks;

            if ($tanda->contains(fn ($m) => $m->mark->isCancelled())) {
                continue;
            }

            $siap = $tanda->contains(fn ($m) => $m->mark->isDone());

            $dueDays = $tanda->filter(fn ($m) => $m->mark === TaskMark::DueDate)->pluck('day');
            $lewat = $dueDays->filter(fn (int $d) => $d < $hariIni);
            $hariNi = $dueDays->contains($hariIni);

            if (! $siap && $lewat->isNotEmpty()) {
                $keluar[] = $this->item(
                    key: 'task-late-'.$task->id,
                    urgency: 100,
                    icon: 'ph-warning-octagon',
                    accent: 'oklch(0.66 0.21 25)',
                    title: __('priority.task_overdue', ['days' => $hariIni - (int) $lewat->min()]),
                    body: $task->title,
                    owner: $task->action_by,
                    route: route('task-planning'),
                    badge: __('priority.badge.overdue'),
                );

                continue;
            }

            if (! $siap && $hariNi) {
                $keluar[] = $this->item(
                    key: 'task-today-'.$task->id,
                    urgency: 92,
                    icon: 'ph-calendar-check',
                    accent: 'oklch(0.72 0.17 60)',
                    title: __('priority.task_due_today'),
                    body: $task->title,
                    owner: $task->action_by,
                    route: route('task-planning'),
                    badge: __('priority.badge.today'),
                );

                continue;
            }

            // KIV yang tersadai. Tugasan yang ditanda KIV dan tidak
            // disentuh selama seminggu bukan lagi "menunggu" — ia
            // terlupa, dan tiada siapa akan mengangkatnya sendiri.
            $kiv = $tanda->filter(fn ($m) => $m->mark === TaskMark::Kiv)->pluck('day');

            if (! $siap && $kiv->isNotEmpty() && ($hariIni - (int) $kiv->max()) >= self::KIV_STALE_DAYS) {
                $keluar[] = $this->item(
                    key: 'task-kiv-'.$task->id,
                    urgency: 62,
                    icon: 'ph-pause-circle',
                    accent: 'oklch(0.65 0.20 330)',
                    title: __('priority.task_kiv', ['days' => $hariIni - (int) $kiv->max()]),
                    body: $task->title,
                    owner: $task->action_by,
                    route: route('task-planning'),
                    badge: __('priority.badge.kiv'),
                );
            }
        }

        return $keluar;
    }

    /**
     * Corong terputus dan metrik merah, setiap servis.
     *
     * @param  Collection<int, Service>  $services
     * @return array<int, array<string, mixed>>
     */
    private function fromServices(Collection $services, int $year, int $month): array
    {
        $keluar = [];

        foreach ($services as $service) {
            $rows = $this->critical->rowsFor($service, $year, $month);

            if ($rows->isEmpty()) {
                continue;
            }

            $journey = $this->journey->build($rows);
            $break = $journey['firstBreak'] ?? null;

            if ($break !== null) {
                $seterusnya = $journey['nextStage'] ?? null;

                $keluar[] = $this->item(
                    key: 'journey-'.$service->id,
                    urgency: 85,
                    icon: 'ph-road-horizon',
                    accent: 'oklch(0.66 0.21 25)',
                    title: __('priority.journey_break', [
                        'service' => $service->name,
                        'stage' => $break['title'],
                    ]),
                    body: ($break['breakReason'] ?? null) === 'missing'
                        ? __('priority.journey_missing', [
                            'stage' => $break['title'],
                            'next' => $seterusnya['title'] ?? '—',
                        ])
                        : __('priority.journey_below', ['stage' => $break['title']]),
                    owner: $break['owner'] ?? null,
                    route: route('service.detail', $service->key),
                    badge: __('priority.badge.blocked'),
                    serviceId: $service->id,
                );
            }

            /*
             * Metrik merah TANPA pelan tindakan didahulukan.
             *
             * Metrik merah yang sudah mempunyai pelan sedang dikendalikan.
             * Yang tiada pelan sedang tidak dikendalikan oleh sesiapa, dan
             * menulis pelan itu ialah satu-satunya langkah yang boleh
             * diambil minggu ini.
             */
            $merah = $rows->filter(fn (array $r) => $r['status'] === MetricStatus::Red);

            $tanpaPelan = $merah->first(fn (array $r) => trim((string) $r['actionPlan']) === '');

            if ($tanpaPelan !== null) {
                $keluar[] = $this->item(
                    key: 'plan-'.$service->id.'-'.$tanpaPelan['id'],
                    urgency: 76,
                    icon: 'ph-note-pencil',
                    accent: 'oklch(0.72 0.17 60)',
                    title: __('priority.no_action_plan', ['metric' => $tanpaPelan['label']]),
                    body: __('priority.no_action_plan_body', ['service' => $service->name]),
                    owner: $tanpaPelan['ownerName'] ?? null,
                    route: route('service.detail', $service->key),
                    badge: __('priority.badge.no_plan'),
                    serviceId: $service->id,
                );
            }

            $denganPelan = $merah->first(fn (array $r) => trim((string) $r['actionPlan']) !== '');

            if ($denganPelan !== null) {
                $keluar[] = $this->item(
                    key: 'metric-'.$service->id.'-'.$denganPelan['id'],
                    urgency: 70,
                    icon: $service->icon_class ?: 'ph-target',
                    accent: $service->chart_color,
                    title: __('priority.metric_red', [
                        'service' => $service->name,
                        'metric' => $denganPelan['label'],
                    ]),
                    // Pelan tindakan yang sebenar dipaparkan, bukan
                    // diringkaskan. Ia ayat yang pemilik sendiri tulis,
                    // dan menggantikannya dengan ayat generik memadam
                    // satu-satunya maklumat khusus di sini.
                    body: $this->trim((string) $denganPelan['actionPlan']),
                    owner: $denganPelan['ownerName'] ?? null,
                    route: route('service.detail', $service->key),
                    badge: __('priority.badge.behind'),
                    serviceId: $service->id,
                );
            }
        }

        return $keluar;
    }

    /**
     * Servis yang roadmap katakan aktif tetapi tiada apa-apa berlaku.
     *
     * @param  Collection<int, Service>  $services
     * @return array<int, array<string, mixed>>
     */
    private function fromRoadmap(Collection $services, int $year, int $month): array
    {
        $cells = RoadmapCell::where('year', $year)->where('month', $month)->get()->keyBy('service_id');

        if ($cells->isEmpty()) {
            return [];
        }

        $keluar = [];

        foreach ($services as $service) {
            $status = $cells->get($service->id)?->status ?? RoadmapStatus::None;

            if (! $status->countsTowardTarget()) {
                continue;
            }

            $rows = $this->critical->rowsFor($service, $year, $month);

            // Roadmap menjanjikan bulan aktif; jadual tiada satu pun angka.
            // Percanggahan itu tidak kelihatan di mana-mana skrin lain.
            $adaAngka = $rows->contains(fn (array $r) => $r['actual'] !== null);

            if (! $adaAngka) {
                $keluar[] = $this->item(
                    key: 'roadmap-'.$service->id,
                    urgency: 58,
                    icon: 'ph-road-horizon',
                    accent: 'oklch(0.72 0.16 330)',
                    title: __('priority.roadmap_idle', ['service' => $service->name]),
                    body: __('priority.roadmap_idle_body', [
                        'status' => $status->label(),
                        'month' => Carbon::create($year, $month, 1)->translatedFormat('F'),
                    ]),
                    owner: null,
                    route: route('service.detail', $service->key),
                    badge: __('priority.badge.roadmap'),
                    serviceId: $service->id,
                );
            }
        }

        return $keluar;
    }

    /**
     * Had dua item setiap servis.
     *
     * @param  Collection<int, array<string, mixed>>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private function capPerService(Collection $items): Collection
    {
        $kiraan = [];

        return $items->filter(function (array $item) use (&$kiraan): bool {
            $id = $item['serviceId'];

            if ($id === null) {
                return true;
            }

            $kiraan[$id] = ($kiraan[$id] ?? 0) + 1;

            return $kiraan[$id] <= self::MAX_PER_SERVICE;
        })->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function item(
        string $key,
        int $urgency,
        string $icon,
        string $accent,
        string $title,
        string $body,
        ?string $owner,
        string $route,
        string $badge,
        ?int $serviceId = null,
    ): array {
        return [
            'key' => $key,
            'urgency' => $urgency,
            'icon' => $icon,
            'accent' => $accent,
            'title' => $title,
            'body' => $body,
            'owner' => filled($owner) && $owner !== '—' ? $owner : null,
            'initials' => $this->initials($owner),
            'route' => $route,
            'badge' => $badge,
            'serviceId' => $serviceId,
        ];
    }

    private function initials(?string $name): string
    {
        return collect(explode(' ', trim((string) $name)))
            ->filter()
            ->take(2)
            ->map(fn (string $p) => mb_strtoupper(mb_substr($p, 0, 1)))
            ->implode('');
    }

    /** Pendekkan pelan tindakan yang panjang tanpa memotong perkataan. */
    private function trim(string $text, int $max = 110): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));

        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, mb_strrpos(mb_substr($text, 0, $max), ' ') ?: $max).'…';
    }
}
