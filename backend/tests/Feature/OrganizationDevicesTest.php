<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationDevicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_devices_for_organization(): void
    {
        $org = Organization::factory()->create();
        Device::factory(2)->create(['organization_id' => $org->id]);

        $response = $this->getJson("/api/organizations/{$org->id}/devices");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json());
    }

    public function test_get_devices_returns_404_for_unknown_org(): void
    {
        $response = $this->getJson('/api/organizations/9999/devices');

        $response->assertStatus(404);
    }
}
