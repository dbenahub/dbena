<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Warna per-kotak.
 *
 * NULL bermakna "ikut gaya" — kotak mengambil warna lalai gaya Eksekutif,
 * Jabatan atau Sokongan. Menyimpan warna gaya ke dalam setiap baris pada
 * masa migrasi akan membekukannya: menukar warna gaya kemudian tidak akan
 * menyentuh mana-mana kotak sedia ada, dan tiada siapa dapat tahu sebabnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('org_nodes', function (Blueprint $table): void {
            if (! Schema::hasColumn('org_nodes', 'color')) {
                $table->string('color', 9)->nullable()->after('style');
            }
        });
    }

    public function down(): void
    {
        Schema::table('org_nodes', function (Blueprint $table): void {
            if (Schema::hasColumn('org_nodes', 'color')) {
                $table->dropColumn('color');
            }
        });
    }
};
