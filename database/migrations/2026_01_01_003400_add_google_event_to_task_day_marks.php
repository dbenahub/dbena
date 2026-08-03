<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ID acara Google bagi setiap tanda hari.
 *
 * Tanpa menyimpan ID, setiap sync mencipta acara BAHARU dan kalendar
 * DBENA dipenuhi salinan tugasan yang sama — sepuluh "Site visit Klang"
 * pada 12 Ogos selepas sepuluh kali sync. Menyimpan ID membolehkan
 * kemas kini dan pemadaman, bukan hanya penciptaan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('task_day_marks')) {
            return;
        }

        Schema::table('task_day_marks', function (Blueprint $table): void {
            if (! Schema::hasColumn('task_day_marks', 'google_event_id')) {
                $table->string('google_event_id')->nullable()->after('start_time');
            }

            if (! Schema::hasColumn('task_day_marks', 'google_synced_at')) {
                $table->timestamp('google_synced_at')->nullable()->after('google_event_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('task_day_marks')) {
            return;
        }

        Schema::table('task_day_marks', function (Blueprint $table): void {
            foreach (['google_event_id', 'google_synced_at'] as $lajur) {
                if (Schema::hasColumn('task_day_marks', $lajur)) {
                    $table->dropColumn($lajur);
                }
            }
        });
    }
};
