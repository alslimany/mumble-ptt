<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Organization;
use App\Services\MumbleIceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MumbleDeviceRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'jwt.secret' => str_repeat('x', 64),
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
        ]);
    }

    public function test_device_registration_provisions_mumble_server_and_user(): void
    {
        $organization = Organization::factory()->create(['settings' => null]);
        $device = Device::factory()->create([
            'organization_id' => $organization->id,
            'mumble_user_id' => null,
        ]);

        $service = Mockery::mock(MumbleIceService::class);
        $service->shouldReceive('createVirtualServer')->once()->with($organization->id)->andReturn([
            'serverId' => 77,
            'port' => 64750,
            'password' => 'org-pass',
        ]);
        $service->shouldReceive('registerUser')->once()->withArgs(function (int $serverId, string $username, string $password) use ($device) {
            return $serverId === 77
                && $username === $device->unique_identifier
                && $password !== '';
        })->andReturn(2100);

        $this->app->instance(MumbleIceService::class, $service);

        $response = $this->postJson('/api/device/register', [
            'unique_identifier' => $device->unique_identifier,
        ]);

        $response->assertOk()->assertJsonPath('mumble_port', 64750);
        $this->assertNotEmpty($response->json('mumble_password'));

        $organization->refresh();
        $this->assertSame(77, $organization->settings['mumble_server_id']);
        $this->assertSame(64750, $organization->settings['mumble_port']);

        $device->refresh();
        $this->assertSame(2100, $device->mumble_user_id);
    }
}
