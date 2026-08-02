<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RoadmapStatus;
use App\Models\RoadmapCell;
use App\Models\RoadmapPlan;
use App\Models\Service;
use Illuminate\Support\Collection;

/**
 * Menghimpun Roadmap Tahunan: grid, sasaran, dan acara kalendar.
 */
class RoadmapService
{
    public function __construct(private readonly GoogleCalendarReader $calendar) {}

    /**
     * @return array<string, mixed>
     */
    public function build(int $year): array
    {
        /*
         * firstOrNew, BUKAN firstOrCreate.
         *
         * Ini berjalan pada setiap paparan Dashboard Utama. Mencipta baris
         * bermakna seorang pengguna yang menatal ke tahun 2034 meninggalkan
         * pelan kosong di belakangnya, dan senarai tahun dalam pangkalan
         * data menjadi rekod tempat orang pernah menekan butang.
         */
        $plan = RoadmapPlan::firstOrNew(['year' => $year]);
        $services = Service::orderBy('sort_order')->get();

        $cells = RoadmapCell::where('year', $year)
            ->get()
            ->keyBy(fn (RoadmapCell $c) => $c->service_id.'-'.$c->month);

        $rows = $services->map(fn (Service $service) => $this->row($service, $year, $cells));

        $events = [];
        $calendarError = null;

        if (filled($plan->calendar_id)) {
            try {
                $events = $this->calendar->eventsByMonth((string) $plan->calendar_id, $year);
            } catch (\Throwable $e) {
                /*
                 * Kalendar yang gagal TIDAK boleh menjatuhkan roadmap.
                 *
                 * Grid ialah kandungan utama dan ia hidup dalam pangkalan
                 * data kita sendiri. Membiarkan ralat Google menaik ke atas
                 * bermakna Dashboard Utama memaparkan halaman ralat kerana
                 * seseorang membatalkan perkongsian kalendar.
                 */
                $calendarError = $e->getMessage();
            }
        }

        return [
            'year' => $year,
            'plan' => $plan,
            'rows' => $rows,
            'months' => range(1, 12),
            'events' => $events,
            'eventCount' => collect($events)->flatten(1)->count(),
            'calendarError' => $calendarError,
            'annualTarget' => $rows->sum('annualTarget'),
            'quarters' => $this->quarters($rows),
        ];
    }

    /**
     * @param  Collection<string, RoadmapCell>  $cells
     * @return array<string, mixed>
     */
    private function row(Service $service, int $year, Collection $cells): array
    {
        $monthly = (float) ($service->monthly_target ?? 0);
        $months = [];
        $activeMonths = 0;

        foreach (range(1, 12) as $month) {
            $cell = $cells->get($service->id.'-'.$month);
            $status = $cell?->status ?? RoadmapStatus::None;

            if ($status->countsTowardTarget()) {
                $activeMonths++;
            }

            $months[$month] = [
                'month' => $month,
                'status' => $status,
                'note' => $cell?->note,
                // Sasaran sel: sifar bagi bulan yang dijeda dengan sengaja.
                'target' => $status->countsTowardTarget() ? $monthly : 0.0,
            ];
        }

        /*
         * Baris "Aktif Sepanjang Tahun" dilukis sebagai SATU bar dalam
         * reka bentuk, bukan dua belas sel yang sama. Bar itu menyampaikan
         * satu keputusan; dua belas petak yang serupa menjemput mata
         * mencari perbezaan yang tiada.
         */
        $allYear = collect($months)->every(
            fn (array $m) => $m['status'] === RoadmapStatus::ActiveAllYear
        );

        return [
            'service' => $service,
            'months' => $months,
            'allYear' => $allYear,
            'monthlyTarget' => $monthly,
            'activeMonths' => $activeMonths,
            'annualTarget' => $monthly * $activeMonths,
        ];
    }

    /**
     * Ringkasan suku tahun — sudut pandang kedua pada data yang sama.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function quarters(Collection $rows): array
    {
        $out = [];

        foreach ([1 => [1, 2, 3], 2 => [4, 5, 6], 3 => [7, 8, 9], 4 => [10, 11, 12]] as $q => $months) {
            $target = 0.0;
            $active = 0;

            foreach ($rows as $row) {
                foreach ($months as $m) {
                    $target += $row['months'][$m]['target'];

                    if ($row['months'][$m]['status']->countsTowardTarget()) {
                        $active++;
                    }
                }
            }

            $out[] = ['quarter' => $q, 'months' => $months, 'target' => $target, 'activeCells' => $active];
        }

        return $out;
    }
}
