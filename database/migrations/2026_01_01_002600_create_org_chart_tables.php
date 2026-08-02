<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Carta Organisasi.
 *
 * Kedudukan disimpan secara eksplisit (x, y) dan bukan diterbitkan
 * daripada hierarki. Carta DBENA mempunyai susunan yang dipilih dengan
 * tangan — freelancer digantung di bawah dan ke tepi, jabatan disusun
 * mengikut lebar halaman — dan susunan automatik akan membuangnya setiap
 * kali seseorang menambah satu kotak.
 *
 * Sambungan disimpan berasingan daripada kedudukan. Satu kotak boleh
 * mempunyai beberapa garisan masuk, dan mengikat garisan kepada medan
 * 'parent' tunggal bermakna hubungan kedua tidak boleh dilukis langsung.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('org_nodes')) {
            Schema::create('org_nodes', function (Blueprint $table): void {
                $table->id();

                $table->string('title')->nullable();
                $table->string('name')->nullable();
                $table->string('icon')->default('ph-user');
                $table->string('style')->default('department');

                // Koordinat kanvas dalam piksel.
                $table->integer('x')->default(0);
                $table->integer('y')->default(0);
                $table->unsignedSmallInteger('width')->default(200);

                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('org_links')) {
            Schema::create('org_links', function (Blueprint $table): void {
                $table->id();

                $table->foreignId('from_node_id')->constrained('org_nodes')->cascadeOnDelete();
                $table->foreignId('to_node_id')->constrained('org_nodes')->cascadeOnDelete();

                $table->string('style')->default('solid');
                $table->timestamps();

                // Garisan pendua kelihatan lebih tebal daripada yang lain
                // dan tiada siapa dapat tahu sebabnya.
                $table->unique(['from_node_id', 'to_node_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('org_links');
        Schema::dropIfExists('org_nodes');
    }
};
