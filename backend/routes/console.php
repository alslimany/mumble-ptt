<?php

use App\Services\MumbleIceService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mumble:test-ice {organizationId=1}', function (int $organizationId) {
    $service = app(MumbleIceService::class);

    $server = $service->createVirtualServer($organizationId);
    $channelId = $service->createChannel($server['serverId'], 'test-room');

    $username = 'ice-test-user-'.str()->random(6);
    $password = str()->random(16);
    $userId = $service->registerUser($server['serverId'], $username, $password);
    $joined = $service->joinChannel($server['serverId'], $userId, $channelId);

    if (! $joined) {
        $this->error('User channel membership verification failed.');

        return self::FAILURE;
    }

    $this->info('Mumble Ice test passed.');
    $this->line('serverId='.$server['serverId']);
    $this->line('port='.$server['port']);
    $this->line('channelId='.$channelId);
    $this->line('userId='.$userId);
    $this->line('username='.$username);
    $this->line('password='.$password);

    return self::SUCCESS;
})->purpose('Test Mumble Murmur Ice integration end-to-end');
