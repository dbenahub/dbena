<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('year_growth_factors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->decimal('factor', 6, 4)->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('year_growth_factors');
    }
};
