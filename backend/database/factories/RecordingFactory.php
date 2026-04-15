<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Recording;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recording>
 */
class RecordingFactory extends Factory
{
    public function definition(): array
    {
        $org = Organization::factory()->create();

        return [
            'organization_id' => $org->id,
            'room_id' => Room::factory()->create(['organization_id' => $org->id])->id,
            'file_path' => 'recordings/' . $this->faker->uuid() . '.ogg',
            'duration' => $this->faker->numberBetween(10, 3600),
            'started_at' => now()->subMinutes(5),
            'ended_at' => now(),
        ];
    }
}
