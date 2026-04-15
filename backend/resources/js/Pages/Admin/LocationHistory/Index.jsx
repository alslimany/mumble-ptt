import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { MapContainer, TileLayer, Polyline, CircleMarker, Popup } from 'react-leaflet';
import 'leaflet/dist/leaflet.css';
import AdminLayout from '@/Layouts/AdminLayout';

export default function LocationHistoryIndex({
    devices,
    organizations,
    selectedOrgId,
    selectedDeviceId,
    dateFrom,
    dateTo,
    points,
}) {
    const [deviceId, setDeviceId] = useState(selectedDeviceId ?? '');
    const [from, setFrom] = useState(dateFrom ?? '');
    const [to, setTo] = useState(dateTo ?? '');

    function search(e) {
        e.preventDefault();
        router.get(route('admin.location-history.index'), {
            organization_id: selectedOrgId,
            device_id: deviceId || undefined,
            date_from: from || undefined,
            date_to: to || undefined,
        });
    }

    function changeOrg(orgId) {
        router.get(route('admin.location-history.index'), { organization_id: orgId });
    }

    const positions = points.map((p) => [p.latitude, p.longitude]);
    const center = positions.length > 0 ? positions[0] : [0, 0];

    return (
        <AdminLayout title="Location History">
            <Head title="Location History" />

            {organizations.length > 1 && (
                <div className="mb-4">
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

            <form onSubmit={search} className="flex flex-wrap gap-3 mb-6 items-end">
                <div>
                    <label className="block text-xs text-gray-600 mb-1">Device</label>
                    <select
                        value={deviceId}
                        onChange={(e) => setDeviceId(e.target.value)}
                        className="rounded border-gray-300 text-sm"
                        required
                    >
                        <option value="">Select a device…</option>
                        {devices.map((d) => (
                            <option key={d.id} value={d.id}>{d.name}</option>
                        ))}
                    </select>
                </div>
                <div>
                    <label className="block text-xs text-gray-600 mb-1">From</label>
                    <input type="date" value={from} onChange={(e) => setFrom(e.target.value)} className="rounded border-gray-300 text-sm" />
                </div>
                <div>
                    <label className="block text-xs text-gray-600 mb-1">To</label>
                    <input type="date" value={to} onChange={(e) => setTo(e.target.value)} className="rounded border-gray-300 text-sm" />
                </div>
                <button type="submit" className="px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700">
                    Search
                </button>
            </form>

            {points.length > 0 ? (
                <>
                    <div className="rounded-lg overflow-hidden shadow h-96 mb-6">
                        <MapContainer center={center} zoom={13} style={{ height: '100%', width: '100%' }}>
                            <TileLayer
                                attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                                url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                            />
                            <Polyline positions={positions} color="indigo" weight={3} opacity={0.7} />
                            {points.map((p) => (
                                <CircleMarker key={p.id} center={[p.latitude, p.longitude]} radius={4} color="indigo">
                                    <Popup>{new Date(p.recorded_at).toLocaleString()}</Popup>
                                </CircleMarker>
                            ))}
                        </MapContainer>
                    </div>

                    <div className="bg-white shadow rounded-lg overflow-hidden">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    {['#', 'Latitude', 'Longitude', 'Recorded At'].map((h) => (
                                        <th key={h} className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{h}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {points.map((p, idx) => (
                                    <tr key={p.id}>
                                        <td className="px-4 py-2 text-sm text-gray-500">{idx + 1}</td>
                                        <td className="px-4 py-2 text-sm font-mono">{p.latitude}</td>
                                        <td className="px-4 py-2 text-sm font-mono">{p.longitude}</td>
                                        <td className="px-4 py-2 text-sm text-gray-500">{new Date(p.recorded_at).toLocaleString()}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </>
            ) : selectedDeviceId ? (
                <p className="text-sm text-gray-500">No GPS points found for the selected criteria.</p>
            ) : null}
        </AdminLayout>
    );
}
