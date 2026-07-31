<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sheet_integrations', function (Blueprint $table): void {
            // service_id NULL bermakna tetapan lalai global.
            $table->id();
            $table->foreignId('service_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('url')->nullable();
            $table->boolean('connected')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('service_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sheet_integrations');
    }
};
