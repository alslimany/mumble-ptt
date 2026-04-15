<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => $this->faker->words(2, true),
            'mumble_channel_id' => (string) $this->faker->numberBetween(1, 100),
        ];
    }
}
