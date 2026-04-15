import { useEffect, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { MapContainer, TileLayer, Marker, Popup } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import AdminLayout from '@/Layouts/AdminLayout';

// Fix default leaflet marker icons for bundlers
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

export default function LiveMapIndex({ devices: initialDevices, organizations, selectedOrgId }) {
    const [markers, setMarkers] = useState(() => {
        const map = {};
        initialDevices.forEach((d) => {
            if (d.latitude != null && d.longitude != null) {
                map[d.id] = { lat: d.latitude, lng: d.longitude, name: d.name, last_seen: d.last_seen };
            }
        });
        return map;
    });

    useEffect(() => {
        const channel = window.Echo.channel(`organization.${selectedOrgId}`);

        channel.listen('OrganizationDeviceLocationUpdatedEvent', ({ device_id, gps }) => {
            setMarkers((prev) => ({
                ...prev,
                [device_id]: {
                    ...prev[device_id],
                    lat: gps.latitude,
                    lng: gps.longitude,
                    last_seen: new Date().toISOString(),
                },
            }));
        });

        return () => {
            window.Echo.leaveChannel(`organization.${selectedOrgId}`);
        };
    }, [selectedOrgId]);

    function changeOrg(orgId) {
        router.get(route('admin.live-map.index'), { organization_id: orgId });
    }

    const markerList = Object.entries(markers);
    const defaultCenter = markerList.length > 0
        ? [markerList[0][1].lat, markerList[0][1].lng]
        : [0, 0];

    return (
        <AdminLayout title="Live Map">
            <Head title="Live Map" />

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

            <div className="rounded-lg overflow-hidden shadow h-[600px]">
                <MapContainer center={defaultCenter} zoom={markerList.length > 0 ? 13 : 2} style={{ height: '100%', width: '100%' }}>
                    <TileLayer
                        attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                    />
                    {markerList.map(([deviceId, data]) => (
                        <Marker key={deviceId} position={[data.lat, data.lng]}>
                            <Popup>
                                <strong>{data.name ?? `Device #${deviceId}`}</strong><br />
                                {data.lat.toFixed(5)}, {data.lng.toFixed(5)}<br />
                                {data.last_seen ? new Date(data.last_seen).toLocaleString() : ''}
                            </Popup>
                        </Marker>
                    ))}
                </MapContainer>
            </div>

            {markerList.length === 0 && (
                <p className="mt-4 text-sm text-gray-500 text-center">No devices with GPS data found.</p>
            )}
        </AdminLayout>
    );
}
