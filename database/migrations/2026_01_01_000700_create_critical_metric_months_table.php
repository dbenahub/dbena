<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Metadata per metrik per BULAN (bukan global seperti prototaip) supaya
        // pelan tindakan & PIC bulan Julai berbeza daripada bulan Ogos.
        Schema::create('critical_metric_months', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('critical_metric_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->foreignId('owner_id')->nullable()->constrained('owners')->nullOnDelete();
            $table->text('action_plan')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['critical_metric_id', 'year', 'month'], 'cmm_metric_year_month_unique');
            $table->index(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('critical_metric_months');
    }
};
