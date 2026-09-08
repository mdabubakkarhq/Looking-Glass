<?php

namespace Database\Factories;

use App\Models\RateLimitEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

class RateLimitEventFactory extends Factory
{
    protected $model = RateLimitEvent::class;

    public function definition(): array
    {
        return [
            'visitor_hash' => hash('sha256', fake()->ipv4()),
            'endpoint' => 'tests',
            'reason' => 'per_ip',
            'node_id' => null,
            'created_at' => now(),
        ];
    }
}
