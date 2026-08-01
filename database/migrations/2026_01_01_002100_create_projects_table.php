<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master List of Project.
 *
 * Jadual ini ialah SALINAN tab Master Project dalam Google Sheet, bukan
 * rekod utama. Data dimasukkan dalam sheet; sync menulis ke sini. Tiada
 * borang dalam aplikasi menulis ke jadual ini, termasuk untuk Admin —
 * dua penulis kepada data yang sama bermakna suntingan hilang secara
 * senyap pada sync berikutnya.
 *
 * (Jadual `projects` yang lama digugurkan dalam migrasi 001900. Ia
 * memegang senarai tugasan per servis, bukan projek pelanggan, dan tiada
 * kaitan dengan jadual ini selain namanya.)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Migrasi 002200 gagal pada percubaan pertama SELEPAS migrasi ini
        // berjaya, dan kegagalan itu menghalang kedua-duanya daripada
        // direkodkan. Jadual ini akan wujud pada percubaan seterusnya.
        if (Schema::hasTable('projects')) {
            return;
        }

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();

            // Kod projek ialah kunci dari sheet. Sync memadankan mengikut
            // kod, jadi membetulkan baris dalam sheet mengemas kini baris
            // yang sama di sini dan bukan mencipta pendua.
            $table->string('code')->unique();

            $table->foreignId('service_id')->constrained()->cascadeOnDelete();

            $table->date('project_date')->nullable();
            $table->string('client_name');
            $table->string('pic_sales')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();

            $table->decimal('contract_amount', 14, 2)->default(0);
            $table->decimal('variation_order', 14, 2)->default(0);

            $table->string('status')->default('pending');

            // Baris sheet yang menghasilkan rekod ini — memudahkan mencari
            // sumber apabila nombor kelihatan salah.
            $table->unsignedInteger('source_row')->nullable();
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();

            $table->index(['service_id', 'status']);
            $table->index('project_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
