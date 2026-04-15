<?php

namespace Tests\Feature;

use App\Events\DeviceRoomSwitchedEvent;
use App\Models\Device;
use App\Models\Organization;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BroadcastForceSwitchRoomTest extends TestCase
{
    use RefreshDatabase;

    public function test_force_switch_room_command_dispatches_event(): void
    {
        Event::fake([DeviceRoomSwitchedEvent::class]);

        $org = Organization::factory()->create();
        $device = Device::factory()->create(['organization_id' => $org->id]);
        $room = Room::factory()->create(['organization_id' => $org->id]);

        $this->artisan('ptt:force-switch-room', [
            'deviceId' => $device->id,
            'roomId'   => $room->id,
        ])->assertExitCode(0);

        Event::assertDispatched(DeviceRoomSwitchedEvent::class, function (DeviceRoomSwitchedEvent $event) use ($device, $room) {
            return $event->deviceId === $device->id && $event->roomId === $room->id;
        });
    }

    public function test_force_switch_room_command_creates_room_device_pivot(): void
    {
        Event::fake([DeviceRoomSwitchedEvent::class]);

        $org = Organization::factory()->create();
        $device = Device::factory()->create(['organization_id' => $org->id]);
        $room = Room::factory()->create(['organization_id' => $org->id]);

        $this->artisan('ptt:force-switch-room', [
            'deviceId' => $device->id,
            'roomId'   => $room->id,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('room_devices', [
            'device_id' => $device->id,
            'room_id'   => $room->id,
            'can_switch' => true,
        ]);
    }

    public function test_force_switch_room_command_fails_for_unknown_device(): void
    {
        $this->artisan('ptt:force-switch-room', [
            'deviceId' => 99999,
            'roomId'   => 1,
        ])->assertExitCode(1);
    }

    public function test_force_switch_room_command_fails_for_unknown_room(): void
    {
        $device = Device::factory()->create();

        $this->artisan('ptt:force-switch-room', [
            'deviceId' => $device->id,
            'roomId'   => 99999,
        ])->assertExitCode(1);
    }

    public function test_device_room_switched_event_broadcasts_on_correct_channel(): void
    {
        $event = new DeviceRoomSwitchedEvent(deviceId: 42, roomId: 7);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertEquals('private-device.42', $channels[0]->name);
    }

    public function test_device_room_switched_event_broadcast_payload(): void
    {
        $event = new DeviceRoomSwitchedEvent(deviceId: 1, roomId: 5);

        $this->assertEquals(['room_id' => 5], $event->broadcastWith());
    }
}
