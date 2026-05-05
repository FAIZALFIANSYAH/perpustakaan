import React, { FormEventHandler } from 'react';
import { router, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { BookOpen, Users, ClipboardList, RotateCcw, AlertTriangle, Clock3 } from 'lucide-react';

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
    filters: {
        date_from?: string | null;
        date_to?: string | null;
    };
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

export default function Index({ summary, recentBorrowings, filters }: Props) {
    const { data, setData } = useForm({
        date_from: filters.date_from ?? '',
        date_to: filters.date_to ?? '',
    });

    const submitFilter: FormEventHandler = (event) => {
        event.preventDefault();
        router.get(route('admin.reports.index'), data, { preserveState: true, replace: true });
    };

    const resetFilter = () => {
        setData('date_from', '');
        setData('date_to', '');
        router.get(route('admin.reports.index'), {}, { preserveState: true, replace: true });
    };

    return (
        <AdminLayout>
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">Reports</h1>
                    <p className="text-sm text-slate-500">Ringkasan statistik perpustakaan untuk Super Admin.</p>
                </div>

                <form onSubmit={submitFilter} className="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-end">
                        <div className="w-full">
                            <label className="mb-2 block text-sm font-medium text-slate-700">Date From</label>
                            <input
                                type="date"
                                value={data.date_from}
                                onChange={(event) => setData('date_from', event.target.value)}
                                className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            />
                        </div>
                        <div className="w-full">
                            <label className="mb-2 block text-sm font-medium text-slate-700">Date To</label>
                            <input
                                type="date"
                                value={data.date_to}
                                onChange={(event) => setData('date_to', event.target.value)}
                                className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            />
                        </div>
                        <div className="flex gap-3">
                            <button
                                type="submit"
                                className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
                            >
                                Apply
                            </button>
                            <button
                                type="button"
                                onClick={resetFilter}
                                className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                Reset
                            </button>
                        </div>
                    </div>
                </form>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <StatCard title="Total Books" value={summary.total_books} icon={BookOpen} tone="bg-slate-100 text-slate-700" />
                    <StatCard title="Total Members" value={summary.total_members} icon={Users} tone="bg-blue-100 text-blue-700" />
                    <StatCard title="Total Borrowings" value={summary.total_borrowings} icon={ClipboardList} tone="bg-emerald-100 text-emerald-700" />
                    <StatCard title="Borrowed Active" value={summary.borrowed_active} icon={Clock3} tone="bg-amber-100 text-amber-700" />
                    <StatCard title="Returned Total" value={summary.returned_total} icon={RotateCcw} tone="bg-violet-100 text-violet-700" />
                    <StatCard title="Late Borrowings" value={summary.late_borrowings} icon={AlertTriangle} tone="bg-rose-100 text-rose-700" />
                </div>

                <div className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div className="border-b border-slate-200 p-6">
                        <h2 className="text-lg font-semibold text-slate-900">Recent Borrowings</h2>
                        <p className="mt-1 text-sm text-slate-500">Daftar transaksi terbaru berdasarkan filter yang dipilih.</p>
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
                                        Belum ada data laporan untuk filter ini
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
