<?php

namespace Database\Factories;

use App\Models\NodeHeartbeat;
use App\Models\Node;
use Illuminate\Database\Eloquent\Factories\Factory;

class NodeHeartbeatFactory extends Factory
{
    protected $model = NodeHeartbeat::class;

    public function definition(): array
    {
        return [
            'node_id' => Node::factory(),
            'agent_version' => '1.0.0',
            'hostname' => fake()->domainWord(),
            'os' => 'linux',
            'cpu_usage_percent' => fake()->numberBetween(1, 80),
            'memory_usage_percent' => fake()->numberBetween(10, 80),
            'disk_usage_percent' => fake()->numberBetween(10, 70),
            'active_tests' => fake()->numberBetween(0, 5),
            'uptime_seconds' => fake()->numberBetween(3600, 864000),
            'load_average' => [0.5, 0.3, 0.2],
            'sent_at' => now(),
        ];
    }
}
