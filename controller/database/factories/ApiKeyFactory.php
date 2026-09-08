<?php

namespace Database\Factories;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true) . ' Key',
            'token' => Str::random(64),
            'abilities' => ['*'],
            'rate_limit_per_minute' => fake()->randomElement([30, 60, 120]),
            'last_used_at' => null,
            'expires_at' => null,
        ];
    }
}
