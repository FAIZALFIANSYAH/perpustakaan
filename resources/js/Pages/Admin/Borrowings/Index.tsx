import React from 'react';

// Status mapping for display
const getStatusInfo = (status: string) => {
    const statusMap: Record<string, { label: string; color: string; bgColor: string }> = {
        borrowed: {
            label: "Dipinjam",
            color: "text-blue-700",
            bgColor: "bg-blue-100"
        },
        overdue: {
            label: "Terlambat",
            color: "text-red-700",
            bgColor: "bg-red-100"
        },
        returned: {
            label: "Dikembalikan",
            color: "text-green-700",
            bgColor: "bg-green-100"
        },
        late_payment: {
            label: "Pembayaran Terlambat",
            color: "text-orange-700",
            bgColor: "bg-orange-100"
        },
        complete: {
            label: "Selesai",
            color: "text-emerald-700",
            bgColor: "bg-emerald-100"
        },
        lost: {
            label: "Hilang",
            color: "text-purple-700",
            bgColor: "bg-purple-100"
        },
        partial: {
            label: "Dikembalikan Sebagian",
            color: "text-yellow-700",
            bgColor: "bg-yellow-100"
        }
    };
    
    return statusMap[status] || {
        label: status,
        color: "text-gray-700",
        bgColor: "bg-gray-100"
    };
};

import { Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import SearchBar from '@/Components/SearchBar';

type BorrowingItem = {
    id: number;
    quantity: number;
    returned_quantity: number;
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
    } | null;
    processed_by?: {
        name: string;
    } | null;
    processedBy?: {
        name: string;
    } | null;
    items: BorrowingItem[];
};

type Filters = {
    search: string;
};

type Props = {
    borrowings: Borrowing[];
    filters: Filters;
};

export default function Index({ borrowings, filters }: Props) {
    return (
        <AdminLayout>
            <div className="space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">Borrowing Management</h1>
                        <p className="text-sm text-slate-500">Kelola transaksi peminjaman buku perpustakaan.</p>
                    </div>

                    <div className="flex items-center gap-3">
                        <SearchBar
                            routeName="admin.borrowings.index"
                            searchValue={filters.search}
                            placeholder="Search by code, member, book..."
                        />
                        <Link
                            href={route('admin.borrowings.create')}
                            className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
                        >
                            Add Borrowing
                        </Link>
                    </div>
                </div>

                <div className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
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
                                <th className="p-4 text-right font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-200">
                            {borrowings.length > 0 ? (
                                borrowings.map((borrowing) => (
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
                                        <td className="p-4 text-slate-600">{borrowing.processedBy?.name ?? borrowing.processed_by?.name ?? '-'}</td>
                                        <td className="p-4">
                                            <div className="flex justify-end">
                                                <Link
                                                    href={route('admin.borrowings.show', borrowing.id)}
                                                    className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                                >
                                                    Detail
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={8} className="p-8 text-center text-slate-500">
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
