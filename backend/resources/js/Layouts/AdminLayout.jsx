import { Link, usePage } from '@inertiajs/react';

export default function AdminLayout({ children, title = '' }) {
    const { auth } = usePage().props;
    const user = auth?.user;

    const navLinks = [
        { href: route('admin.dashboard'), label: 'Dashboard' },
        { href: route('admin.devices.index'), label: 'Devices' },
        { href: route('admin.rooms.index'), label: 'Rooms' },
        { href: route('admin.live-map.index'), label: 'Live Map' },
        { href: route('admin.location-history.index'), label: 'Location History' },
        { href: route('admin.recordings.index'), label: 'Recordings' },
    ];

    return (
        <div className="flex min-h-screen bg-gray-100">
            {/* Sidebar */}
            <aside className="w-64 bg-gray-900 text-gray-100 flex flex-col">
                <div className="px-6 py-4 text-xl font-bold border-b border-gray-700">
                    PTT Admin
                </div>

                <nav className="flex-1 px-4 py-4 space-y-1">
                    {navLinks.map(({ href, label }) => (
                        <Link
                            key={label}
                            href={href}
                            className="block px-3 py-2 rounded-md text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white transition-colors"
                        >
                            {label}
                        </Link>
                    ))}
                </nav>

                {user && (
                    <div className="px-4 py-4 border-t border-gray-700 text-sm">
                        <p className="text-gray-400 truncate">{user.email}</p>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="mt-2 text-gray-400 hover:text-white transition-colors"
                        >
                            Log out
                        </Link>
                    </div>
                )}
            </aside>

            {/* Main content */}
            <div className="flex-1 flex flex-col">
                {title && (
                    <header className="bg-white shadow px-6 py-4">
                        <h1 className="text-xl font-semibold text-gray-800">{title}</h1>
                    </header>
                )}
                <main className="flex-1 p-6">{children}</main>
            </div>
        </div>
    );
}
