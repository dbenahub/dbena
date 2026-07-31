<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = \App\Models\Service::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'key' => str($name)->slug()->value(),
            'name_ms' => ucfirst($name),
            'name_en' => ucfirst($name),
            'icon_class' => 'ph-wrench',
            'monthly_target' => fake()->numberBetween(40000, 500000),
            'chart_color' => 'oklch(0.6 0.2 350)',
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
