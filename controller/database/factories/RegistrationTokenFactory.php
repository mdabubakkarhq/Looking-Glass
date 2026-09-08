<?php

namespace Database\Factories;

use App\Models\RegistrationToken;
use App\Models\Node;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RegistrationTokenFactory extends Factory
{
    protected $model = RegistrationToken::class;

    public function definition(): array
    {
        return [
            'node_id' => Node::factory(),
            'token' => Str::random(64),
            'expires_at' => now()->addHours(24),
            'used_at' => null,
            'used_by_ip' => null,
            'created_by' => User::factory(),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subHours(1),
        ]);
    }

    public function used(): static
    {
        return $this->state(fn () => [
            'used_at' => now()->subMinutes(30),
            'used_by_ip' => fake()->ipv4(),
        ]);
    }
}
