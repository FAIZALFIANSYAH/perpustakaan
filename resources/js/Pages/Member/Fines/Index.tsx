import React, { useState, FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import MemberLayout from '@/Layouts/MemberLayout';

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
};

type Props = {
    fines: Fine[];
    statistics: Statistics;
    totalUnpaid: number;
};

// Payment request functionality disabled - handled by Super Admin
function PaymentActionButton({ fine }: { fine: Fine }) {
    // No payment actions for members - handled by Super Admin
    return null;
}

export default function MemberFinesIndex({ fines, statistics, totalUnpaid }: Props) {

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
        <MemberLayout>
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">My Fines</h1>
                    <p className="text-sm text-slate-500">View and manage your library fines.</p>
                </div>

                {totalUnpaid > 0 && (
                    <div className="rounded-2xl bg-red-50 p-6 ring-1 ring-red-200">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-lg font-semibold text-red-900">Outstanding Fines</h3>
                                <p className="text-sm text-red-700 mt-1">
                                    You have unpaid fines that need to be settled. Unpaid fines may block your ability to borrow books.
                                </p>
                            </div>
                            <div className="text-right">
                                <div className="text-3xl font-bold text-red-600">
                                    Rp {formatCurrency(totalUnpaid)}
                                </div>
                                <div className="text-sm text-red-700 mt-1">Total Unpaid</div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Statistics */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <div className="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <div className="text-xs font-medium uppercase tracking-wide text-slate-500">Total Fines</div>
                        <div className="mt-2 text-2xl font-bold text-slate-900">{statistics.total_fines}</div>
                    </div>
                    <div className="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <div className="text-xs font-medium uppercase tracking-wide text-slate-500">Paid</div>
                        <div className="mt-2 text-2xl font-bold text-green-600">{statistics.total_paid}</div>
                    </div>
                    <div className="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <div className="text-xs font-medium uppercase tracking-wide text-slate-500">Unpaid</div>
                        <div className="mt-2 text-2xl font-bold text-red-600">{statistics.total_unpaid}</div>
                    </div>
                </div>

                {/* Fines List */}
                <div className="space-y-4">
                    {fines.length === 0 ? (
                        <div className="rounded-2xl bg-white p-12 text-center shadow-sm ring-1 ring-slate-200">
                            <p className="text-slate-500">You have no fines. Great job returning books on time!</p>
                        </div>
                    ) : (
                        fines.map((fine) => {
                            const remaining = parseFloat(fine.amount) - parseFloat(fine.paid_amount);
                            return (
                                <div key={fine.id} className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                                    <div className="flex items-start justify-between mb-4">
                                        <div>
                                            <h3 className="text-lg font-semibold text-slate-900">
                                                {fine.borrowing_item.book.title}
                                            </h3>
                                            <p className="text-sm text-slate-500 mt-1">
                                                Fine Type: {getTypeLabel(fine.type)}
                                            </p>
                                        </div>
                                        <span className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ${getStatusBadge(fine.status)}`}>
                                            {fine.status}
                                        </span>
                                    </div>

                                    {fine.reason && (
                                        <div className="mb-4 p-3 bg-slate-50 rounded-lg">
                                            <p className="text-sm text-slate-700">{fine.reason}</p>
                                        </div>
                                    )}

                                    <div className="grid gap-3 sm:grid-cols-3 mb-4">
                                        <div>
                                            <div className="text-xs text-slate-500">Total Fine</div>
                                            <div className="text-lg font-semibold text-slate-900">
                                                Rp {formatCurrency(fine.amount)}
                                            </div>
                                        </div>
                                        <div>
                                            <div className="text-xs text-slate-500">Paid Amount</div>
                                            <div className="text-lg font-semibold text-green-600">
                                                Rp {formatCurrency(fine.paid_amount)}
                                            </div>
                                        </div>
                                        <div>
                                            <div className="text-xs text-slate-500">Remaining</div>
                                            <div className="text-lg font-semibold text-red-600">
                                                Rp {formatCurrency(remaining)}
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex items-center justify-between pt-4 border-t border-slate-200">
                                        <div className="text-xs text-slate-500">
                                            Due Date: {new Date(fine.due_date).toLocaleDateString()}
                                        </div>
                                        <PaymentActionButton fine={fine} />
                                    </div>

                                    {/* Payment History */}
                                    {fine.payments && fine.payments.length > 0 && (
                                        <div className="mt-4 pt-4 border-t border-slate-200">
                                            <h4 className="text-sm font-semibold text-slate-900 mb-2">Payment History</h4>
                                            <div className="space-y-2">
                                                {fine.payments.map((payment) => (
                                                    <div key={payment.id} className="flex items-center justify-between text-sm p-2 bg-slate-50 rounded">
                                                        <div>
                                                            <span className="font-medium">Rp {formatCurrency(payment.amount)}</span>
                                                            <span className="text-slate-500 ml-2">via {payment.payment_method}</span>
                                                        </div>
                                                        <div className="text-slate-500">
                                                            {new Date(payment.created_at).toLocaleDateString()}
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            );
                        })
                    )}
                </div>
            </div>
        </MemberLayout>
    );
}
