<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jadual `projects` digugurkan atas permintaan DBENA (1 Ogos 2026).
     *
     * Senarai Projek tidak pernah menjadi sebahagian aliran kerja DBENA — ia
     * data demo yang diwarisi daripada prototaip. Dua nilai yang bergantung
     * padanya telah ditakrif semula menggunakan data Google Sheet sebenar:
     *
     *   Kadar Penukaran      quotation ÷ lead
     *   Purata Nilai Deal →  Purata Nilai Quotation = amaun ÷ bilangan
     *
     * Migrasi ini TIDAK BOLEH DIPULIHKAN — down() mencipta semula struktur
     * jadual tetapi bukan datanya.
     */
    public function up(): void
    {
        Schema::dropIfExists('projects');
    }

    public function down(): void
    {
        Schema::create('projects', function (Illuminate\Database\Schema\Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('client_name');
            $table->decimal('value', 14, 2)->default(0);
            $table->enum('status', ['selesai', 'dalam_proses', 'menunggu_kelulusan', 'perancangan'])
                ->default('perancangan')->index();
            $table->date('project_date')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
};
