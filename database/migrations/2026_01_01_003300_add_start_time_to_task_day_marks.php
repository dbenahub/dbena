<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Masa pada tanda hari.
 *
 * Papan bulanan tidak memerlukan masa — satu petak setiap hari sudah
 * cukup untuk mesyuarat mingguan. Kalendar memerlukannya: dua tugasan
 * pada hari yang sama tanpa masa tidak boleh disusun, dan "site visit"
 * pada 10 pagi berbeza sepenuhnya daripada "site visit" pada 4 petang
 * apabila seseorang merancang harinya.
 *
 * NULLABLE dengan sengaja. Memaksa masa bermakna setiap tanda pada papan
 * bulanan memerlukan satu keputusan tambahan yang tiada siapa mahu buat
 * semasa mesyuarat.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('task_day_marks')) {
            return;
        }

        Schema::table('task_day_marks', function (Blueprint $table): void {
            if (! Schema::hasColumn('task_day_marks', 'start_time')) {
                $table->time('start_time')->nullable()->after('mark');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('task_day_marks')) {
            return;
        }

        Schema::table('task_day_marks', function (Blueprint $table): void {
            if (Schema::hasColumn('task_day_marks', 'start_time')) {
                $table->dropColumn('start_time');
            }
        });
    }
};
