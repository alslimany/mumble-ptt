<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\GpsLog;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GpsLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_gps_insert(): void
    {
        $org = Organization::factory()->create();
        $device = Device::factory()->create(['organization_id' => $org->id]);

        $points = [
            ['device_id' => $device->id, 'latitude' => 48.8566, 'longitude' => 2.3522, 'recorded_at' => now()->toIso8601String()],
            ['device_id' => $device->id, 'latitude' => 51.5074, 'longitude' => -0.1278, 'recorded_at' => now()->toIso8601String()],
            ['device_id' => $device->id, 'latitude' => 40.7128, 'longitude' => -74.0060, 'recorded_at' => now()->toIso8601String()],
        ];

        $response = $this->postJson('/api/device/gps', ['points' => $points]);

        $response->assertStatus(201)
            ->assertJson(['count' => 3]);

        $this->assertDatabaseCount('gps_logs', 3);
    }
}
