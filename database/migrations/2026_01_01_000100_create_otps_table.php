<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // PEMBETULAN isu #1: kod TIDAK PERNAH disimpan sebagai plaintext.
            $table->string('code_hash');
            $table->enum('type', ['login', 'reset'])->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'consumed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
