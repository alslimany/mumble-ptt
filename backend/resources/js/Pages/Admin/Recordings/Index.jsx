import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function RecordingsIndex({ recordings, rooms, organizations, selectedOrgId, selectedRoomId }) {
    function changeOrg(orgId) {
        router.get(route('admin.recordings.index'), { organization_id: orgId });
    }

    function changeRoom(roomId) {
        router.get(route('admin.recordings.index'), {
            organization_id: selectedOrgId,
            room_id: roomId || undefined,
        });
    }

    function formatDuration(seconds) {
        if (!seconds) return '—';
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        return `${m}:${String(s).padStart(2, '0')}`;
    }

    const { data: recordingList, links } = recordings;

    return (
        <AdminLayout title="Voice Recordings">
            <Head title="Recordings" />

            <div className="flex flex-wrap gap-4 mb-4">
                {organizations.length > 1 && (
                    <div>
                        <label className="text-sm text-gray-600 mr-2">Organisation:</label>
                        <select
                            value={selectedOrgId}
                            onChange={(e) => changeOrg(e.target.value)}
                            className="rounded border-gray-300 text-sm"
                        >
                            {organizations.map((org) => (
                                <option key={org.id} value={org.id}>{org.name}</option>
                            ))}
                        </select>
                    </div>
                )}
                <div>
                    <label className="text-sm text-gray-600 mr-2">Room:</label>
                    <select
                        value={selectedRoomId ?? ''}
                        onChange={(e) => changeRoom(e.target.value)}
                        className="rounded border-gray-300 text-sm"
                    >
                        <option value="">All rooms</option>
                        {rooms.map((room) => (
                            <option key={room.id} value={room.id}>{room.name}</option>
                        ))}
                    </select>
                </div>
            </div>

            {recordingList.length === 0 ? (
                <p className="text-sm text-gray-500">No recordings found.</p>
            ) : (
                <div className="space-y-4">
                    {recordingList.map((rec) => (
                        <div key={rec.id} className="bg-white shadow rounded-lg p-4">
                            <div className="flex items-center justify-between mb-2">
                                <div>
                                    <span className="text-sm font-medium text-gray-800">
                                        {rec.room?.name ?? 'Unknown Room'}
                                    </span>
                                    <span className="ml-3 text-xs text-gray-500">
                                        {rec.started_at ? new Date(rec.started_at).toLocaleString() : ''}
                                        {rec.duration ? ` · ${formatDuration(rec.duration)}` : ''}
                                    </span>
                                </div>
                            </div>
                            <audio
                                controls
                                src={rec.url}
                                className="w-full mt-1"
                                preload="metadata"
                            >
                                Your browser does not support the audio element.
                            </audio>
                        </div>
                    ))}
                </div>
            )}

            {/* Pagination */}
            {links && links.length > 3 && (
                <div className="flex gap-1 mt-6 flex-wrap">
                    {links.map((link, i) => (
                        <button
                            key={i}
                            disabled={!link.url || link.active}
                            onClick={() => link.url && router.get(link.url)}
                            className={`px-3 py-1 rounded text-sm border ${
                                link.active
                                    ? 'bg-indigo-600 text-white border-indigo-600'
                                    : 'text-gray-600 border-gray-300 hover:bg-gray-50 disabled:opacity-40'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}
