<?php

namespace Database\Factories;

use App\Models\SecurityEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

class SecurityEventFactory extends Factory
{
    protected $model = SecurityEvent::class;

    public function definition(): array
    {
        return [
            'event_type' => 'agent_auth_failure',
            'severity' => fake()->randomElement(['info', 'warning', 'critical']),
            'source_ip' => fake()->ipv4(),
            'visitor_hash' => null,
            'node_id' => null,
            'metadata' => null,
            'description' => fake()->sentence(),
            'created_at' => now(),
        ];
    }
}
