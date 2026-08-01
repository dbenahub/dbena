<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bezakan integrasi Data Kritikal daripada integrasi Master Project.
 *
 * Kedua-duanya menunjuk ke fail Google Sheet yang SAMA tetapi tab yang
 * berbeza, dengan pemetaan lajur yang berbeza sepenuhnya — satu baris
 * dalam tab Data Kritikal ialah metrik mingguan; satu baris dalam tab
 * Master Project ialah projek pelanggan.
 *
 * service_id NULL sudah bermakna "tetapan global", dan uniknya menghalang
 * baris NULL kedua. Menambah `kind` pada kunci unik membenarkan satu
 * konfigurasi global bagi setiap jenis tanpa melonggarkan jaminan bahawa
 * satu servis hanya boleh ada satu integrasi Data Kritikal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sheet_integrations', function (Blueprint $table): void {
            $table->string('kind')->default('critical')->after('service_id');
        });

        Schema::table('sheet_integrations', function (Blueprint $table): void {
            $table->dropUnique(['service_id']);
            $table->unique(['kind', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::table('sheet_integrations', function (Blueprint $table): void {
            $table->dropUnique(['kind', 'service_id']);
            $table->unique('service_id');
            $table->dropColumn('kind');
        });
    }
};
