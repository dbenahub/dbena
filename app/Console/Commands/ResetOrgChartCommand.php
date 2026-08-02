<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\OrgLink;
use App\Models\OrgNode;
use Database\Seeders\OrgChartSeeder;
use Illuminate\Console\Command;

/**
 * Bina semula carta organisasi kepada susunan rasmi.
 *
 * Seeder berundur sebaik ada nod, supaya sync semula tidak boleh membuang
 * kedudukan yang telah diseret dengan tangan. Perintah ini ialah cara
 * SENGAJA untuk mengatasi perlindungan itu — dinamakan dengan jelas dan
 * meminta pengesahan, kerana ia memadam setiap suntingan yang pernah
 * dibuat pada carta.
 */
class ResetOrgChartCommand extends Command
{
    protected $signature = 'dbena:carta-reset {--force : Langkau soalan pengesahan}';

    protected $description = 'Bina semula Carta Organisasi kepada susunan rasmi DBENA';

    public function handle(): int
    {
        $bilangan = OrgNode::count();

        if ($bilangan > 0 && ! $this->option('force')) {
            $this->warn("Carta semasa mempunyai {$bilangan} kotak. Semua akan dipadam.");

            if (! $this->confirm('Teruskan?', false)) {
                $this->info('Dibatalkan. Tiada apa-apa diubah.');

                return self::SUCCESS;
            }
        }

        // Garisan dahulu: kekunci asing menghalang pemadaman nod yang masih
        // mempunyai garisan menuding kepadanya.
        OrgLink::query()->delete();
        OrgNode::query()->delete();

        (new OrgChartSeeder)->run();

        $this->info('Carta dibina semula: '.OrgNode::count().' kotak, '.OrgLink::count().' garisan.');

        return self::SUCCESS;
    }
}
