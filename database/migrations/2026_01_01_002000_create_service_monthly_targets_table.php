<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sasaran jualan per servis, per bulan.
     *
     * Sebelum ini `services.monthly_target` ialah satu nilai yang digunakan
     * untuk kesemua 12 bulan. Perniagaan sebenar bermusim — Ramadan, hujung
     * tahun, dan cuti sekolah tidak sama.
     *
     * Baris di sini MENGATASI nilai asas. Bulan tanpa baris kekal menggunakan
     * `services.monthly_target`, jadi tiada apa yang rosak jika DBENA memilih
     * untuk tidak menetapkan sasaran bulanan.
     */
    public function up(): void
    {
        Schema::create('service_monthly_targets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('target', 14, 2);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['service_id', 'year', 'month'], 'smt_service_year_month_unique');
            $table->index(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_monthly_targets');
    }
};
