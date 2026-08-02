<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kotak jabatan dalam carta DBENA membawa TIGA baris, bukan dua:
 *
 *   MARKETING DEPARTMENT
 *   Head of Dept.
 *   AHMAD ZIKRI BIN ZAINAL
 *
 * Memampatkan baris tengah ke dalam tajuk menghasilkan "MARKETING
 * DEPARTMENT Head of Dept." pada satu baris, yang membalut dengan hodoh
 * dan kehilangan hierarki tipografi yang menjadikan carta itu boleh
 * diimbas.
 *
 * Tinggi menjadi per-kotak kerana tiga gaya itu mempunyai bilangan baris
 * yang berbeza. Tinggi tetap bermakna kotak eksekutif dua baris membawa
 * ruang kosong sebanyak baris ketiga yang tidak pernah wujud.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('org_nodes', function (Blueprint $table): void {
            if (! Schema::hasColumn('org_nodes', 'subtitle')) {
                $table->string('subtitle')->nullable()->after('title');
            }

            if (! Schema::hasColumn('org_nodes', 'height')) {
                $table->unsignedSmallInteger('height')->default(66)->after('width');
            }
        });
    }

    public function down(): void
    {
        Schema::table('org_nodes', function (Blueprint $table): void {
            if (Schema::hasColumn('org_nodes', 'subtitle')) {
                $table->dropColumn('subtitle');
            }

            if (Schema::hasColumn('org_nodes', 'height')) {
                $table->dropColumn('height');
            }
        });
    }
};
