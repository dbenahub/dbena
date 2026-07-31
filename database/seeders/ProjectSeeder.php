<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Database\Seeder;

/** 16 projek demo — nilai tepat dari prototaip (projectsByService). */
class ProjectSeeder extends Seeder
{
    private const DATA = [
        'renovation' => [
            ['Rumah Taman Melawati',        'En. Zulkifli Rahman',        68000,  ProjectStatus::Selesai,           '2026-07-12'],
            ['Kondominium Mont Kiara',      'Pn. Siti Aishah',            92000,  ProjectStatus::DalamProses,       '2026-07-18'],
            ['Rumah Banglo Bangi',          'En. Farid Iskandar',         145000, ProjectStatus::DalamProses,       '2026-07-22'],
            ['Apartmen Cheras',             'Pn. Wong Mei Ling',          54000,  ProjectStatus::MenungguKelulusan, '2026-07-24'],
        ],
        'kabinet' => [
            ['Kabinet Dapur Puchong',       'Pn. Nor Aina',               18500,  ProjectStatus::Selesai,           '2026-07-10'],
            ['Kabinet Bilik Subang',        'En. Rizal Hakim',            24200,  ProjectStatus::DalamProses,       '2026-07-19'],
            ['Kabinet Pejabat Shah Alam',   'Syarikat ABC Sdn Bhd',       31500,  ProjectStatus::DalamProses,       '2026-07-21'],
            ['Kabinet Dapur Klang',         'Pn. Fatimah Zahra',          15800,  ProjectStatus::MenungguKelulusan, '2026-07-25'],
        ],
        'bina-rumah' => [
            ['Banglo Setia Alam',           "Dato' Azman",                850000, ProjectStatus::DalamProses,       '2026-07-05'],
            ['Rumah Teres Rawang',          'En. Hafizuddin',             320000, ProjectStatus::Perancangan,       '2026-07-14'],
            ['Rumah Kluster Semenyih',      'Pn. Aida Roslan',            410000, ProjectStatus::DalamProses,       '2026-07-20'],
        ],
        'divider' => [
            ['Pembahagi Ruang Tetamu, PJ',  'En. Kamal Ariffin',          8500,   ProjectStatus::Selesai,           '2026-07-08'],
            ['Pembahagi Pejabat KL',        'Firma Guaman XYZ',           14200,  ProjectStatus::DalamProses,       '2026-07-16'],
            ['Pembahagi Rumah Ampang',      'Pn. Suraya Ismail',          6900,   ProjectStatus::Selesai,           '2026-07-23'],
        ],
        'mihrab' => [
            ['Mihrab Masjid Ampang',        'Jawatankuasa Masjid Ampang', 38000,  ProjectStatus::DalamProses,       '2026-07-11'],
            ['Mihrab Surau Bangi',          'Jawatankuasa Surau Bangi',   22000,  ProjectStatus::Perancangan,       '2026-07-20'],
        ],
    ];

    public function run(): void
    {
        foreach (self::DATA as $serviceKey => $projects) {
            $service = Service::where('key', $serviceKey)->firstOrFail();

            foreach ($projects as [$name, $client, $value, $status, $date]) {
                Project::updateOrCreate(
                    ['service_id' => $service->id, 'name' => $name],
                    [
                        'client_name' => $client,
                        'value' => $value,
                        'status' => $status,
                        'project_date' => $date,
                    ]
                );
            }
        }
    }
}
