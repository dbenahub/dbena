<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\IndexTier;
use Illuminate\Database\Seeder;

class IndexTierSeeder extends Seeder
{
    public function run(): void
    {
        // Nilai threshold TEPAT dari prototaip (Admin Panel.dc.html baris 148-154).
        $tiers = [
            ['key' => 'critical',       'name_ms' => 'Kritikal',   'name_en' => 'Critical',       'color_token' => 'oklch(0.6 0.2 25)',   'sort_order' => 0, 'monthly_revenue_threshold' => 0,          'monthly_profit_threshold' => 0],
            ['key' => 'survival',       'name_ms' => 'Bertahan',   'name_en' => 'Survival',       'color_token' => 'oklch(0.75 0.15 70)', 'sort_order' => 1, 'monthly_revenue_threshold' => 457142.86,  'monthly_profit_threshold' => 0],
            ['key' => 'growing',        'name_ms' => 'Berkembang', 'name_en' => 'Growing',        'color_token' => 'oklch(0.62 0.16 300)','sort_order' => 2, 'monthly_revenue_threshold' => 685714.29,  'monthly_profit_threshold' => 80000],
            ['key' => 'stable',         'name_ms' => 'Stabil',     'name_en' => 'Stable',         'color_token' => 'oklch(0.6 0.15 235)', 'sort_order' => 3, 'monthly_revenue_threshold' => 914285.71,  'monthly_profit_threshold' => 160000],
            ['key' => 'sustainability', 'name_ms' => 'Mampan',     'name_en' => 'Sustainability', 'color_token' => 'oklch(0.65 0.16 150)','sort_order' => 4, 'monthly_revenue_threshold' => 1371428.57, 'monthly_profit_threshold' => 320000],
        ];

        foreach ($tiers as $tier) {
            IndexTier::updateOrCreate(['key' => $tier['key']], $tier);
        }
    }
}
