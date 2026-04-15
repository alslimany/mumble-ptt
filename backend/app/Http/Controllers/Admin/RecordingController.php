<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Recording;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecordingController extends Controller
{
    /**
     * List recordings grouped by room, with pagination.
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

        $selectedRoomId = $request->query('room_id');
        $rooms = $org->rooms()->select('id', 'name')->get();

        $query = Recording::where('organization_id', $orgId)
            ->with('room:id,name')
            ->latest('started_at');

        if ($selectedRoomId) {
            $query->where('room_id', $selectedRoomId);
        }

        $recordings = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Recordings/Index', [
            'recordings' => $recordings,
            'rooms' => $rooms,
            'organizations' => $organizations,
            'selectedOrgId' => $orgId,
            'selectedRoomId' => $selectedRoomId ? (int) $selectedRoomId : null,
        ]);
    }
}
