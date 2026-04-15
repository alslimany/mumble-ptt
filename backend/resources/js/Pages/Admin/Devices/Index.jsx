import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function DevicesIndex({ devices, rooms, organizations, selectedOrgId }) {
    const [editingDevice, setEditingDevice] = useState(null);

    const editForm = useForm({ name: '', is_active: true });
    const roomForm = useForm({ room_ids: [] });

    function openEdit(device) {
        setEditingDevice(device);
        editForm.setData({ name: device.name, is_active: device.is_active });
    }

    function submitEdit(e) {
        e.preventDefault();
        editForm.put(route('admin.devices.update', editingDevice.id), {
            onSuccess: () => setEditingDevice(null),
        });
    }

    function submitRooms(deviceId, selectedRoomIds) {
        roomForm.setData('room_ids', selectedRoomIds);
        roomForm.put(route('admin.devices.assign-rooms', deviceId));
    }

    function changeOrg(orgId) {
        router.get(route('admin.devices.index'), { organization_id: orgId });
    }

    return (
        <AdminLayout title="Device Management">
            <Head title="Devices" />

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

            <div className="bg-white shadow rounded-lg overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            {['Name', 'Identifier', 'Status', 'Rooms', 'Actions'].map((h) => (
                                <th key={h} className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    {h}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200">
                        {devices.map((device) => (
                            <tr key={device.id}>
                                <td className="px-4 py-3 text-sm text-gray-800">{device.name}</td>
                                <td className="px-4 py-3 text-sm font-mono text-gray-500">{device.unique_identifier}</td>
                                <td className="px-4 py-3 text-sm">
                                    <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${device.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'}`}>
                                        {device.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </td>
                                <td className="px-4 py-3 text-sm">
                                    <RoomSelect
                                        allRooms={rooms}
                                        assignedRoomIds={device.rooms.map((r) => r.id)}
                                        onSave={(ids) => submitRooms(device.id, ids)}
                                    />
                                </td>
                                <td className="px-4 py-3 text-sm">
                                    <button
                                        onClick={() => openEdit(device)}
                                        className="text-indigo-600 hover:underline text-sm"
                                    >
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Edit modal */}
            {editingDevice && (
                <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
                    <div className="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
                        <h3 className="text-lg font-semibold mb-4">Edit Device</h3>
                        <form onSubmit={submitEdit} className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Name</label>
                                <input
                                    type="text"
                                    value={editForm.data.name}
                                    onChange={(e) => editForm.setData('name', e.target.value)}
                                    className="mt-1 block w-full rounded border-gray-300 shadow-sm"
                                />
                                {editForm.errors.name && <p className="text-red-500 text-xs mt-1">{editForm.errors.name}</p>}
                            </div>
                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={editForm.data.is_active}
                                    onChange={(e) => editForm.setData('is_active', e.target.checked)}
                                    id="is_active"
                                />
                                <label htmlFor="is_active" className="text-sm text-gray-700">Active</label>
                            </div>
                            <div className="flex justify-end gap-2">
                                <button type="button" onClick={() => setEditingDevice(null)} className="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">
                                    Cancel
                                </button>
                                <button type="submit" disabled={editForm.processing} className="px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700 disabled:opacity-50">
                                    Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}

function RoomSelect({ allRooms, assignedRoomIds, onSave }) {
    const [selected, setSelected] = useState(assignedRoomIds);
    const [open, setOpen] = useState(false);

    function toggle(id) {
        setSelected((prev) => prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]);
    }

    return (
        <div className="relative">
            <button onClick={() => setOpen(!open)} className="text-xs text-indigo-600 hover:underline">
                {selected.length > 0 ? `${selected.length} room(s)` : 'Assign rooms'}
            </button>
            {open && (
                <div className="absolute z-10 mt-1 bg-white border rounded shadow-md p-2 min-w-max">
                    {allRooms.map((room) => (
                        <label key={room.id} className="flex items-center gap-2 text-sm py-0.5 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={selected.includes(room.id)}
                                onChange={() => toggle(room.id)}
                            />
                            {room.name}
                        </label>
                    ))}
                    <button
                        onClick={() => { onSave(selected); setOpen(false); }}
                        className="mt-2 w-full bg-indigo-600 text-white text-xs rounded py-1 hover:bg-indigo-700"
                    >
                        Save
                    </button>
                </div>
            )}
        </div>
    );
}
