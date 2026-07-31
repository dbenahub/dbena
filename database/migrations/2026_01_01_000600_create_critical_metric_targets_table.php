<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('critical_metric_targets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('critical_metric_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            // Nullable kerana prototaip mempunyai sasaran bukan-angka: 'Progress'
            // pada baris Sales Collection (Progress Claim). Rujuk soalan terbuka Q6.
            $table->decimal('monthly_target', 14, 2)->nullable();
            $table->string('target_text')->nullable();
            $table->timestamps();

            $table->unique(['critical_metric_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('critical_metric_targets');
    }
};
