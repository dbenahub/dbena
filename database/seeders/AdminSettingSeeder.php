<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AdminSetting;
use App\Models\SheetIntegration;
use Illuminate\Database\Seeder;

class AdminSettingSeeder extends Seeder
{
    public function run(): void
    {
        AdminSetting::updateOrCreate(['key' => 'company_name'], ['value' => 'DBENA SDN BHD']);
        AdminSetting::updateOrCreate(['key' => 'report_email'], ['value' => 'dbenareport@gmail.com']);

        /*
         * Integrasi sheet global — dipra-isi dengan susun atur sheet DBENA
         * sebenar, jadi setup hanya memerlukan: tampal pautan → hidupkan sync.
         *
         *   A DATA CRITICAL │ B-E Week 1-4 │ F Data Type │ G Monthly Actual
         *   H Monthly Target │ I Data Status │ J Data Owner │ K Action Plan
         *
         * Baris 1 ialah banner arahan, jadi header_row dibiarkan 0 supaya
         * baris header dikesan secara automatik.
         */
        SheetIntegration::firstOrCreate(['service_id' => null], [
            'url' => null,
            'connected' => false,
            'sync_enabled' => false,
            'layout_mode' => 'multi',
            'header_row' => 0,
            'match_mode' => 'label',
            'import_targets' => false,
            'column_map' => [
                'metric' => 'A',
                'week1' => 'B',
                'week2' => 'C',
                'week3' => 'D',
                'week4' => 'E',
                'data_type' => 'F',
                'target' => 'H',
                'owner' => 'J',
                'action_plan' => 'K',
            ],
        ]);
    }
}
