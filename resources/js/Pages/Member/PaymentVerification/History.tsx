import React from 'react';
import { Link } from '@inertiajs/react';
import MemberLayout from '@/Layouts/MemberLayout';

type PaymentVerification = {
    id: number;
    requested_amount: number;
    payment_method: string;
    reference_number: string | null;
    notes: string | null;
    status: 'pending' | 'verified' | 'rejected' | 'expired';
    requested_at: string;
    verified_at: string | null;
    rejection_reason: string | null;
    expires_at: string;
    fine: {
        id: number;
        type: string;
        amount: number;
        borrowing_item: {
            book: {
                title: string;
                author: string;
            };
        };
    };
    verifiedBy: {
        name: string | null;
    } | null;
    receipt: {
        receipt_number: string;
        id: number;
    } | null;
};

type PageProps = {
    paymentHistory: PaymentVerification[];
};

export default function History({ paymentHistory }: PageProps) {
    const getStatusBadge = (status: string) => {
        const styles = {
            pending: 'bg-amber-100 text-amber-700',
            verified: 'bg-emerald-100 text-emerald-700',
            rejected: 'bg-rose-100 text-rose-700',
            expired: 'bg-slate-100 text-slate-700',
        };

        return (
            <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${styles[status as keyof typeof styles]}`}>
                {status.charAt(0).toUpperCase() + status.slice(1)}
            </span>
        );
    };

    const getPaymentMethodLabel = (method: string) => {
        const labels = {
            cash: 'Cash',
            bank_transfer: 'Bank Transfer',
            e_wallet: 'E-Wallet',
            check: 'Check',
        };
        return labels[method as keyof typeof labels] || method;
    };

    return (
        <MemberLayout>
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">Payment History</h1>
                    <p className="text-sm text-slate-500">View your payment request history and receipts.</p>
                </div>

                {paymentHistory.length === 0 ? (
                    <div className="rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-200">
                        <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                            <svg className="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 className="mt-4 text-lg font-medium text-slate-900">No payment history</h3>
                        <p className="mt-2 text-sm text-slate-500">You haven't made any payment requests yet.</p>
                        <div className="mt-6">
                            <Link
                                href={route('member.fines.index')}
                                className="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
                            >
                                View Fines
                            </Link>
                        </div>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {paymentHistory.map((payment) => (
                            <div key={payment.id} className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                                <div className="flex items-start justify-between">
                                    <div className="flex-1">
                                        <div className="flex items-center gap-3 mb-2">
                                            {getStatusBadge(payment.status)}
                                            <span className="text-sm text-slate-500">
                                                Requested: {new Date(payment.requested_at).toLocaleDateString('id-ID')} {new Date(payment.requested_at).toLocaleTimeString('id-ID')}
                                            </span>
                                        </div>

                                        <div className="space-y-2">
                                            <div>
                                                <h3 className="font-semibold text-slate-900">
                                                    {payment.fine.borrowing_item.book.title}
                                                </h3>
                                                <p className="text-sm text-slate-600">
                                                    by {payment.fine.borrowing_item.book.author} • {payment.fine.type}
                                                </p>
                                            </div>

                                            <div className="grid grid-cols-2 gap-4 text-sm">
                                                <div>
                                                    <span className="font-medium text-slate-700">Amount:</span>
                                                    <span className="ml-2 text-slate-900">
                                                        Rp {payment.requested_amount.toLocaleString('id-ID')}
                                                    </span>
                                                </div>
                                                <div>
                                                    <span className="font-medium text-slate-700">Method:</span>
                                                    <span className="ml-2 text-slate-900">
                                                        {getPaymentMethodLabel(payment.payment_method)}
                                                    </span>
                                                </div>
                                                {payment.reference_number && (
                                                    <div>
                                                        <span className="font-medium text-slate-700">Reference:</span>
                                                        <span className="ml-2 text-slate-900">{payment.reference_number}</span>
                                                    </div>
                                                )}
                                                {payment.verified_at && (
                                                    <div>
                                                        <span className="font-medium text-slate-700">Verified:</span>
                                                        <span className="ml-2 text-slate-900">
                                                            {new Date(payment.verified_at).toLocaleDateString('id-ID')}
                                                        </span>
                                                    </div>
                                                )}
                                            </div>

                                            {payment.notes && (
                                                <div>
                                                    <span className="font-medium text-slate-700">Notes:</span>
                                                    <p className="mt-1 text-sm text-slate-600">{payment.notes}</p>
                                                </div>
                                            )}

                                            {payment.status === 'rejected' && payment.rejection_reason && (
                                                <div className="rounded-lg bg-rose-50 p-3 border border-rose-200">
                                                    <span className="font-medium text-rose-700">Rejection Reason:</span>
                                                    <p className="mt-1 text-sm text-rose-600">{payment.rejection_reason}</p>
                                                </div>
                                            )}

                                            {payment.status === 'pending' && (
                                                <div className="rounded-lg bg-amber-50 p-3 border border-amber-200">
                                                    <p className="text-sm text-amber-700">
                                                        Expires on: {new Date(payment.expires_at).toLocaleDateString('id-ID')} {new Date(payment.expires_at).toLocaleTimeString('id-ID')}
                                                    </p>
                                                </div>
                                            )}

                                            {payment.status === 'verified' && payment.receipt && (
                                                <div className="rounded-lg bg-emerald-50 p-3 border border-emerald-200">
                                                    <div className="flex items-center justify-between">
                                                        <div>
                                                            <span className="font-medium text-emerald-700">Receipt:</span>
                                                            <p className="text-sm text-emerald-600">
                                                                #{payment.receipt.receipt_number}
                                                            </p>
                                                        </div>
                                                        <Link
                                                            href={route('librarian.payment-verification.receipt.download', payment.receipt.id)}
                                                            className="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-emerald-700"
                                                        >
                                                            Download
                                                        </Link>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </MemberLayout>
    );
}
