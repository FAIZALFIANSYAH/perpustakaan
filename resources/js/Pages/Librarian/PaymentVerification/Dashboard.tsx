import React from 'react';
import { Link } from '@inertiajs/react';
import LibrarianLayout from '@/Layouts/LibrarianLayout';

type PaymentVerification = {
    id: number;
    requested_amount: number;
    payment_method: string;
    reference_number: string | null;
    requested_at: string;
    expires_at: string;
    fine: {
        id: number;
        type: string;
        borrowing_item: {
            book: {
                title: string;
                author: string;
            };
        };
    };
    member: {
        id: number;
        name: string;
        email: string;
    };
};

type Statistics = {
    pending_count: number;
    expired_count: number;
    verified_today: number;
    rejected_today: number;
    total_amount_pending: number;
};

type PageProps = {
    statistics: Statistics;
    pendingPayments: PaymentVerification[];
};

export default function Dashboard({ statistics, pendingPayments }: PageProps) {
    const getPaymentMethodLabel = (method: string) => {
        const labels = {
            cash: 'Cash',
            bank_transfer: 'Bank Transfer',
            e_wallet: 'E-Wallet',
            check: 'Check',
        };
        return labels[method as keyof typeof labels] || method;
    };

    const isExpiringSoon = (expiresAt: string) => {
        const expiry = new Date(expiresAt);
        const now = new Date();
        const hoursUntilExpiry = (expiry.getTime() - now.getTime()) / (1000 * 60 * 60);
        return hoursUntilExpiry < 6 && hoursUntilExpiry > 0;
    };

    return (
        <LibrarianLayout>
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">Payment Verification Dashboard</h1>
                    <p className="text-sm text-slate-500">Review and verify payment requests from members.</p>
                </div>

                {/* Statistics Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100">
                                    <svg className="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-slate-600">Pending</p>
                                <p className="text-2xl font-bold text-slate-900">{statistics.pending_count}</p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-100">
                                    <svg className="h-5 w-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-slate-600">Expired</p>
                                <p className="text-2xl font-bold text-slate-900">{statistics.expired_count}</p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100">
                                    <svg className="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-slate-600">Verified Today</p>
                                <p className="text-2xl font-bold text-slate-900">{statistics.verified_today}</p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100">
                                    <svg className="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-slate-600">Total Pending</p>
                                <p className="text-2xl font-bold text-slate-900">
                                    Rp {statistics.total_amount_pending.toLocaleString('id-ID')}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Quick Actions */}
                <div className="flex flex-wrap gap-3">
                    <Link
                        href={route('librarian.payment-verification.index')}
                        className="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
                    >
                        View All Payments
                    </Link>
                    <Link
                        href={route('librarian.payment-verification.process-expired')}
                        method="post"
                        as="button"
                        className="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        Process Expired
                    </Link>
                </div>

                {/* Pending Payments */}
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-slate-900">Pending Payment Requests</h2>
                        <span className="text-sm text-slate-500">
                            {pendingPayments.length} request{pendingPayments.length !== 1 ? 's' : ''}
                        </span>
                    </div>

                    {pendingPayments.length === 0 ? (
                        <div className="rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-200">
                            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100">
                                <svg className="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 className="mt-4 text-lg font-medium text-slate-900">All caught up!</h3>
                            <p className="mt-2 text-sm text-slate-500">No pending payment requests to review.</p>
                        </div>
                    ) : (
                        <div className="space-y-3">
                            {pendingPayments.map((payment) => (
                                <div key={payment.id} className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                                    <div className="flex items-start justify-between">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-3 mb-2">
                                                <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${
                                                    isExpiringSoon(payment.expires_at) 
                                                        ? 'bg-red-100 text-red-700' 
                                                        : 'bg-amber-100 text-amber-700'
                                                }`}>
                                                    {isExpiringSoon(payment.expires_at) ? 'Expiring Soon' : 'Pending'}
                                                </span>
                                                <span className="text-sm text-slate-500">
                                                    Expires: {new Date(payment.expires_at).toLocaleDateString('id-ID')} {new Date(payment.expires_at).toLocaleTimeString('id-ID')}
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
                                                        <span className="font-medium text-slate-700">Member:</span>
                                                        <span className="ml-2 text-slate-900">{payment.member.name}</span>
                                                    </div>
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
                                                </div>

                                                <div className="text-sm text-slate-500">
                                                    Requested: {new Date(payment.requested_at).toLocaleDateString('id-ID')} {new Date(payment.requested_at).toLocaleTimeString('id-ID')}
                                                </div>
                                            </div>
                                        </div>

                                        <div className="ml-4 flex flex-col gap-2">
                                            <Link
                                                href={route('librarian.payment-verification.show', payment.id)}
                                                className="inline-flex items-center rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-slate-700"
                                            >
                                                Review
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </LibrarianLayout>
    );
}
