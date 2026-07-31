<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('critical_weekly_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('critical_metric_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('week_number'); // 1-4
            $table->decimal('value', 14, 2)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['critical_metric_id', 'year', 'month', 'week_number'], 'cwe_metric_period_unique');
            $table->index(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('critical_weekly_entries');
    }
};
