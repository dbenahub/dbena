<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ikon menjadi pilihan.
 *
 * Carta rasmi DBENA tiada ikon. Lencana duduk di ATAS tepi kotak, jadi
 * memaksa satu pada setiap kotak menolak semua teks ke bawah 16px dan
 * memusnahkan penjajaran baris yang diukur dengan teliti.
 *
 * Lajur ini asalnya NOT NULL dengan lalai 'ph-user', jadi "tiada ikon"
 * tidak boleh diwakili langsung.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('org_nodes')) {
            return;
        }

        // Ditukar melalui SQL mentah: doctrine/dbal tidak dipasang, dan
        // Schema::table()->change() memerlukannya.
        DB::statement('ALTER TABLE org_nodes MODIFY icon VARCHAR(255) NULL DEFAULT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('org_nodes')) {
            return;
        }

        DB::statement("UPDATE org_nodes SET icon = 'ph-user' WHERE icon IS NULL");
        DB::statement("ALTER TABLE org_nodes MODIFY icon VARCHAR(255) NOT NULL DEFAULT 'ph-user'");
    }
};
