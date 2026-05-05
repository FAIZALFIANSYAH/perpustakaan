import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { BookOpen, ClipboardList, Home, Search, AlertTriangle, FileBarChart2, Tag } from 'lucide-react';
import UserDropdown from '@/Components/Layout/UserDropdown';

type AuthUser = {
    name: string;
    email: string;
    roles?: { name: string }[];
};

type PageProps = {
    auth: {
        user?: AuthUser | null;
    };
    flash?: {
        success?: string | null;
        error?: string | null;
    };
};

type Props = {
    children: React.ReactNode;
};

export default function LibrarianLayout({ children }: Props) {
    const { auth, flash } = usePage().props as PageProps;
    const user = auth?.user;

    const menus = [
        { label: 'Dashboard', href: route('librarian.dashboard'), icon: Home },
        { label: 'Categories', href: route('librarian.categories.index'), icon: Tag },
        { label: 'Books', href: route('librarian.books.index'), icon: BookOpen },
        { label: 'Borrowings', href: route('librarian.borrowings.index'), icon: ClipboardList },
        { label: 'Reports', href: route('librarian.reports.index'), icon: FileBarChart2 },
        { label: 'Overdue', href: route('librarian.overdue'), icon: AlertTriangle },
        { label: 'Members', href: route('librarian.members.index'), icon: Search },
    ];

    return (
        <div className="min-h-screen bg-slate-100 flex">
            <aside className="w-64 bg-slate-900 text-white min-h-screen flex flex-col">
                <div className="p-4 border-b border-slate-800">
                    <div className="font-bold text-lg">Perpustakaan</div>
                    <div className="text-xs text-slate-400">Librarian Panel</div>
                </div>

                <nav className="flex-1 p-3 space-y-1">
                    {menus.map((item) => {
                        const Icon = item.icon;
                        return (
                            <Link
                                key={item.label}
                                href={item.href}
                                className="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-800 transition-colors text-sm font-medium"
                            >
                                <Icon size={18} />
                                <span>{item.label}</span>
                            </Link>
                        );
                    })}
                </nav>

            </aside>

            <div className="flex-1 flex flex-col">
                <header className="bg-white shadow-sm px-6 py-4 flex items-center justify-between">
                    <h1 className="text-xl font-semibold text-slate-900">Librarian Dashboard</h1>
                    <UserDropdown profileRoute={route('profile.edit')} />
                </header>

                <main className="p-6 space-y-4">
                    {flash?.success && (
                        <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            {flash.success}
                        </div>
                    )}
                    {flash?.error && (
                        <div className="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            {flash.error}
                        </div>
                    )}
                    {children}
                </main>
            </div>
        </div>
    );
}
