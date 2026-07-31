<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('priorities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title_ms')->nullable();
            $table->string('title_en')->nullable();
            $table->text('desc_ms');
            $table->text('desc_en');
            $table->string('owner_name')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('owners')->nullOnDelete();
            $table->string('avatar_seed')->nullable();
            $table->string('icon_class')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('priorities');
    }
};
