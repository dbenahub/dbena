<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Monthly Task Planning.
 *
 * Papan ini DITULIS dalam aplikasi, bukan disegerak daripada sheet. Ia
 * dikemas kini secara langsung semasa mesyuarat mingguan, dan satu
 * pusingan sync untuk menukar satu petak daripada P kepada C akan
 * memusnahkan seluruh gunanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('task_departments')) {
            Schema::create('task_departments', function (Blueprint $table): void {
                $table->id();

                $table->string('name');
                $table->string('icon')->default('ph-megaphone');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('active')->default(true);

                $table->timestamps();
            });
        }

        if (! Schema::hasTable('monthly_tasks')) {
            Schema::create('monthly_tasks', function (Blueprint $table): void {
                $table->id();

                $table->foreignId('task_department_id')->constrained()->cascadeOnDelete();

                // Bulan dan tahun disimpan pada TUGASAN, bukan diterbitkan
                // daripada tarikh tanda. Tugasan yang belum ditanda
                // langsung masih milik satu bulan tertentu — kalau tidak
                // ia hilang daripada papan sehingga seseorang menandanya.
                $table->unsignedSmallInteger('year');
                $table->unsignedTinyInteger('month');

                $table->text('title');

                // PIC sebagai teks, BUKAN kekunci asing kepada owners.
                //
                // Papan menamakan orang yang mungkin belum wujud sebagai
                // pemilik metrik — kontraktor, kakitangan baharu, pihak
                // luar. Kekunci asing akan menolak baris itu dan
                // menyembunyikan sebahagian pelan.
                $table->string('action_by')->nullable();
                $table->string('monitor_by')->nullable();

                $table->text('remark')->nullable();

                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

                $table->timestamps();

                $table->index(['year', 'month', 'task_department_id'], 'tasks_month_dept_idx');
            });
        }

        if (! Schema::hasTable('task_day_marks')) {
            Schema::create('task_day_marks', function (Blueprint $table): void {
                $table->id();

                $table->foreignId('monthly_task_id')->constrained()->cascadeOnDelete();
                $table->unsignedTinyInteger('day');
                $table->string('mark');

                $table->timestamps();

                // Satu tanda setiap hari setiap tugasan. Tanpa ini, dua
                // klik pantas menghasilkan dua baris dan petak mula
                // berkelip antara dua warna.
                $table->unique(['monthly_task_id', 'day']);
            });
        }

        if (! Schema::hasTable('task_board_notes')) {
            Schema::create('task_board_notes', function (Blueprint $table): void {
                $table->id();

                $table->unsignedSmallInteger('year');
                $table->unsignedTinyInteger('month');

                $table->string('prepared_by')->nullable();
                $table->date('prepared_on')->nullable();

                // Satu baris setiap poin, disimpan sebagai JSON.
                $table->json('priorities')->nullable();
                $table->json('notes')->nullable();

                $table->timestamps();

                $table->unique(['year', 'month']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('task_day_marks');
        Schema::dropIfExists('monthly_tasks');
        Schema::dropIfExists('task_board_notes');
        Schema::dropIfExists('task_departments');
    }
};
