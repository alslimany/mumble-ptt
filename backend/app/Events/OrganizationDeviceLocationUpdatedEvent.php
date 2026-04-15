<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrganizationDeviceLocationUpdatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $orgId,
        public readonly int $deviceId,
        public readonly float $latitude,
        public readonly float $longitude,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('organization.'.$this->orgId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'device_id' => $this->deviceId,
            'gps' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
        ];
    }
}
