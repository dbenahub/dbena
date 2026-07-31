<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\YearGrowthFactor;
use Illuminate\Database\Seeder;

class YearGrowthFactorSeeder extends Seeder
{
    public function run(): void
    {
        // 2026 = 1.0 (asas semasa) — nilai tepat dari prototaip.
        $factors = [
            2023 => 0.58, 2024 => 0.72, 2025 => 0.87, 2026 => 1.00, 2027 => 1.15,
            2028 => 1.30, 2029 => 1.45, 2030 => 1.60, 2031 => 1.75, 2032 => 1.90,
        ];

        foreach ($factors as $year => $factor) {
            YearGrowthFactor::updateOrCreate(['year' => $year], ['factor' => $factor]);
        }
    }
}
