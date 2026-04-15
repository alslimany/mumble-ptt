<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_register_returns_mumble_credentials(): void
    {
        $org = Organization::factory()->create();
        $device = Device::factory()->create(['organization_id' => $org->id]);

        $response = $this->postJson('/api/device/register', [
            'unique_identifier' => $device->unique_identifier,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'ws_token', 'mumble_host', 'mumble_port', 'mumble_username', 'mumble_password']);
    }

    public function test_device_register_returns_404_for_unknown_device(): void
    {
        $response = $this->postJson('/api/device/register', [
            'unique_identifier' => 'nonexistent-device-uuid',
        ]);

        $response->assertStatus(404);
    }

    public function test_device_without_organization_returns_403(): void
    {
        $device = Device::factory()->create(['organization_id' => null]);

        $response = $this->postJson('/api/device/register', [
            'unique_identifier' => $device->unique_identifier,
        ]);

        $response->assertStatus(403);
    }
}
