<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Organization;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeviceController extends Controller
{
    /**
     * Resolve the current user's organization or throw 403.
     */
    private function resolveOrganization(Request $request): Organization
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $orgId = $request->input('organization_id') ?? $request->route('organization_id');
            return Organization::findOrFail($orgId);
        }

        return $user->organization ?? abort(403, 'No organisation assigned.');
    }

    /**
     * List devices for the organisation.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $organizations = Organization::select('id', 'name')->get();
            $orgId = $request->query('organization_id', $organizations->first()?->id);
            $org = Organization::findOrFail($orgId);
        } else {
            $org = $user->organization ?? abort(403);
            $organizations = collect([$org]);
        }

        $devices = $org->devices()->with('rooms:id,name')->get();
        $rooms = $org->rooms()->select('id', 'name')->get();

        return Inertia::render('Admin/Devices/Index', [
            'devices' => $devices,
            'rooms' => $rooms,
            'organizations' => $organizations,
            'selectedOrgId' => $org->id,
        ]);
    }

    /**
     * Assign rooms to a device (multi-select).
     */
    public function assignRooms(Request $request, Device $device): RedirectResponse
    {
        $request->validate([
            'room_ids' => ['array'],
            'room_ids.*' => ['integer', 'exists:rooms,id'],
        ]);

        $org = $this->resolveOrganization($request);

        abort_unless($device->organization_id === $org->id, 403);

        $device->rooms()->sync($request->input('room_ids', []));

        return back()->with('success', 'Room assignments updated.');
    }

    /**
     * Update a device's basic attributes.
     */
    public function update(Request $request, Device $device): RedirectResponse
    {
        $org = $this->resolveOrganization($request);
        abort_unless($device->organization_id === $org->id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $device->update($validated);

        return back()->with('success', 'Device updated.');
    }
}
