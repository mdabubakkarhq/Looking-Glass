<?php

namespace Database\Factories;

use App\Models\DownloadFile;
use App\Models\Node;
use Illuminate\Database\Eloquent\Factories\Factory;

class DownloadFileFactory extends Factory
{
    protected $model = DownloadFile::class;

    public function definition(): array
    {
        return [
            'node_id' => Node::factory(),
            'name' => fake()->words(2, true) . ' Test File',
            'filename' => fake()->numerify('###MB.bin'),
            'size_bytes' => fake()->randomElement([104857600, 1073741824]),
            'size_label' => fake()->randomElement(['100MB', '1GB']),
            'url' => fake()->url(),
            'enabled' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
