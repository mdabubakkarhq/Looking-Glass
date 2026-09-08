<?php

namespace Database\Factories;

use App\Models\Node;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NodeFactory extends Factory
{
    protected $model = Node::class;

    public function definition(): array
    {
        $name = fake()->city();
        return [
            'uuid' => (string) Str::uuid(),
            'slug' => Str::slug($name) . '-' . fake()->unique()->randomNumber(3),
            'name' => $name,
            'city' => $name,
            'country_code' => fake()->countryCode(),
            'provider' => fake()->company(),
            'asn' => 'AS' . fake()->numberBetween(1000, 65000),
            'ipv4' => fake()->ipv4(),
            'ipv6' => null,
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'uplink_mbps' => fake()->randomElement([1000, 10000]),
            'status' => 'online',
            'maintenance' => false,
            'public' => true,
            'sort_order' => fake()->numberBetween(0, 100),
            'download_host' => null,
            'agent_version' => '1.0.0',
            'last_seen_at' => now(),
        ];
    }

    public function offline(): static
    {
        return $this->state(fn () => ['status' => 'offline']);
    }

    public function maintenance(): static
    {
        return $this->state(fn () => ['maintenance' => true, 'status' => 'maintenance']);
    }

    public function private(): static
    {
        return $this->state(fn () => ['public' => false]);
    }
}
