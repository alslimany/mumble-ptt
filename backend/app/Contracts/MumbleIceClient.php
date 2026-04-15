<?php

namespace App\Contracts;

interface MumbleIceClient
{
    public function createVirtualServer(int $organizationId): array;

    public function createChannel(int $serverId, string $channelName): int;

    public function registerUser(int $serverId, string $username, string $password): int;

    public function joinChannel(int $serverId, int $userId, int $channelId): bool;
}
