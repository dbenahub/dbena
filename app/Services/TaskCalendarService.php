<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TaskMark;
use App\Models\MonthlyTask;
use App\Models\TaskDayMark;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Kalendar tugasan — dibina daripada papan Task Planning yang sama.
 *
 * Tiada jadual acara berasingan. Satu tanda hari pada papan bulanan ialah
 * satu acara kalendar; menyimpannya dua kali bermakna menandakan hari
 * pada papan tidak muncul dalam kalendar sehingga seseorang menyalinnya,
 * dan salinan itu tidak akan pernah dibuat.
 */
class TaskCalendarService
{
    /**
     * Warna PIC — deterministik daripada nama.
     *
     * Mengikut kedudukan bermakna Zikri bertukar warna sebaik seseorang
     * ditambah di atasnya dalam senarai, dan seluruh gunanya — mengimbas
     * kalendar dan melihat siapa memiliki apa — hilang.
     *
     * @var array<int, string>
     */
    private const PIC_COLORS = [
        '#8E2A5F', '#16345C', '#1E6B4A', '#8A6A12',
        '#5B2E86', '#10656B', '#9A4A17', '#8C2230',
    ];

    /**
     * @return array<string, mixed>
     */
    public function build(int $year, int $month, ?string $pic = null): array
    {
        $tarikh = Carbon::create($year, $month, 1);

        $tasks = MonthlyTask::with(['marks', 'department'])
            ->where('year', $year)->where('month', $month)
            ->orderBy('sort_order')
            ->get();

        $team = $this->team($tasks);

        $ditapis = $pic !== null && $pic !== ''
            ? $tasks->filter(fn (MonthlyTask $t) => $t->action_by === $pic)
            : $tasks;

        $events = $this->events($ditapis, $year, $month);

        return [
            'year' => $year,
            'month' => $month,
            'monthLabel' => $tarikh->translatedFormat('F Y'),
            'grid' => $this->monthGrid($tarikh, $events),
            'events' => $events,
            'team' => $team,
            'stats' => $this->stats($ditapis),
            'upcoming' => $this->upcoming($events, $year, $month),
        ];
    }

    /**
     * Satu acara setiap tanda hari.
     *
     * @param  Collection<int, MonthlyTask>  $tasks
     * @return Collection<int, array<string, mixed>>
     */
    private function events(Collection $tasks, int $year, int $month): Collection
    {
        $keluar = collect();

        foreach ($tasks as $task) {
            foreach ($task->marks as $mark) {
                // Hari di luar bulan (Februari 30, contohnya) tidak boleh
                // menjadi tarikh. Membiarkannya menghempaskan Carbon.
                if ($mark->day < 1 || $mark->day > Carbon::create($year, $month, 1)->daysInMonth) {
                    continue;
                }

                $keluar->push([
                    'id' => $mark->id,
                    'taskId' => $task->id,
                    'day' => $mark->day,
                    'date' => Carbon::create($year, $month, $mark->day),
                    'time' => $mark->start_time ? substr((string) $mark->start_time, 0, 5) : null,
                    'title' => $task->title,
                    'mark' => $mark->mark,
                    'pic' => $task->action_by,
                    'picColor' => $this->picColor($task->action_by),
                    'department' => $task->department?->name,
                    'remark' => $task->remark,
                ]);
            }
        }

        /*
         * Diisih mengikut hari kemudian masa, dengan acara TANPA masa
         * dahulu. Acara tanpa masa ialah "sepanjang hari"; meletakkannya
         * selepas acara bermasa bermakna ia muncul di bawah mesyuarat 5
         * petang, yang membaca seolah-olah ia berlaku lewat malam.
         */
        return $keluar->sortBy([
            fn (array $a, array $b) => $a['day'] <=> $b['day'],
            fn (array $a, array $b) => ($a['time'] ?? '') <=> ($b['time'] ?? ''),
        ])->values();
    }

