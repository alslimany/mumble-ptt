<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LiveMapController extends Controller
{
    /**
     * Show the live map with the latest GPS position of each device.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $organizations = Organization::select('id', 'name')->get();
            $orgId = (int) $request->query('organization_id', $organizations->first()?->id);
            $org = Organization::findOrFail($orgId);
        } else {
            $org = $user->organization ?? abort(403, 'No organisation assigned.');
            $organizations = collect([$org]);
            $orgId = $org->id;
        }

        $devices = $org->devices()
            ->with(['gpsLogs' => function ($query) {
                $query->latest('recorded_at')->limit(1);
            }])
            ->get()
            ->map(function ($device) {
                $latest = $device->gpsLogs->first();
                return [
                    'id' => $device->id,
                    'name' => $device->name,
                    'latitude' => $latest?->latitude,
                    'longitude' => $latest?->longitude,
                    'last_seen' => $latest?->recorded_at?->toIso8601String(),
                ];
            });

        return Inertia::render('Admin/LiveMap/Index', [
            'devices' => $devices,
            'organizations' => $organizations,
            'selectedOrgId' => $orgId,
        ]);
    }
}
