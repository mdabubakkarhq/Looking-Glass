<?php

namespace Database\Factories;

use App\Models\SystemUpdate;
use Illuminate\Database\Eloquent\Factories\Factory;

class SystemUpdateFactory extends Factory
{
    protected $model = SystemUpdate::class;

    public function definition(): array
    {
        return [
            'version' => fake()->semver(),
            'previous_version' => fake()->semver(),
            'status' => 'completed',
            'notes' => fake()->sentence(),
            'metadata' => null,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ];
    }
}
