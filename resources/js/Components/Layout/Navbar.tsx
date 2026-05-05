import React from 'react';
import { Menu, Bell } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import UserDropdown from './UserDropdown';

type AuthUser = {
    name: string;
    email: string;
    roles?: { name: string }[];
};

type PageProps = {
    auth: {
        user?: AuthUser | null;
    };
};

type Props = {
    onToggleSidebar: () => void;
};

export default function Navbar({ onToggleSidebar }: Props) {
    const { auth } = usePage().props as PageProps;
    const user = auth?.user;
    const isSuperAdmin = user?.roles?.some((r) => r.name === 'Super Admin') ?? false;
    const isLibrarian = user?.roles?.some((r) => r.name === 'Librarian') ?? false;

    const panelTitle = isSuperAdmin ? 'Admin Dashboard' : isLibrarian ? 'Librarian Dashboard' : 'Dashboard';

    return (
        <header className="bg-white shadow-sm px-6 py-4 flex items-center justify-between">
            <div className="flex items-center gap-3">
                <button
                    onClick={onToggleSidebar}
                    className="p-2 rounded-lg hover:bg-slate-100 transition-colors"
                >
                    <Menu size={18} />
                </button>
                <h1 className="text-xl font-semibold">{panelTitle}</h1>
            </div>

            <div className="flex items-center gap-3 text-slate-600">
                <button className="p-2 rounded-lg hover:bg-slate-100">
                    <Bell size={18} />
                </button>

                <UserDropdown profileRoute={route('profile.edit')} />
            </div>
        </header>
    );
}