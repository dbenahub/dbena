<?php

declare(strict_types=1);

use Database\Seeders\OrgChartSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Semai carta organisasi semasa migrasi atas sebab yang sama.
 *
 * Seeder berundur sebaik ada nod, jadi ini tidak akan menyentuh carta
 * yang sudah disusun dengan tangan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('org_nodes') || ! Schema::hasTable('org_links')) {
            return;
        }

        (new OrgChartSeeder)->run();
    }

    public function down(): void
    {
        // Kedudukan yang diseret dengan tangan tidak boleh dipulihkan
        // daripada apa-apa, jadi rollback tidak memadamnya.
    }
};
