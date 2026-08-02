<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OwnerSeeder::class,
            ServiceSeeder::class,
            IndexTierSeeder::class,
            YearGrowthFactorSeeder::class,
            CriticalMetricSeeder::class,
            PrioritySeeder::class,
            AdminSettingSeeder::class,
            UserSeeder::class,
            OrgChartSeeder::class,
            DemoWeeklyDataSeeder::class,
        ]);
    }
}
