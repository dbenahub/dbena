<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bezakan integrasi Data Kritikal daripada integrasi Master Project.
 *
 * Kedua-duanya menunjuk ke fail Google Sheet yang SAMA tetapi tab yang
 * berbeza, dengan pemetaan lajur yang berbeza sepenuhnya. service_id NULL
 * sudah bermakna "tetapan global", dan uniknya menghalang baris NULL
 * kedua — jadi `kind` ditambah pada kunci unik.
 *
 * ═══ Dua perkara yang mematahkan versi pertama ═══
 *
 * 1. Susunan lajur. MySQL memerlukan indeks pada lajur kunci asing, dan
 *    ia menolak untuk menggugurkan indeks terakhir yang berkhidmat untuk
 *    kunci itu (ralat 1553). Unik (kind, service_id) TIDAK berkhidmat
 *    untuknya kerana service_id bukan lajur pertama. Dengan
 *    (service_id, kind), awalan kiri ialah service_id dan kunci asing
 *    kekal terlindung.
 *
 * 2. Urutan. Indeks pengganti mesti WUJUD sebelum yang lama digugurkan,
 *    bukan selepas.
 *
 * Migrasi ini juga idempoten. Percubaan pertama menambah lajur `kind`
 * kemudian gagal pada perubahan indeks, dan DDL MySQL bukan transaksi —
 * jadi lajur itu kekal manakala migrasi tidak direkodkan sebagai selesai.
 * Menjalankannya semula tanpa semakan ini akan gagal dengan "duplicate
 * column".
 */
return new class extends Migration
{
    private const TABLE = 'sheet_integrations';

    private const OLD_INDEX = 'sheet_integrations_service_id_unique';

    private const NEW_INDEX = 'sheet_integrations_service_id_kind_unique';

    public function up(): void
    {
        if (! Schema::hasColumn(self::TABLE, 'kind')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->string('kind')->default('critical')->after('service_id');
            });
        }

        // Cipta pengganti DAHULU supaya kunci asing tidak pernah tanpa indeks.
        if (! $this->hasIndex(self::NEW_INDEX)) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->unique(['service_id', 'kind'], self::NEW_INDEX);
            });
        }

        if ($this->hasIndex(self::OLD_INDEX)) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->dropUnique(self::OLD_INDEX);
            });
        }
    }

    public function down(): void
    {
        if (! $this->hasIndex(self::OLD_INDEX)) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->unique('service_id', self::OLD_INDEX);
            });
        }

        if ($this->hasIndex(self::NEW_INDEX)) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->dropUnique(self::NEW_INDEX);
            });
        }

        if (Schema::hasColumn(self::TABLE, 'kind')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->dropColumn('kind');
            });
        }
    }

    private function hasIndex(string $name): bool
    {
        foreach (Schema::getIndexes(self::TABLE) as $index) {
            if (($index['name'] ?? null) === $name) {
                return true;
            }
        }

        return false;
    }
};
