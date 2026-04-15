<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Organization;
use App\Models\Room;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $organizations = Organization::factory(2)->create();

        foreach ($organizations as $org) {
            $devices = Device::factory(3)->create(['organization_id' => $org->id]);
            $rooms = Room::factory(2)->create(['organization_id' => $org->id]);

            foreach ($devices as $device) {
                foreach ($rooms as $room) {
                    $device->rooms()->attach($room->id, ['can_switch' => false]);
                }
            }
        }
    }
}
