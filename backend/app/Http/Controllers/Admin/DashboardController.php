<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     * Superadmins see all organisations with device counts.
     * Org admins see their own organisation's devices.
     */
    public function index(): Response
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $organizations = Organization::withCount('devices')->get();

            return Inertia::render('Admin/Dashboard', [
                'role' => 'superadmin',
                'organizations' => $organizations,
            ]);
        }

        $devices = $user->organization
            ? $user->organization->devices()->with('rooms')->get()
            : collect();

        return Inertia::render('Admin/Dashboard', [
            'role' => 'org_admin',
            'devices' => $devices,
            'organization' => $user->organization,
        ]);
    }
}