    /**
     * Grid kalendar SEBENAR — enam baris Isnin hingga Ahad.
     *
     * Hari daripada bulan sebelum dan selepas disertakan supaya minggu
     * kekal utuh. Grid yang bermula pada 1 haribulan tanpa mengira hari
     * apa ia jatuh bukan kalendar; ia jadual, dan tiada siapa boleh
     * membaca hujung minggu daripadanya.
     *
     * @param  Collection<int, array<string, mixed>>  $events
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function monthGrid(Carbon $bulan, Collection $events): array
    {
        $mula = $bulan->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $tamat = $bulan->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $ikutHari = $events->groupBy('day');

        $baris = [];
        $minggu = [];

        for ($d = $mula->copy(); $d->lte($tamat); $d->addDay()) {
            $dalamBulan = $d->month === $bulan->month && $d->year === $bulan->year;

            $minggu[] = [
                'date' => $d->copy(),
                'day' => (int) $d->day,
                'inMonth' => $dalamBulan,
                'isToday' => $d->isToday(),
                'isWeekend' => $d->isWeekend(),
                'events' => $dalamBulan ? ($ikutHari->get((int) $d->day, collect())) : collect(),
            ];

            if (count($minggu) === 7) {
                $baris[] = $minggu;
                $minggu = [];
            }
        }

        return $baris;
    }

    /**
     * Acara akan datang — dari HARI INI ke hadapan.
     *
     * Kalendar bulan lepas tiada acara akan datang; menunjukkan acara
     * lampau di bawah tajuk "Upcoming" ialah pembohongan kecil yang
     * menjadikan seluruh panel tidak boleh dipercayai.
     *
     * @param  Collection<int, array<string, mixed>>  $events
     * @return array<int, array<string, mixed>>
     */
    private function upcoming(Collection $events, int $year, int $month): array
    {
        $hariIni = now()->startOfDay();

        return $events
            ->filter(fn (array $e) => $e['date']->gte($hariIni))
            ->filter(fn (array $e) => ! $e['mark']->isCancelled() || $e['date']->isToday())
            ->take(8)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, MonthlyTask>  $tasks
     * @return array<string, int>
     */
    private function stats(Collection $tasks): array
    {
        $siap = $batal = $tunggu = $berjalan = 0;

        foreach ($tasks as $task) {
            $tanda = $task->marks->pluck('mark');

            // Keutamaan sama seperti papan bulanan. Mengira setiap tanda
            // secara berasingan menghasilkan jumlah melebihi bilangan
            // tugasan sebenar.
            if ($tanda->contains(fn (TaskMark $m) => $m->isCancelled())) {
                $batal++;
            } elseif ($tanda->contains(fn (TaskMark $m) => $m->isDone())) {
                $siap++;
            } elseif ($tanda->contains(fn (TaskMark $m) => $m->isPending())) {
                $tunggu++;
            } elseif ($tanda->isNotEmpty()) {
                $berjalan++;
            }
        }

        $dikira = $tasks->count() - $batal;

        return [
            'total' => $tasks->count(),
            'inProgress' => $berjalan,
            'completed' => $siap,
            'pending' => $tunggu,
            'cancelled' => $batal,
            'rate' => $dikira > 0 ? (int) round($siap / $dikira * 100) : 0,
        ];
    }

    /**
     * Senarai PIC dengan jabatan dan kiraan.
     *
     * @param  Collection<int, MonthlyTask>  $tasks
     * @return array<int, array<string, mixed>>
     */
    private function team(Collection $tasks): array
    {
        return $tasks
            ->filter(fn (MonthlyTask $t) => filled($t->action_by))
            ->groupBy('action_by')
            ->map(fn (Collection $bagi, string $nama) => [
                'name' => $nama,
                'color' => $this->picColor($nama),
                'count' => $bagi->count(),
                // Jabatan pertama yang orang ini muncul. Menyenaraikan
                // kesemuanya menjadikan penapis lebih lebar daripada
                // panelnya untuk maklumat yang jarang berbeza.
                'department' => $bagi->first()->department?->name,
            ])
            ->sortBy('name')
            ->values()
            ->all();
    }

    public function picColor(?string $name): string
    {
        $bersih = mb_strtolower(trim((string) $name));

        if ($bersih === '') {
            return '#6B6472';
        }

        return self::PIC_COLORS[abs(crc32($bersih)) % count(self::PIC_COLORS)];
    }
}
