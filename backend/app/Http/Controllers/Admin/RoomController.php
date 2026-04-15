<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    /**
     * Resolve the user's organisation.
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
     * List rooms for the organisation.
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

        $rooms = $org->rooms()->withCount('devices')->get();

        return Inertia::render('Admin/Rooms/Index', [
            'rooms' => $rooms,
            'organizations' => $organizations,
            'selectedOrgId' => $org->id,
        ]);
    }

    /**
     * Store a new room.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mumble_channel_id' => ['nullable', 'integer'],
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
        ]);

        $org = $this->resolveOrganization($request);
        abort_unless((int) $validated['organization_id'] === $org->id, 403);

        Room::create($validated);

        return back()->with('success', 'Room created.');
    }

    /**
     * Update an existing room.
     */
    public function update(Request $request, Room $room): RedirectResponse
    {
        $org = $this->resolveOrganization($request);
        abort_unless($room->organization_id === $org->id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mumble_channel_id' => ['nullable', 'integer'],
        ]);

        $room->update($validated);

        return back()->with('success', 'Room updated.');
    }

    /**
     * Delete a room.
     */
    public function destroy(Request $request, Room $room): RedirectResponse
    {
        $org = $this->resolveOrganization($request);
        abort_unless($room->organization_id === $org->id, 403);

        $room->delete();

        return back()->with('success', 'Room deleted.');
    }
}
