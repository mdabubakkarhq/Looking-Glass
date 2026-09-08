<?php

namespace Database\Factories;

use App\Models\NodeCapability;
use App\Models\Node;
use Illuminate\Database\Eloquent\Factories\Factory;

class NodeCapabilityFactory extends Factory
{
    protected $model = NodeCapability::class;

    public function definition(): array
    {
        $feature = fake()->randomElement(['ping', 'traceroute', 'mtr', 'dns']);
        return [
            'node_id' => Node::factory(),
            'feature' => $feature,
            'enabled' => true,
            'max_concurrent' => ['ping' => 20, 'traceroute' => 8, 'mtr' => 5, 'dns' => 20][$feature],
            'timeout_seconds' => ['ping' => 30, 'traceroute' => 60, 'mtr' => 60, 'dns' => 15][$feature],
        ];
    }
}
