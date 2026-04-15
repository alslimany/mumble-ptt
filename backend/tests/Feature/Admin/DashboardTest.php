<?php

namespace Tests\Feature\Admin;

use App\Models\Device;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_sees_all_organisations_on_dashboard(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin']);
        Organization::factory()->count(3)->create();

        $response = $this->actingAs($superadmin)
            ->withHeaders(['X-Inertia' => 'true'])
            ->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertJson([
            'component' => 'Admin/Dashboard',
            'props' => ['role' => 'superadmin'],
        ]);
        $this->assertCount(3, $response->json('props.organizations'));
    }

    public function test_org_admin_sees_own_devices_on_dashboard(): void
    {
        $org = Organization::factory()->create();
        $orgAdmin = User::factory()->create(['role' => 'org_admin', 'organization_id' => $org->id]);
        Device::factory()->count(2)->create(['organization_id' => $org->id]);

        $response = $this->actingAs($orgAdmin)
            ->withHeaders(['X-Inertia' => 'true'])
            ->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertJson([
            'component' => 'Admin/Dashboard',
            'props' => ['role' => 'org_admin'],
        ]);
        $this->assertCount(2, $response->json('props.devices'));
    }

    public function test_unauthenticated_user_is_redirected_from_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }
}
