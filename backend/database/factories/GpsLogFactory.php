<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\GpsLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GpsLog>
 */
class GpsLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'recorded_at' => now(),
        ];
    }
}
