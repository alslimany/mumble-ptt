<?php

namespace App\Services;

use App\Contracts\MumbleIceClient;
use RuntimeException;

class MurmurIceClient implements MumbleIceClient
{
    protected mixed $communicator = null;

    protected mixed $metaProxy = null;

    public function __construct(protected ?string $host = null, protected ?int $port = null, protected ?string $secret = null)
    {
        $this->host = $host ?? (string) config('services.mumble.ice_host', 'murmur');
        $this->port = $port ?? (int) config('services.mumble.ice_port', 6502);
        $this->secret = $secret ?? (string) config('services.mumble.ice_secret');
    }

    public function createVirtualServer(int $organizationId): array
    {
        $server = $this->metaProxy()->newServer();
        $serverId = (int) $this->callFirst($server, ['id', 'getId']);
        $port = (int) $this->callFirst($server, ['getConf'], ['port']);
        $password = bin2hex(random_bytes(8));

        $this->callFirst($server, ['setConf'], ['registername', 'org-'.$organizationId]);
        $this->callFirst($server, ['setConf'], ['serverpassword', $password]);

        return [
            'serverId' => $serverId,
            'port' => $port,
            'password' => $password,
        ];
    }

    public function createChannel(int $serverId, string $channelName): int
    {
        return (int) $this->callFirst($this->server($serverId), ['addChannel'], [$channelName, 0]);
    }

    public function registerUser(int $serverId, string $username, string $password): int
    {
        return (int) $this->callFirst(
            $this->server($serverId),
            ['registerUser'],
            [['name' => $username, 'password' => $password]]
        );
    }

    public function joinChannel(int $serverId, int $userId, int $channelId): bool
    {
        $server = $this->server($serverId);

        if (method_exists($server, 'moveUserToChannel')) {
            $server->moveUserToChannel($userId, $channelId);
        } elseif (method_exists($server, 'setUserChannel')) {
            $server->setUserChannel($userId, $channelId);
        } else {
            throw new RuntimeException('Unable to move user to a channel with current Murmur Ice bindings.');
        }

        if (method_exists($server, 'getState')) {
            $state = $server->getState($userId);
            return (int) $this->extractValue($state, ['channel', 'channelId']) === $channelId;
        }

        return true;
    }

    protected function server(int $serverId): mixed
    {
        return $this->callFirst($this->metaProxy(), ['getServer'], [$serverId]);
    }

    protected function metaProxy(): mixed
    {
        if ($this->metaProxy) {
            return $this->metaProxy;
        }

        if (! function_exists('Ice\\initialize') && ! class_exists('\Ice\Communicator')) {
            throw new RuntimeException('PHP Ice extension is not installed. Install it in the runtime before using MumbleIceService.');
        }

        $initData = new \Ice\InitializationData;
        $properties = \Ice\createProperties();
        $properties->setProperty('Ice.ImplicitContext', 'Shared');
        $initData->properties = $properties;

        $this->communicator = \Ice\initialize($initData);

        if ($this->secret !== '') {
            $this->communicator->getImplicitContext()->put('secret', $this->secret);
        }

        $proxy = $this->communicator->stringToProxy(sprintf('Meta:tcp -h %s -p %d -t 5000', $this->host, $this->port));

        foreach (['\Murmur\MetaPrx', '\Murmur_MetaPrx', '\Murmur_MetaPrxHelper'] as $className) {
            if (class_exists($className) && method_exists($className, 'checkedCast')) {
                $meta = $className::checkedCast($proxy);
                if ($meta) {
                    $this->metaProxy = $meta;
                    return $this->metaProxy;
                }
            }
        }

        throw new RuntimeException('Unable to resolve Murmur Meta proxy. Ensure generated Murmur Ice PHP bindings are available.');
    }

    protected function callFirst(mixed $target, array $methods, array $arguments = []): mixed
    {
        foreach ($methods as $method) {
            if (method_exists($target, $method)) {
                return $target->{$method}(...$arguments);
            }
        }

        throw new RuntimeException('None of the expected methods are available: '.implode(', ', $methods));
    }

    protected function extractValue(mixed $value, array $keys): mixed
    {
        if (is_array($value)) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $value)) {
                    return $value[$key];
                }
            }
        }

        if (is_object($value)) {
            foreach ($keys as $key) {
                if (isset($value->{$key})) {
                    return $value->{$key};
                }
            }
        }

        return null;
    }
}
