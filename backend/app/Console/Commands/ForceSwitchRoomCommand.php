<?php

namespace App\Console\Commands;

use App\Events\DeviceRoomSwitchedEvent;
use App\Models\Device;
use App\Models\Room;
use App\Models\RoomDevice;
use Illuminate\Console\Command;

class ForceSwitchRoomCommand extends Command
{
    protected $signature = 'ptt:force-switch-room {deviceId : The ID of the device} {roomId : The ID of the room to switch to}';

    protected $description = 'Force a device to switch to a specific room and broadcast the change';

    public function handle(): int
    {
        $deviceId = (int) $this->argument('deviceId');
        $roomId = (int) $this->argument('roomId');

        $device = Device::find($deviceId);
        if (! $device) {
            $this->error("Device [{$deviceId}] not found.");

            return self::FAILURE;
        }

        $room = Room::find($roomId);
        if (! $room) {
            $this->error("Room [{$roomId}] not found.");

            return self::FAILURE;
        }

        RoomDevice::updateOrCreate(
            ['device_id' => $deviceId, 'room_id' => $roomId],
            ['can_switch' => true],
        );

        DeviceRoomSwitchedEvent::dispatch($deviceId, $roomId);

        $this->info("Device [{$deviceId}] has been switched to room [{$roomId}] and the event was broadcast.");

        return self::SUCCESS;
    }
}
