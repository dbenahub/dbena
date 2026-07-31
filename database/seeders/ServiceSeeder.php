<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * PEMBETULAN isu #20 — prototaip menyimpan:
         *   renovation.nameEn = 'Ubah Suai'   (Bahasa Melayu!)
         *   divider.nameEn    = 'Pembahagi'   (Bahasa Melayu!)
         *   mihrab.nameEn     = ''            (kosong)
         * Lajur dibetulkan di bawah.
         * TODO: sahkan dengan DBENA (soalan terbuka Q1 dalam PRD.md §15.1)
         */
        $services = [
            ['key' => 'renovation',  'name_ms' => 'Renovation', 'name_en' => 'Renovation',         'icon_class' => 'ph-wrench',        'monthly_target' => 500000, 'chart_color' => 'oklch(0.6 0.2 350)',  'sort_order' => 1],
            ['key' => 'kabinet',     'name_ms' => 'Kabinet',    'name_en' => 'Cabinetry',          'icon_class' => 'ph-squares-four',  'monthly_target' => 200000, 'chart_color' => 'oklch(0.75 0.15 85)', 'sort_order' => 2],
            ['key' => 'bina-rumah',  'name_ms' => 'Bina Rumah', 'name_en' => 'House Construction', 'icon_class' => 'ph-house-line',    'monthly_target' => 500000, 'chart_color' => 'oklch(0.6 0.16 250)', 'sort_order' => 3],
            ['key' => 'divider',     'name_ms' => 'Divider',    'name_en' => 'Divider',            'icon_class' => 'ph-columns',       'monthly_target' => 40000,  'chart_color' => 'oklch(0.65 0.15 145)','sort_order' => 4],
            ['key' => 'mihrab',      'name_ms' => 'Mihrab',     'name_en' => 'Mihrab',             'icon_class' => 'ph-bank',          'monthly_target' => 80000,  'chart_color' => 'oklch(0.7 0.16 40)',  'sort_order' => 5],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(['key' => $service['key']], $service);
        }
    }
}
