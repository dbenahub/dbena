<?php

declare(strict_types=1);

use Database\Seeders\TaskDepartmentSeeder;
use Database\Seeders\TaskPlanningExampleSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Semai data papan tugasan semasa migrasi, bukan melalui perintah manual.
 *
 * Menyuruh seseorang menjalankan `php artisan db:seed --class=...` selepas
 * setiap deploy menambah langkah yang mesti diingat, dijalankan di tempat
 * yang betul, dan dilaporkan dengan betul oleh UI hos. Kesemua tiga boleh
 * gagal — dan apabila UI Forge gagal membaca fail outputnya sendiri, ia
 * melaporkan "Failed" tanpa memberitahu sama ada perintah itu berjalan.
 *
 * Migrasi berjalan sendiri pada setiap deploy dan melaporkan kegagalan
 * SEBENAR. Kedua-dua seeder berundur jika data sudah wujud, jadi migrasi
 * ini selamat walaupun seeder sudah pernah dijalankan dengan tangan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('task_departments') || ! Schema::hasTable('monthly_tasks')) {
            return;
        }

        (new TaskDepartmentSeeder)->run();
        (new TaskPlanningExampleSeeder)->run();
    }

    public function down(): void
    {
        // Data yang disemai TIDAK dibuang.
        //
        // Papan ini disunting oleh manusia sebaik ia muncul. Membuangnya
        // pada rollback bermakna satu `migrate:rollback` memadam rekod
        // mesyuarat sebenar yang tiada salinan di tempat lain.
    }
};
