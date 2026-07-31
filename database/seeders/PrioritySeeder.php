<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Priority;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * 3 keutamaan — nilai tepat dari prototaip.
 * PEMBETULAN isu #26: avatar tidak lagi dari CDN i.pravatar.cc; kami simpan
 * `avatar_seed` dan render inisial secara tempatan.
 */
class PrioritySeeder extends Seeder
{
    private const DATA = [
        ['kabinet',    'Ahmad Hafiz',  'Tingkatkan pencapaian kepada 35% ke atas.',   'Increase achievement to above 35%.',        1],
        ['bina-rumah', 'Mohd Amirul',  'Tutup sekurang-kurangnya 3 projek baharu.',   'Close at least 3 new projects.',            2],
        ['mihrab',     'Nurul Farah',  'Bina momentum kutipan untuk minggu ini.',     'Build collection momentum for this week.',  3],
    ];

    public function run(): void
    {
        foreach (self::DATA as [$serviceKey, $owner, $descMs, $descEn, $order]) {
            $service = Service::where('key', $serviceKey)->firstOrFail();

            Priority::updateOrCreate(
                ['service_id' => $service->id, 'owner_name' => $owner],
                [
                    'title_ms' => $service->name_ms,
                    'title_en' => $service->name_en,
                    'desc_ms' => $descMs,
                    'desc_en' => $descEn,
                    'avatar_seed' => str($owner)->slug()->value(),
                    'icon_class' => $service->icon_class,
                    'sort_order' => $order,
                    'is_active' => true,
                ]
            );
        }
    }
}
