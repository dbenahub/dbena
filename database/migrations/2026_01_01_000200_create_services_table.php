<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name_ms');
            // PEMBETULAN isu #20: prototaip menyimpan BM dalam lajur nameEn
            // (renovation="Ubah Suai", divider="Pembahagi") dan mihrab kosong.
            $table->string('name_en');
            $table->string('icon_class');
            $table->decimal('monthly_target', 14, 2)->default(0);
            $table->string('chart_color');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
