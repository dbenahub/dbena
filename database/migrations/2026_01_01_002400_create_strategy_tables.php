<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Strategic Planning & KPI Alignment.
 *
 * Ketiga-tiga jadual ini ialah SALINAN tab strategic planning dalam
 * Google Sheet, bukan rekod utama. Perancangan ditulis dalam sheet; sync
 * menulis ke sini. Tiada borang dalam aplikasi menulis ke jadual ini,
 * termasuk untuk Admin — dua penulis kepada data yang sama bermakna
 * suntingan hilang secara senyap pada sync berikutnya.
 *
 * Barisan dipisahkan daripada petak kerana keduanya menjawab soalan yang
 * berbeza: petak ialah sasaran yang perlu dihafal, jadual ialah cara
 * mencapainya. Menyimpannya dalam satu jadual bermakna setengah lajur
 * kosong pada setiap baris.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('strategy_plans')) {
            Schema::create('strategy_plans', function (Blueprint $table): void {
                $table->id();

                // Satu pelan setiap servis. Sync menulis ganti mengikut
                // service_id, jadi menyegerak dua kali tidak menghasilkan
                // dua pelan.
                $table->foreignId('service_id')->unique()->constrained()->cascadeOnDelete();

                $table->string('heading')->nullable();
                $table->text('vision')->nullable();

                $table->timestamp('synced_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('strategy_tiles')) {
            Schema::create('strategy_tiles', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('service_id')->constrained()->cascadeOnDelete();

                $table->unsignedSmallInteger('position');
                $table->string('label');

                // Nilai disimpan sebagai teks, bukan nombor.
                //
                // Sheet mengandungi "RM500,000", "> RM600,000", "100%" dan
                // "1". Menghuraikannya menjadi perpuluhan membuang tanda
                // lebih-daripada dan simbol peratus, dan tiada satu pun
                // daripada nombor ini pernah dikira — ia dipaparkan.
                $table->string('value');
                $table->string('unit')->nullable();
                $table->string('icon')->nullable();

                $table->timestamps();

                $table->unique(['service_id', 'position']);
            });
        }

        if (! Schema::hasTable('strategy_rows')) {
            Schema::create('strategy_rows', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('service_id')->constrained()->cascadeOnDelete();

                $table->unsignedSmallInteger('position');

                $table->string('kra');
                $table->text('kpi')->nullable();
                $table->text('target')->nullable();
                $table->text('tactics')->nullable();
                $table->text('initiatives')->nullable();
                $table->string('timeline')->nullable();

                // Nama PIC seperti tertulis dalam sheet. TIDAK dipautkan
                // ke jadual owners: pelan boleh menamakan HOD yang belum
                // wujud sebagai pemilik metrik, dan kekunci asing akan
                // menolak baris itu dan menyembunyikan sebahagian pelan.
                $table->string('pic')->nullable();

                $table->unsignedInteger('source_row')->nullable();
                $table->timestamps();

                $table->index(['service_id', 'position']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('strategy_rows');
        Schema::dropIfExists('strategy_tiles');
        Schema::dropIfExists('strategy_plans');
    }
};
