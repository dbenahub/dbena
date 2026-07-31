<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = \App\Models\Project::class;

    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'name' => fake()->words(3, true),
            'client_name' => fake()->name(),
            'value' => fake()->numberBetween(5000, 500000),
            'status' => fake()->randomElement(ProjectStatus::cases()),
            'project_date' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
