<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MetricType;
use App\Enums\MetricValueType;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class CriticalMetricFactory extends Factory
{
    protected $model = \App\Models\CriticalMetric::class;

    public function definition(): array
    {
        $key = fake()->unique()->slug(2, false);

        return [
            'service_id' => Service::factory(),
            'metric_key' => $key,
            'label_ms' => ucfirst(str_replace('-', ' ', $key)),
            'label_en' => ucfirst(str_replace('-', ' ', $key)),
            'type' => MetricType::Total,
            'value_type' => MetricValueType::Currency,
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
