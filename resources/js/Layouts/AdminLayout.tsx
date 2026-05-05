import React from 'react';
import Sidebar from '@/Components/Layout/Sidebar';
import Navbar from '@/Components/Layout/Navbar';
import { usePage } from '@inertiajs/react';

type PageProps = {
    flash?: {
        success?: string | null;
        error?: string | null;
    };
};

type Props = {
    children: React.ReactNode;
};

export default function AdminLayout({ children }: Props) {
    const [collapsed, setCollapsed] = React.useState(false);
    const { flash } = usePage().props as PageProps;

    return (
        <div className="min-h-screen bg-slate-100 flex">
            <Sidebar collapsed={collapsed} />

            <div className="flex-1 flex flex-col">
                <Navbar onToggleSidebar={() => setCollapsed(!collapsed)} />

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