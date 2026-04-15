<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\GpsLog;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LocationHistoryController extends Controller
{
    /**
     * Show location history for a device over a date range.
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

        $devices = $org->devices()->select('id', 'name')->get();

        $points = collect();
        $selectedDeviceId = $request->query('device_id');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        if ($selectedDeviceId) {
            $device = Device::where('id', $selectedDeviceId)
                ->where('organization_id', $orgId)
                ->firstOrFail();

            $query = GpsLog::where('device_id', $device->id)
                ->orderBy('recorded_at');

            if ($dateFrom) {
                $query->whereDate('recorded_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('recorded_at', '<=', $dateTo);
            }

            $points = $query->get(['id', 'latitude', 'longitude', 'recorded_at']);
        }

        return Inertia::render('Admin/LocationHistory/Index', [
            'devices' => $devices,
            'organizations' => $organizations,
            'selectedOrgId' => $orgId,
            'selectedDeviceId' => $selectedDeviceId ? (int) $selectedDeviceId : null,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'points' => $points,
        ]);
    }
}
