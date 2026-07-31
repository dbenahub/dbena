<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owners', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            // PEMBETULAN isu #12: SATU sumber kebenaran warna PIC. Prototaip
            // menjana warna berbeza di Dashboard (peta+hash) dan Admin Panel
            // (index array) untuk PIC yang sama.
            $table->string('color_token');
            $table->boolean('is_core')->default(false);
            // PEMBETULAN isu #21: 'INFO' bukan PIC sebenar — ia label sistem.
            $table->boolean('is_system')->default(false);
            $table->enum('status', ['active', 'pending_approval', 'rejected'])->default('active')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owners');
    }
};
