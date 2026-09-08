<?php

namespace Database\Factories;

use App\Models\NodeCredential;
use App\Models\Node;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NodeCredentialFactory extends Factory
{
    protected $model = NodeCredential::class;

    public function definition(): array
    {
        return [
            'node_id' => Node::factory(),
            'node_key_id' => 'lk_' . Str::random(32),
            'node_secret' => hash('sha256', Str::random(64)),
            'agent_ip' => fake()->ipv4(),
            'last_authenticated_at' => now(),
            'active' => true,
        ];
    }
}
