<?php

namespace Tests\Feature\Admin;

use App\Models\Device;
use App\Models\Organization;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_org_admin_can_list_their_devices(): void
    {
        $org = Organization::factory()->create();
        $orgAdmin = User::factory()->create(['role' => 'org_admin', 'organization_id' => $org->id]);
        Device::factory()->count(3)->create(['organization_id' => $org->id]);

        $response = $this->actingAs($orgAdmin)
            ->withHeaders(['X-Inertia' => 'true'])
            ->get(route('admin.devices.index'));

        $response->assertOk();
        $response->assertJson(['component' => 'Admin/Devices/Index']);
        $this->assertCount(3, $response->json('props.devices'));
    }

    public function test_org_admin_can_assign_rooms_to_device(): void
    {
        $org = Organization::factory()->create();
        $orgAdmin = User::factory()->create(['role' => 'org_admin', 'organization_id' => $org->id]);
        $device = Device::factory()->create(['organization_id' => $org->id]);
        $rooms = Room::factory()->count(2)->create(['organization_id' => $org->id]);

        $response = $this->actingAs($orgAdmin)->put(
            route('admin.devices.assign-rooms', $device->id),
            ['room_ids' => $rooms->pluck('id')->toArray()]
        );

        $response->assertRedirect();
        $this->assertCount(2, $device->fresh()->rooms);
    }

    public function test_org_admin_cannot_manage_devices_from_another_organisation(): void
    {
        $ownOrg = Organization::factory()->create();
        $otherOrg = Organization::factory()->create();
        $orgAdmin = User::factory()->create(['role' => 'org_admin', 'organization_id' => $ownOrg->id]);
        $otherDevice = Device::factory()->create(['organization_id' => $otherOrg->id]);

        $response = $this->actingAs($orgAdmin)->put(
            route('admin.devices.assign-rooms', $otherDevice->id),
            ['room_ids' => []]
        );

        $response->assertForbidden();
    }
}
