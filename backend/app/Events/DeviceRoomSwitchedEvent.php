<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceRoomSwitchedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $deviceId,
        public readonly int $roomId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('device.'.$this->deviceId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->roomId,
        ];
    }
}
