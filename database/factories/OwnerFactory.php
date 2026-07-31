<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OwnerStatus;
use App\Models\Owner;
use Illuminate\Database\Eloquent\Factories\Factory;

class OwnerFactory extends Factory
{
    protected $model = Owner::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper(fake()->unique()->firstName()),
            'color_token' => fake()->randomElement(Owner::PALETTE),
            'is_core' => false,
            'is_system' => false,
            'status' => OwnerStatus::Active,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => OwnerStatus::PendingApproval]);
    }

    public function core(): static
    {
        return $this->state(fn () => ['is_core' => true]);
    }
}
