<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('index_tiers', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name_ms');
            $table->string('name_en');
            $table->string('color_token');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->decimal('monthly_revenue_threshold', 14, 2)->default(0);
            $table->decimal('monthly_profit_threshold', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('index_tiers');
    }
};
