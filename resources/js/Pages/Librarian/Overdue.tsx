import React, { useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import LibrarianLayout from '@/Layouts/LibrarianLayout';
import { AlertTriangle } from 'lucide-react';

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

type BorrowingItem = {
    id: number;
    quantity: number;
    returned_quantity: number;
    book?: { title: string } | null;
};

type Borrowing = {
    id: number;
    code: string;
    borrowed_at: string;
    due_at: string;
    status: string;
    member?: { name: string; email: string } | null;
    items: BorrowingItem[];
};

type Props = {
    overdues: Borrowing[];
};

function getDaysLate(dueAt: string): number {
    const due = new Date(dueAt);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    due.setHours(0, 0, 0, 0);
    return Math.max(0, Math.ceil((today.getTime() - due.getTime()) / (1000 * 60 * 60 * 24)));
}

export default function Overdue({ overdues }: Props) {
    return (
        <LibrarianLayout>
            <Head title="Overdue Borrowings" />

            <div className="space-y-6">
                <div>
                    <h2 className="text-2xl font-bold text-slate-900">Overdue Borrowings</h2>
                    <p className="text-slate-500">Daftar peminjaman yang sudah melewati tanggal jatuh tempo.</p>
                </div>

                <div className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-slate-600">
                            <tr>
                                <th className="p-4 text-left font-semibold">Borrow Code</th>
                                <th className="p-4 text-left font-semibold">Member</th>
                                <th className="p-4 text-left font-semibold">Books</th>
                                <th className="p-4 text-left font-semibold">Due Date</th>
                                <th className="p-4 text-left font-semibold">Days Late</th>
                                <th className="p-4 text-left font-semibold">Status</th>
                                <th className="p-4 text-right font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-200">
                            {overdues.length > 0 ? (
                                overdues.map((borrowing) => {
                                    const daysLate = getDaysLate(borrowing.due_at);
                                    return (
                                        <tr key={borrowing.id} className="align-top">
                                            <td className="p-4 font-medium text-slate-900">{borrowing.code}</td>
                                            <td className="p-4">
                                                <div className="text-slate-900">{borrowing.member?.name ?? '-'}</div>
                                                <div className="text-xs text-slate-500">{borrowing.member?.email ?? ''}</div>
                                            </td>
                                            <td className="p-4 text-slate-600">
                                                <div className="space-y-1">
                                                    {borrowing.items.map((item) => (
                                                        <div key={item.id} className="text-xs">
                                                            {item.book?.title ?? '-'} x {item.quantity}
                                                        </div>
                                                    ))}
                                                </div>
                                            </td>
                                            <td className="p-4 text-slate-600">{borrowing.due_at}</td>
                                            <td className="p-4">
                                                <span className="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                                    <AlertTriangle size={12} />
                                                    {daysLate} day{daysLate !== 1 ? 's' : ''}
                                                </span>
                                            </td>
                                            <td className="p-4">
                                                <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${getStatusInfo(borrowing.status).bgColor} ${getStatusInfo(borrowing.status).color}`}>
                            {getStatusInfo(borrowing.status).label}
                        </span>
                                            </td>
                                            <td className="p-4">
                                                <div className="flex justify-end">
                                                    <Link
                                                        href={route('librarian.borrowings.show', borrowing.id)}
                                                        className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                                    >
                                                        Detail
                                                    </Link>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })
                            ) : (
                                <tr>
                                    <td colSpan={7} className="p-8 text-center text-slate-500">
                                        No overdue borrowings. All clear!
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </LibrarianLayout>
    );
}
