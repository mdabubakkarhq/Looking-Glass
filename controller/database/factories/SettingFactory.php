<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'group' => 'general',
            'key' => fake()->unique()->slug(),
            'value' => fake()->numerify('value-###'),
            'type' => 'string',
            'description' => fake()->sentence(3),
            'public' => false,
        ];
    }

    public function public(): static
    {
        return $this->state(fn () => ['public' => true]);
    }
}
