<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TaskMark;
use App\Models\MonthlyTask;
use App\Models\TaskBoardNote;
use App\Models\TaskDayMark;
use App\Models\TaskDepartment;
use Illuminate\Database\Seeder;

/**
 * Papan Ogos 2026 sebenar DBENA sebagai titik permulaan.
 *
 * Menaip semula tujuh tugasan dan dua puluh tanda hari sebelum ciri ini
 * boleh dinilai ialah halangan yang menyebabkan ia tidak pernah dinilai.
 * Bermula daripada papan yang betul bermakna mesyuarat pertama ialah
 * pindaan, bukan kemasukan data.
 *
 * BERUNDUR jika bulan itu sudah mempunyai tugasan. Menjalankan seeder
 * dua kali tidak boleh menggandakan papan yang sedang digunakan.
 */
class TaskPlanningExampleSeeder extends Seeder
{
    private const YEAR = 2026;

    private const MONTH = 8;

    public function run(): void
    {
        if (MonthlyTask::where('year', self::YEAR)->where('month', self::MONTH)->exists()) {
            return;
        }

        $jabatan = TaskDepartment::orderBy('sort_order')->get()->keyBy('name');

        $marketing = $jabatan->get('Marketing Department');
        $operasi = $jabatan->get('Operation Department');

        if ($marketing === null || $operasi === null) {
            return;
        }

        /*
         * Setiap tugasan: [tajuk, action by, monitor by, remark, tanda].
         * Tanda ialah hari => TaskMark, tepat seperti papan Ogos.
         */
        $tugasan = [
            [$marketing, 'Buka car booth event Renovation di Pasar pagi Stadium Shah Alam',
                'Zikri', 'Nizam', 'Task Complete',
                [7 => TaskMark::Complete, 8 => TaskMark::Complete]],

            [$marketing, 'Joint booth 3 hari di Putrajaya', 'Zikri', 'Nizam', null,
                [18 => TaskMark::Planning, 19 => TaskMark::Planning, 20 => TaskMark::Planning]],

            [$marketing, 'Gantung banner Renovation di taman-2 perumahan',
                'Zikri', 'Nizam', 'KIV - Clash appt site visit client',
                [13 => TaskMark::Kiv, 14 => TaskMark::Kiv]],

            [$marketing, 'Call Calling ball room session (Grouping)', 'Zikri', 'Nizam', 'On going',
                [7 => TaskMark::Complete, 8 => TaskMark::Complete,
                 14 => TaskMark::Complete, 15 => TaskMark::Complete,
                 21 => TaskMark::Planning, 28 => TaskMark::Planning]],

            [$marketing, 'Join event nextworking BNI', 'Zikri', 'Nizam', 'Event cancel postpone new date',
                [19 => TaskMark::Cancel, 20 => TaskMark::Cancel]],

            [$marketing, 'Shoot video Klinik Tg Rambutan', 'Zikri', 'Nizam', null,
                [13 => TaskMark::Complete, 14 => TaskMark::Complete]],

            [$operasi, 'Recrut New Tukang : 1 contact setiap minggu',
                'Azhari', 'Nizam', 'On going',
                [21 => TaskMark::Planning, 28 => TaskMark::Planning]],
        ];

        $urutan = [];

        foreach ($tugasan as [$dept, $tajuk, $action, $monitor, $remark, $tanda]) {
            $urutan[$dept->id] = ($urutan[$dept->id] ?? 0) + 1;

            $task = MonthlyTask::create([
                'task_department_id' => $dept->id,
                'year' => self::YEAR, 'month' => self::MONTH,
                'title' => $tajuk,
                'action_by' => $action,
                'monitor_by' => $monitor,
                'remark' => $remark,
                'sort_order' => $urutan[$dept->id],
            ]);

            foreach ($tanda as $hari => $mark) {
                TaskDayMark::create([
                    'monthly_task_id' => $task->id,
                    'day' => $hari,
                    'mark' => $mark->value,
                ]);
            }
        }

        TaskBoardNote::updateOrCreate(
            ['year' => self::YEAR, 'month' => self::MONTH],
            [
                'prepared_by' => 'NIZAM',
                'prepared_on' => '2026-08-01',
                'priorities' => [
                    'Increase leads & site visit',
                    'Convert quotation to sales',
                    'Strengthen event & booth activities',
                    'Recruit new tukang consistently',
                ],
                'notes' => [
                    'Pastikan semua task dikemas kini setiap hari.',
                    'Fokus pada aktiviti sales & event untuk hasil lebih tinggi.',
                    'Pantau due date dan selesaikan sebelum tarikh akhir.',
                ],
            ]
        );
    }
}
