import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { BookOpen, Clock3, LayoutDashboard, Bookmark, DollarSign } from 'lucide-react';
import UserDropdown from '@/Components/Layout/UserDropdown';

type AuthUser = {
    name: string;
    email: string;
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

export default function MemberLayout({ children }: Props) {
    const { auth, flash } = usePage().props as PageProps;
    const user = auth?.user;
    const menus = [
        { label: 'Dashboard', href: route('member.dashboard'), icon: LayoutDashboard },
        { label: 'Borrowing History', href: route('member.borrowings.history'), icon: Clock3 },
        { label: 'Catalog', href: route('member.catalog.index'), icon: BookOpen },
        { label: 'Reservations', href: route('member.reservations.index'), icon: Bookmark },
        { label: 'My Fines', href: route('member.fines.index'), icon: DollarSign },
    ];

    return (
        <div className="min-h-screen bg-slate-100 flex">
            <aside className="w-64 bg-slate-900 text-white min-h-screen flex flex-col">
                <div className="p-4 border-b border-slate-800">
                    <div className="font-bold text-lg">Perpustakaan</div>
                    <div className="text-xs text-slate-400">Member Area</div>
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
                    <h1 className="text-xl font-semibold text-slate-900">Member Dashboard</h1>
                    <UserDropdown profileRoute={route('member.profile.edit')} />
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
