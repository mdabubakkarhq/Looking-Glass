<?php

namespace Database\Factories;

use App\Models\NetworkTest;
use App\Models\Node;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NetworkTestFactory extends Factory
{
    protected $model = NetworkTest::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'node_id' => Node::factory(),
            'test_type' => fake()->randomElement(['ping', 'traceroute', 'mtr', 'dns']),
            'target' => fake()->domainName(),
            'resolved_ip' => null,
            'ip_family' => 'auto',
            'status' => 'pending',
            'visitor_hash' => hash('sha256', fake()->ipv4()),
            'started_at' => null,
            'completed_at' => null,
            'runtime_ms' => null,
            'packet_loss' => null,
            'latency_min' => null,
            'latency_avg' => null,
            'latency_max' => null,
            'latency_stddev' => null,
            'hop_count' => null,
            'error_code' => null,
            'error_message' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'started_at' => now()->subSeconds(3),
            'completed_at' => now(),
            'runtime_ms' => 3120,
            'packet_loss' => 0.0,
            'latency_min' => 1.23,
            'latency_avg' => 2.45,
            'latency_max' => 4.67,
        ]);
    }

    public function running(): static
    {
        return $this->state(fn () => [
            'status' => 'running',
            'started_at' => now()->subSeconds(1),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'started_at' => now()->subSeconds(3),
            'completed_at' => now(),
            'error_code' => 'timeout',
            'error_message' => 'Test timed out.',
        ]);
    }
}
