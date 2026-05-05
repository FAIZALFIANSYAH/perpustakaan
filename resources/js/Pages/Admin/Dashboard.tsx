import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { BookOpen, Users, ClipboardList, AlertTriangle, Clock3, RotateCcw } from 'lucide-react';

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

type Summary = {
    total_books: number;
    total_members: number;
    total_borrowings: number;
    borrowed_active: number;
    returned_total: number;
    late_borrowings: number;
};

type BorrowingItem = {
    id: number;
    quantity: number;
    book?: {
        title: string;
    } | null;
};

type Borrowing = {
    id: number;
    code: string;
    borrowed_at: string;
    due_at: string;
    status: string;
    member?: {
        name: string;
        email?: string;
    } | null;
    processedBy?: {
        name: string;
    } | null;
    items: BorrowingItem[];
};

type Props = {
    summary: Summary;
    recentBorrowings: Borrowing[];
};

type StatCardProps = {
    title: string;
    value: number;
    icon: React.ElementType;
    tone: string;
};

function StatCard({ title, value, icon: Icon, tone }: StatCardProps) {
    return (
        <div className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-sm text-slate-500">{title}</p>
                    <h3 className="mt-2 text-3xl font-bold text-slate-900">{value}</h3>
                </div>
                <div className={`rounded-xl p-3 ${tone}`}>
                    <Icon size={22} />
                </div>
            </div>
        </div>
    );
}

export default function Dashboard({ summary, recentBorrowings }: Props) {
    const { auth } = usePage().props as PageProps;
    const user = auth?.user;

    return (
        <AdminLayout>
            <Head title="Admin Dashboard" />
            <div className="space-y-6">
                <div>
                    <h2 className="text-2xl font-bold text-slate-900">Welcome, {user?.name ?? 'Super Admin'}</h2>
                    <p className="text-slate-500">Sistem Informasi Perpustakaan - Panel Administrator</p>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <StatCard title="Total Books" value={summary.total_books} icon={BookOpen} tone="bg-slate-100 text-slate-700" />
                    <StatCard title="Total Members" value={summary.total_members} icon={Users} tone="bg-blue-100 text-blue-700" />
                    <StatCard title="Total Borrowings" value={summary.total_borrowings} icon={ClipboardList} tone="bg-emerald-100 text-emerald-700" />
                    <StatCard title="Active Borrowings" value={summary.borrowed_active} icon={Clock3} tone="bg-amber-100 text-amber-700" />
                    <StatCard title="Returned Total" value={summary.returned_total} icon={RotateCcw} tone="bg-violet-100 text-violet-700" />
                    <StatCard title="Late Borrowings" value={summary.late_borrowings} icon={AlertTriangle} tone="bg-rose-100 text-rose-700" />
                </div>

                <div className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div className="border-b border-slate-200 p-6">
                        <h3 className="text-lg font-semibold text-slate-900">Recent Borrowings</h3>
                        <p className="mt-1 text-sm text-slate-500">Daftar transaksi peminjaman terbaru.</p>
                    </div>

                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-slate-600">
                            <tr>
                                <th className="p-4 text-left font-semibold">Code</th>
                                <th className="p-4 text-left font-semibold">Member</th>
                                <th className="p-4 text-left font-semibold">Books</th>
                                <th className="p-4 text-left font-semibold">Borrowed</th>
                                <th className="p-4 text-left font-semibold">Due</th>
                                <th className="p-4 text-left font-semibold">Status</th>
                                <th className="p-4 text-left font-semibold">Processed By</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-200">
                            {recentBorrowings.length > 0 ? (
                                recentBorrowings.map((borrowing) => (
                                    <tr key={borrowing.id} className="align-top">
                                        <td className="p-4 font-medium text-slate-900">{borrowing.code}</td>
                                        <td className="p-4 text-slate-600">{borrowing.member?.name ?? '-'}</td>
                                        <td className="p-4 text-slate-600">
                                            <div className="space-y-1">
                                                {borrowing.items.map((item) => (
                                                    <div key={item.id} className="text-xs">
                                                        {item.book?.title ?? '-'} x {item.quantity}
                                                    </div>
                                                ))}
                                            </div>
                                        </td>
                                        <td className="p-4 text-slate-600">{borrowing.borrowed_at}</td>
                                        <td className="p-4 text-slate-600">{borrowing.due_at}</td>
                                        <td className="p-4">
                                            <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${
                                                borrowing.status === 'returned' || borrowing.status === 'complete' ? 'bg-emerald-100 text-emerald-700' : 
                                                borrowing.status === 'partial' ? 'bg-blue-100 text-blue-700' : 
                                                borrowing.status === 'lost' ? 'bg-red-100 text-red-700' :
                                                borrowing.status === 'awaiting_fine_payment' ? 'bg-amber-100 text-amber-700' :
                                                'bg-gray-100 text-gray-700'
                                            }`}>
                                                {borrowing.status}
                                            </span>
                                        </td>
                                        <td className="p-4 text-slate-600">{borrowing.processedBy?.name ?? '-'}</td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={7} className="p-8 text-center text-slate-500">
                                        Belum ada data peminjaman
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminLayout>
    );
}