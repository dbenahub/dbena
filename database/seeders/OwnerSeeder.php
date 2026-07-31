<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OwnerStatus;
use App\Models\Owner;
use Illuminate\Database\Seeder;

class OwnerSeeder extends Seeder
{
    public function run(): void
    {
        // 4 PIC teras — tidak boleh dibuang (prototaip: senarai `removable`).
        $core = [
            ['name' => 'ZIKRI',   'color_token' => 'oklch(0.6 0.15 250)'],
            ['name' => 'HAFIZAN', 'color_token' => 'oklch(0.7 0.12 85)'],
            ['name' => 'NIZAM',   'color_token' => 'oklch(0.6 0.16 350)'],
            ['name' => 'AZHARI',  'color_token' => 'oklch(0.6 0.15 145)'],
        ];

        foreach ($core as $owner) {
            Owner::updateOrCreate(['name' => $owner['name']], [
                'color_token' => $owner['color_token'],
                'is_core' => true,
                'is_system' => false,
                'status' => OwnerStatus::Active,
            ]);
        }

        // PEMBETULAN isu #21: 'INFO' muncul sebagai pemilik dalam Data Kritikal
        // prototaip tetapi TIADA dalam senarai PIC Admin Panel, dan dikecualikan
        // daripada pengiraan Prestasi Pemilik Data. Ia label sistem, bukan orang.
        Owner::updateOrCreate(['name' => 'INFO'], [
            'color_token' => 'oklch(0.6 0.02 260)',
            'is_core' => true,
            'is_system' => true,
            'status' => OwnerStatus::Active,
        ]);
    }
}
