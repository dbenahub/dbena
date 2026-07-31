<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jejak audit setiap larian sync — supaya "kenapa nombor saya tak berubah?"
        // boleh dijawab dengan fakta, bukan tekaan.
        Schema::create('sheet_sync_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sheet_integration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('trigger', ['schedule', 'manual', 'webhook'])->index();
            $table->enum('status', ['success', 'partial', 'failed'])->index();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedTinyInteger('month')->nullable();
            $table->unsignedInteger('rows_read')->default(0);
            $table->unsignedInteger('rows_matched')->default(0);
            $table->unsignedInteger('values_updated')->default(0);
            $table->unsignedInteger('rows_skipped')->default(0);
            $table->json('unmatched_labels')->nullable();
            $table->text('message')->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sheet_sync_logs');
    }
};
