<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'unique_identifier' => $this->faker->uuid(),
            'name' => $this->faker->words(2, true),
            'model' => $this->faker->word(),
            'is_active' => true,
        ];
    }
}
