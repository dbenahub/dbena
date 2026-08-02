<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TaskDepartment;
use Illuminate\Database\Seeder;

/**
 * Jabatan papan tugasan bulanan — lima jabatan sebenar DBENA.
 *
 * Kesemuanya disemai walaupun tiga daripadanya masih kosong pada papan
 * semasa. Jabatan yang hilang daripada papan bermakna tiada tempat untuk
 * butang "tambah tugasan", jadi tugasan pertama Design atau Contract
 * tidak boleh dimasukkan langsung — dan orang menulisnya di tempat lain.
 */
class TaskDepartmentSeeder extends Seeder
{
    public function run(): void
    {
        if (TaskDepartment::exists()) {
            return;
        }

        foreach ([
            ['name' => 'Marketing Department', 'icon' => 'ph-megaphone', 'sort_order' => 1],
            ['name' => 'Design Department', 'icon' => 'ph-wrench', 'sort_order' => 2],
            ['name' => 'Management Department', 'icon' => 'ph-users-three', 'sort_order' => 3],
            ['name' => 'Contract Department', 'icon' => 'ph-briefcase', 'sort_order' => 4],
            ['name' => 'Operation Department', 'icon' => 'ph-gear', 'sort_order' => 5],
        ] as $jabatan) {
            TaskDepartment::create($jabatan);
        }
    }
}
