<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('critical_metrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('metric_key');
            $table->string('label_ms');
            $table->string('label_en');
            $table->enum('type', ['total', 'avg'])->default('total');
            $table->enum('value_type', ['currency', 'number'])->default('currency');
            $table->foreignId('default_owner_id')->nullable()->constrained('owners')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['service_id', 'metric_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('critical_metrics');
    }
};
