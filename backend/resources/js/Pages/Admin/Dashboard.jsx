import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';

export default function Dashboard({ role, organizations = [], devices = [], organization = null }) {
    return (
        <AdminLayout title="Dashboard">
            <Head title="Dashboard" />

            {role === 'superadmin' ? (
                <div>
                    <h2 className="text-lg font-medium text-gray-700 mb-4">All Organisations</h2>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        {organizations.map((org) => (
                            <div
                                key={org.id}
                                className="bg-white rounded-lg shadow p-5 flex flex-col gap-1"
                            >
                                <p className="text-base font-semibold text-gray-800">{org.name}</p>
                                <p className="text-sm text-gray-500">
                                    {org.devices_count} device{org.devices_count !== 1 ? 's' : ''}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            ) : (
                <div>
                    <h2 className="text-lg font-medium text-gray-700 mb-4">
                        {organization?.name ?? 'Your Organisation'} — Devices
                    </h2>
                    <div className="bg-white shadow rounded-lg overflow-hidden">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    {['Name', 'Identifier', 'Status', 'Rooms'].map((h) => (
                                        <th
                                            key={h}
                                            className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                        >
                                            {h}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {devices.map((device) => (
                                    <tr key={device.id}>
                                        <td className="px-4 py-3 text-sm text-gray-800">{device.name}</td>
                                        <td className="px-4 py-3 text-sm text-gray-500 font-mono">{device.unique_identifier}</td>
                                        <td className="px-4 py-3 text-sm">
                                            <span
                                                className={`inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${
                                                    device.is_active
                                                        ? 'bg-green-100 text-green-700'
                                                        : 'bg-red-100 text-red-600'
                                                }`}
                                            >
                                                {device.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-500">
                                            {device.rooms?.map((r) => r.name).join(', ') || '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
