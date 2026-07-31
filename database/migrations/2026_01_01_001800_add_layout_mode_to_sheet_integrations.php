<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sheet_integrations', function (Blueprint $table): void {
            /*
             * Sheet DBENA sebenar menyimpan KESEMUA 5 servis dalam satu tab,
             * dipisahkan oleh baris jalur ("Renovation", "Bina Rumah", …).
             *
             * 'multi'  — satu tab, banyak servis, dikesan melalui baris jalur
             * 'single' — satu tab = satu servis (tiada jalur)
             */
            $table->enum('layout_mode', ['multi', 'single'])->default('multi')->after('match_mode');

            // Import Monthly Target (lajur H) sekali dengan nilai mingguan.
            // Lalai MATI — sasaran biasanya milik Admin Panel.
            $table->boolean('import_targets')->default(false)->after('layout_mode');

            // Label yang dikesan sebagai jalur servis semasa sync terakhir.
            $table->json('detected_services')->nullable()->after('import_targets');
        });
    }

    public function down(): void
    {
        Schema::table('sheet_integrations', function (Blueprint $table): void {
            $table->dropColumn(['layout_mode', 'import_targets', 'detected_services']);
        });
    }
};
