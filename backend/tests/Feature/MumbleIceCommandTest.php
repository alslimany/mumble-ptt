<?php

namespace Tests\Feature;

use App\Services\MumbleIceService;
use Mockery;
use Tests\TestCase;

class MumbleIceCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_mumble_test_ice_command_runs_expected_service_calls(): void
    {
        $service = Mockery::mock(MumbleIceService::class);
        $service->shouldReceive('createVirtualServer')->once()->with(99)->andReturn([
            'serverId' => 500,
            'port' => 64739,
            'password' => 'server-pass',
        ]);
        $service->shouldReceive('createChannel')->once()->with(500, 'test-room')->andReturn(42);
        $service->shouldReceive('registerUser')->once()->withArgs(function (int $serverId, string $username, string $password) {
            return $serverId === 500
                && str_starts_with($username, 'ice-test-user-')
                && $password !== '';
        })->andReturn(1234);
        $service->shouldReceive('joinChannel')->once()->with(500, 1234, 42)->andReturn(true);

        $this->app->instance(MumbleIceService::class, $service);

        $this->artisan('mumble:test-ice 99')
            ->expectsOutput('Mumble Ice test passed.')
            ->assertExitCode(0);
    }
}
