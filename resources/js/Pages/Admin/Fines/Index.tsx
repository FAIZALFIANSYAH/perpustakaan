import React, { useState, FormEventHandler } from 'react';
import { Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

type FinePayment = {
    id: number;
    amount: string;
    payment_method: string;
    notes: string | null;
    created_at: string;
};

type Fine = {
    id: number;
    type: string;
    amount: string;
    paid_amount: string;
    status: string;
    due_date: string;
    paid_at: string | null;
    reason: string | null;
    notes: string | null;
    created_at: string;
    member: {
        name: string;
        email: string;
    };
    borrowing_item: {
        book: {
            title: string;
            isbn: string | null;
        };
    };
    payments: FinePayment[];
};

type Statistics = {
    total_fines: number;
    total_unpaid: number;
    total_paid: number;
    total_amount: string;
    total_paid_amount: string;
    total_unpaid_amount: string;
    late_return_fines: number;
    lost_book_fines: number;
};

type Props = {
    fines: Fine[];
    statistics: Statistics;
    filters: {
        search: string;
        status: string;
    };
};

function StatCard({ label, value, color }: { label: string; value: string | number; color: string }) {
    return (
        <div className={`rounded-xl p-4 ${color}`}>
            <div className="text-xs font-medium uppercase tracking-wide opacity-75">{label}</div>
            <div className="mt-2 text-2xl font-bold">{value}</div>
        </div>
    );
}

function PaymentModal({ fine, onClose }: { fine: Fine; onClose: () => void }) {
    const remaining = parseFloat(fine.amount) - parseFloat(fine.paid_amount);
    const { data, setData, post, processing, errors, reset } = useForm({
        amount: remaining.toString(),
        payment_method: 'cash',
        notes: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const routeName = window.location.pathname.startsWith('/librarian')
            ? 'librarian.fines.payment'
            : 'admin.fines.payment';
        post(route(routeName, fine.id), {
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 className="text-lg font-semibold text-slate-900 mb-4">Process Fine Payment</h3>
                
                <div className="space-y-2 mb-4 text-sm">
                    <div className="flex justify-between">
                        <span className="text-slate-600">Member:</span>
                        <span className="font-medium">{fine.member.name}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-slate-600">Book:</span>
                        <span className="font-medium">{fine.borrowing_item.book.title}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-slate-600">Total Fine:</span>
                        <span className="font-medium">Rp {new Intl.NumberFormat('id-ID').format(parseFloat(fine.amount))}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-slate-600">Remaining:</span>
                        <span className="font-semibold text-red-600">Rp {new Intl.NumberFormat('id-ID').format(remaining)}</span>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-2">Payment Amount (Rp)</label>
                        <input
                            type="number"
                            min="0"
                            max={remaining}
                            step="100"
                            value={data.amount}
                            onChange={(e) => setData('amount', e.target.value)}
                            className="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        />
                        {errors.amount && <p className="mt-1 text-sm text-red-600">{errors.amount}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-2">Payment Method</label>
                        <select
                            value={data.payment_method}
                            onChange={(e) => setData('payment_method', e.target.value)}
                            className="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >
                            <option value="cash">Cash</option>
                            <option value="transfer">Transfer</option>
                        </select>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-2">Notes (Optional)</label>
                        <textarea
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            rows={2}
                            className="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        />
                    </div>

                    <div className="flex gap-3 pt-2">
                        <button
                            type="button"
                            onClick={onClose}
                            className="flex-1 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="flex-1 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:opacity-60"
                        >
                            {processing ? 'Processing...' : 'Process Payment'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

export default function FinesIndex({ fines, statistics, filters }: Props) {
    const [selectedFine, setSelectedFine] = useState<Fine | null>(null);

    const formatCurrency = (amount: string | number) => {
        return new Intl.NumberFormat('id-ID').format(parseFloat(amount.toString()));
    };

    const getStatusBadge = (status: string) => {
        const styles = {
            unpaid: 'bg-red-100 text-red-700',
            partial: 'bg-yellow-100 text-yellow-700',
            paid: 'bg-green-100 text-green-700',
            lost: 'bg-red-100 text-red-700',
            complete: 'bg-green-100 text-green-700',
            awaiting_fine_payment: 'bg-amber-100 text-amber-700',
        };
        return styles[status] || 'bg-gray-100 text-gray-700';
    };

    const getTypeLabel = (type: string) => {
        const labels = {
            late_return: 'Late Return',
            lost_book: 'Lost Book',
            damage: 'Damage',
        };
        return labels[type] || type;
    };

    return (
        <AdminLayout>
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">Fines Management</h1>
                        <p className="text-sm text-slate-500">View and manage all fines and payments.</p>
                    </div>
                </div>

                {/* Statistics */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label="Total Fines"
                        value={statistics.total_fines}
                        color="bg-blue-500 text-white"
                    />
                    <StatCard
                        label="Unpaid Fines"
                        value={statistics.total_unpaid}
                        color="bg-red-500 text-white"
                    />
                    <StatCard
                        label="Paid Fines"
                        value={statistics.total_paid}
                        color="bg-green-500 text-white"
                    />
                    <StatCard
                        label="Total Unpaid Amount"
                        value={`Rp ${formatCurrency(statistics.total_unpaid_amount)}`}
                        color="bg-orange-500 text-white"
                    />
                </div>

                {/* Filters */}
                <div className="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <form
                        method="GET"
                        action={window.location.pathname}
                        className="flex flex-wrap gap-4"
                    >
                        <input
                            type="text"
                            name="search"
                            defaultValue={filters.search}
                            placeholder="Search by member name or email..."
                            className="flex-1 min-w-[200px] rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        />
                        <select
                            name="status"
                            defaultValue={filters.status}
                            className="rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >
                            <option value="">All Status</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                        </select>
                        <button
                            type="submit"
                            className="rounded-lg bg-slate-900 px-6 py-2 text-sm font-semibold text-white hover:bg-slate-700"
                        >
                            Filter
                        </button>
                    </form>
                </div>

                {/* Fines Table */}
                <div className="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-slate-600">
                            <tr>
                                <th className="p-4 text-left font-semibold">Member</th>
                                <th className="p-4 text-left font-semibold">Book</th>
                                <th className="p-4 text-left font-semibold">Type</th>
                                <th className="p-4 text-left font-semibold">Amount</th>
                                <th className="p-4 text-left font-semibold">Paid</th>
                                <th className="p-4 text-left font-semibold">Remaining</th>
                                <th className="p-4 text-left font-semibold">Status</th>
                                <th className="p-4 text-left font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-200">
                            {fines.map((fine) => {
                                const remaining = parseFloat(fine.amount) - parseFloat(fine.paid_amount);
                                return (
                                    <tr key={fine.id} className="hover:bg-slate-50">
                                        <td className="p-4">
                                            <div className="font-medium text-slate-900">{fine.member.name}</div>
                                            <div className="text-xs text-slate-500">{fine.member.email}</div>
                                        </td>
                                        <td className="p-4">
                                            <div className="text-slate-900">{fine.borrowing_item.book.title}</div>
                                        </td>
                                        <td className="p-4">
                                            <span className="text-xs font-medium">{getTypeLabel(fine.type)}</span>
                                        </td>
                                        <td className="p-4 font-medium">
                                            Rp {formatCurrency(fine.amount)}
                                        </td>
                                        <td className="p-4 text-green-600">
                                            Rp {formatCurrency(fine.paid_amount)}
                                        </td>
                                        <td className="p-4 font-semibold text-red-600">
                                            Rp {formatCurrency(remaining)}
                                        </td>
                                        <td className="p-4">
                                            <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${getStatusBadge(fine.status)}`}>
                                                {fine.status}
                                            </span>
                                        </td>
                                        <td className="p-4">
                                            {fine.status !== 'paid' && (
                                                <button
                                                    onClick={() => setSelectedFine(fine)}
                                                    className="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700"
                                                >
                                                    Process Payment
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>

                {selectedFine && (
                    <PaymentModal
                        fine={selectedFine}
                        onClose={() => setSelectedFine(null)}
                    />
                )}
            </div>
        </AdminLayout>
    );
}
