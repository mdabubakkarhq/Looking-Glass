<?php

namespace Database\Factories;

use App\Models\NetworkTestEvent;
use App\Models\NetworkTest;
use Illuminate\Database\Eloquent\Factories\Factory;

class NetworkTestEventFactory extends Factory
{
    protected $model = NetworkTestEvent::class;

    public function definition(): array
    {
        return [
            'network_test_id' => NetworkTest::factory(),
            'event_type' => 'output',
            'data' => ['line' => fake()->sentence()],
            'occurred_at' => now(),
        ];
    }
}
