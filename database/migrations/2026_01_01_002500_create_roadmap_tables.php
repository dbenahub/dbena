<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Roadmap Tahunan Servis.
 *
 * Tidak seperti Data Kritikal, Projek dan Strategic Planning, roadmap
 * DITULIS dalam aplikasi. Ia enam puluh sel setahun yang berubah beberapa
 * kali sahaja — memaksanya melalui Google Sheet bermakna satu pusingan
 * sync untuk menukar satu bulan daripada Pause kepada Sambung Semula.
 *
 * Kerana aplikasi ialah penulisnya, kebenaran mesti dikuatkuasakan di
 * lapisan dasar dan bukan dengan menyembunyikan butang.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roadmap_plans')) {
            Schema::create('roadmap_plans', function (Blueprint $table): void {
                $table->id();

                // Satu pelan setahun. Roadmap tahun lepas kekal boleh
                // dibaca — perancangan tahun hadapan bermula dengan
                // melihat apa yang dijanjikan tahun ini.
                $table->unsignedSmallInteger('year')->unique();

                $table->string('title')->nullable();
                $table->string('subtitle')->nullable();

                // Baris RINGKASAN STRATEGI di kaki reka bentuk.
                $table->json('summary')->nullable();

                // ID kalendar Google yang dikongsi dengan service account.
                $table->string('calendar_id')->nullable();

                $table->timestamps();
            });
        }

        if (! Schema::hasTable('roadmap_cells')) {
            Schema::create('roadmap_cells', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('service_id')->constrained()->cascadeOnDelete();

                $table->unsignedSmallInteger('year');
                $table->unsignedTinyInteger('month');

                $table->string('status')->default('none');
                $table->string('note')->nullable();

                $table->timestamps();

                // Satu sel setiap servis setiap bulan setiap tahun. Tanpa
                // ini, klik pantas dua kali dalam editor menghasilkan dua
                // baris dan sel itu mula berkelip antara dua status.
                $table->unique(['service_id', 'year', 'month']);
                $table->index(['year', 'month']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('roadmap_cells');
        Schema::dropIfExists('roadmap_plans');
    }
};
