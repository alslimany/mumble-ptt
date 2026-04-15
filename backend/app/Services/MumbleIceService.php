<?php

namespace App\Services;

use App\Contracts\MumbleIceClient;

class MumbleIceService
{
    public function __construct(protected MumbleIceClient $client)
    {
    }

    public function createVirtualServer(int $organizationId): array
    {
        return $this->client->createVirtualServer($organizationId);
    }

    public function createChannel(int $serverId, string $channelName): int
    {
        return $this->client->createChannel($serverId, $channelName);
    }

    public function registerUser(int $serverId, string $username, string $password): int
    {
        return $this->client->registerUser($serverId, $username, $password);
    }

    public function joinChannel(int $serverId, int $userId, int $channelId): bool
    {
        return $this->client->joinChannel($serverId, $userId, $channelId);
    }
}
