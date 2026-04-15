import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function RoomsIndex({ rooms, organizations, selectedOrgId }) {
    const [modalRoom, setModalRoom] = useState(null); // null=closed, {}=create, {id,...}=edit

    const form = useForm({ name: '', mumble_channel_id: '', organization_id: selectedOrgId });

    function openCreate() {
        form.reset();
        form.setData('organization_id', selectedOrgId);
        setModalRoom({});
    }

    function openEdit(room) {
        form.setData({
            name: room.name,
            mumble_channel_id: room.mumble_channel_id ?? '',
            organization_id: room.organization_id,
        });
        setModalRoom(room);
    }

    function submitForm(e) {
        e.preventDefault();
        if (modalRoom?.id) {
            form.put(route('admin.rooms.update', modalRoom.id), { onSuccess: () => setModalRoom(null) });
        } else {
            form.post(route('admin.rooms.store'), { onSuccess: () => setModalRoom(null) });
        }
    }

    function deleteRoom(room) {
        if (confirm(`Delete room "${room.name}"?`)) {
            router.delete(route('admin.rooms.destroy', room.id));
        }
    }

    function changeOrg(orgId) {
        router.get(route('admin.rooms.index'), { organization_id: orgId });
    }

    return (
        <AdminLayout title="Room Management">
            <Head title="Rooms" />

            <div className="flex items-center justify-between mb-4">
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
                <button onClick={openCreate} className="px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700">
                    + New Room
                </button>
            </div>

            <div className="bg-white shadow rounded-lg overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            {['Name', 'Mumble Channel ID', 'Devices', 'Actions'].map((h) => (
                                <th key={h} className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200">
                        {rooms.map((room) => (
                            <tr key={room.id}>
                                <td className="px-4 py-3 text-sm text-gray-800">{room.name}</td>
                                <td className="px-4 py-3 text-sm text-gray-500">{room.mumble_channel_id ?? '—'}</td>
                                <td className="px-4 py-3 text-sm text-gray-500">{room.devices_count}</td>
                                <td className="px-4 py-3 text-sm space-x-3">
                                    <button onClick={() => openEdit(room)} className="text-indigo-600 hover:underline">Edit</button>
                                    <button onClick={() => deleteRoom(room)} className="text-red-500 hover:underline">Delete</button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Modal */}
            {modalRoom !== null && (
                <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
                    <div className="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
                        <h3 className="text-lg font-semibold mb-4">{modalRoom.id ? 'Edit Room' : 'New Room'}</h3>
                        <form onSubmit={submitForm} className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Name</label>
                                <input
                                    type="text"
                                    value={form.data.name}
                                    onChange={(e) => form.setData('name', e.target.value)}
                                    className="mt-1 block w-full rounded border-gray-300 shadow-sm"
                                    required
                                />
                                {form.errors.name && <p className="text-red-500 text-xs mt-1">{form.errors.name}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Mumble Channel ID</label>
                                <input
                                    type="number"
                                    value={form.data.mumble_channel_id}
                                    onChange={(e) => form.setData('mumble_channel_id', e.target.value)}
                                    className="mt-1 block w-full rounded border-gray-300 shadow-sm"
                                    placeholder="Optional"
                                />
                                {form.errors.mumble_channel_id && <p className="text-red-500 text-xs mt-1">{form.errors.mumble_channel_id}</p>}
                            </div>
                            <div className="flex justify-end gap-2">
                                <button type="button" onClick={() => setModalRoom(null)} className="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">
                                    Cancel
                                </button>
                                <button type="submit" disabled={form.processing} className="px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700 disabled:opacity-50">
                                    {modalRoom.id ? 'Update' : 'Create'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
