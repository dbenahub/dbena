<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TaskDepartment;
use Illuminate\Database\Seeder;

/**
 * Jabatan permulaan untuk papan tugasan bulanan.
 *
 * Papan kosong tanpa satu pun jabatan tidak mempunyai tempat untuk butang
 * "tambah tugasan", jadi skrin pertama yang dilihat pengguna ialah jalan
 * mati. Dua jabatan daripada papan sebenar memberi mereka tempat untuk
 * bermula; selebihnya ditambah dalam Panel Admin.
 */
class TaskDepartmentSeeder extends Seeder
{
    public function run(): void
    {
        if (TaskDepartment::exists()) {
            return;
        }

        foreach ([
            ['name' => 'SALES & MARKETING', 'icon' => 'ph-megaphone', 'sort_order' => 1],
            ['name' => 'OPERATION DEPARTMENT', 'icon' => 'ph-gear', 'sort_order' => 2],
        ] as $jabatan) {
            TaskDepartment::create($jabatan);
        }
    }
}
