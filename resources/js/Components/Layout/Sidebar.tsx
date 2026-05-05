import React from 'react';
import { Home, BookOpen, Users, FileBarChart2, ClipboardList, Tag, DollarSign, Settings } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';

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
    collapsed: boolean;
};

export default function Sidebar({ collapsed }: Props) {
    const { auth } = usePage().props as PageProps;
    const user = auth?.user;
    const isSuperAdmin = user?.roles?.some((r) => r.name === 'Super Admin') ?? false;
    const isLibrarian = user?.roles?.some((r) => r.name === 'Librarian') ?? false;

    const menus = [
        ...(isSuperAdmin ? [{ label: 'Dashboard', icon: Home, href: '/admin/dashboard' }] : []),
        ...(isSuperAdmin ? [{ label: 'Categories', icon: Tag, href: '/admin/categories' }] : []),
        ...(isSuperAdmin ? [{ label: 'Books', icon: BookOpen, href: '/admin/books' }] : []),
        ...(isSuperAdmin ? [{ label: 'Borrowings', icon: ClipboardList, href: '/admin/borrowings' }] : []),
        ...(isSuperAdmin ? [{ label: 'Fines', icon: DollarSign, href: '/admin/fines' }] : []),
        ...(isSuperAdmin ? [{ label: 'Fine Config', icon: Settings, href: '/admin/fine-config' }] : []),
        ...(isSuperAdmin ? [{ label: 'Users', icon: Users, href: '/admin/users' }] : []),
        ...(isSuperAdmin ? [{ label: 'Reports', icon: FileBarChart2, href: '/admin/reports' }] : []),
        ...(isLibrarian ? [{ label: 'Dashboard', icon: Home, href: '/librarian/dashboard' }] : []),
        ...(isLibrarian ? [{ label: 'Books', icon: BookOpen, href: '/librarian/books' }] : []),
        ...(isLibrarian ? [{ label: 'Borrowings', icon: ClipboardList, href: '/librarian/borrowings' }] : []),
        ...(isLibrarian ? [{ label: 'Fines', icon: DollarSign, href: '/librarian/fines' }] : []),
        ...(isLibrarian ? [{ label: 'Categories', icon: Tag, href: '/librarian/categories' }] : []),
        ...(isLibrarian ? [{ label: 'Reports', icon: FileBarChart2, href: '/librarian/reports' }] : []),
    ];

    return (
        <aside className={`${collapsed ? 'w-20' : 'w-64'} bg-slate-900 text-white min-h-screen transition-all duration-300 flex flex-col`}>
            <div className="p-4 border-b border-slate-800 font-bold text-lg">
                {collapsed ? 'LIB' : 'Perpustakaan'}
            </div>

            <nav className="flex-1 p-3 space-y-5">
                {menus.map((item) => {
                    const Icon = item.icon;
                    return (
                        <Link
                            key={item.label}
                            href={item.href}
                            className="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-slate-800 transition-colors"
                        >
                            <Icon size={18} />
                            {!collapsed && <span>{item.label}</span>}
                        </Link>
                    );
                })}
            </nav>

            <div className="p-3 border-t border-slate-800">
                <div className={`flex items-center gap-3 rounded-xl bg-slate-800/50 px-3 py-2.5 ${collapsed ? 'justify-center' : ''}`}>
                    <div className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold ${isSuperAdmin ? 'bg-emerald-500 text-white' : isLibrarian ? 'bg-blue-500 text-white' : 'bg-slate-600 text-white'}`}>
                        {user?.name?.charAt(0).toUpperCase() ?? 'U'}
                    </div>
                    {!collapsed && (
                        <div className="min-w-0">
                            <div className="text-sm font-medium truncate">{user?.name ?? 'User'}</div>
                            <div className={`text-[10px] font-semibold uppercase tracking-wider ${isSuperAdmin ? 'text-emerald-400' : isLibrarian ? 'text-blue-400' : 'text-slate-400'}`}>
                                {isSuperAdmin ? 'Super Admin' : isLibrarian ? 'Librarian' : 'Member'}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </aside>
    );
}
