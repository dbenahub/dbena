<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sheet_integrations', function (Blueprint $table): void {
            // ID sheet + tab, diekstrak automatik daripada URL yang ditampal.
            $table->string('spreadsheet_id')->nullable()->after('url');
            $table->string('tab_name')->nullable()->after('spreadsheet_id');
            $table->string('gid')->nullable()->after('tab_name');

            // Pemetaan lajur — membolehkan sheet SEDIA ADA digunakan tanpa diubah.
            // Bentuk: {"metric":"A","week1":"C","week2":"D","week3":"E","week4":"F"}
            $table->json('column_map')->nullable()->after('gid');
            $table->unsignedTinyInteger('header_row')->default(1)->after('column_map');

            // Padanan baris metrik: 'label' (padan teks) atau 'key' (padan metric_key)
            $table->enum('match_mode', ['label', 'key'])->default('label')->after('header_row');

            $table->boolean('sync_enabled')->default(false)->after('connected');
            $table->enum('last_sync_status', ['pending', 'success', 'partial', 'failed'])
                ->nullable()->after('last_synced_at');
            $table->text('last_sync_message')->nullable()->after('last_sync_status');
            $table->unsignedInteger('last_sync_rows')->default(0)->after('last_sync_message');

            // Rahsia untuk webhook Apps Script (tolak permintaan tanpa tandatangan sah).
            $table->string('webhook_secret', 64)->nullable()->after('last_sync_rows');
        });
    }

    public function down(): void
    {
        Schema::table('sheet_integrations', function (Blueprint $table): void {
            $table->dropColumn([
                'spreadsheet_id', 'tab_name', 'gid', 'column_map', 'header_row',
                'match_mode', 'sync_enabled', 'last_sync_status', 'last_sync_message',
                'last_sync_rows', 'webhook_secret',
            ]);
        });
    }
};
