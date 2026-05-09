import React, { useState, FormEventHandler } from 'react';
import { Link, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
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

function PaymentModal({ fine, onClose, t }: { fine: Fine; onClose: () => void; t: (key: string, options?: any) => string }) {
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
                <h3 className="text-lg font-semibold text-slate-900 mb-4">{t('process_fine_payment')}</h3>

                <div className="space-y-2 mb-4 text-sm">
                    <div className="flex justify-between">
                        <span className="text-slate-600">{t('member_label')}:</span>
                        <span className="font-medium">{fine.member.name}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-slate-600">{t('book_label')}:</span>
                        <span className="font-medium">{fine.borrowing_item.book.title}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-slate-600">{t('total_fine_label')}:</span>
                        <span className="font-medium">Rp {new Intl.NumberFormat('id-ID').format(parseFloat(fine.amount))}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-slate-600">{t('remaining_label')}:</span>
                        <span className="font-semibold text-red-600">Rp {new Intl.NumberFormat('id-ID').format(remaining)}</span>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-2">{t('payment_amount_label')}</label>
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
                        <label className="block text-sm font-medium text-slate-700 mb-2">{t('payment_method_label')}</label>
                        <select
                            value={data.payment_method}
                            onChange={(e) => setData('payment_method', e.target.value)}
                            className="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >
                            <option value="cash">{t('cash_option')}</option>
                            <option value="transfer">{t('transfer_option')}</option>
                        </select>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-2">{t('notes_optional_label')}</label>
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
                            {t('cancel_button')}
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="flex-1 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:opacity-60"
                        >
                            {processing ? t('processing_button') : t('submit_payment_button')}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

export default function FinesIndex({ fines, statistics, filters }: Props) {
    const { t } = useTranslation();
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
        const labels: Record<string, string> = {
            late_return: t('late_return'),
            lost_book: t('lost_book'),
            damage: t('damage'),
        };
        return labels[type] || type;
    };

    const getStatusLabel = (status: string) => {
        const labels: Record<string, string> = {
            unpaid: t('status_unpaid'),
            partial: t('status_partial'),
            paid: t('status_paid'),
        };
        return labels[status] || status;
    };

    return (
        <AdminLayout>
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">{t('fines_management')}</h1>
                        <p className="text-sm text-slate-500">{t('fines_description')}</p>
                    </div>
                </div>

                {/* Statistics */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label={t('total_fines')}
                        value={statistics.total_fines}
                        color="bg-blue-500 text-white"
                    />
                    <StatCard
                        label={t('unpaid_fines')}
                        value={statistics.total_unpaid}
                        color="bg-red-500 text-white"
                    />
                    <StatCard
                        label={t('paid_fines')}
                        value={statistics.total_paid}
                        color="bg-green-500 text-white"
                    />
                    <StatCard
                        label={t('total_unpaid_amount')}
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
                            placeholder={t('search_by_member')}
                            className="flex-1 min-w-[200px] rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        />
                        <select
                            name="status"
                            defaultValue={filters.status}
                            className="rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >
                            <option value="">{t('all_status')}</option>
                            <option value="unpaid">{t('status_unpaid')}</option>
                            <option value="partial">{t('status_partial')}</option>
                            <option value="paid">{t('status_paid')}</option>
                        </select>
                        <button
                            type="submit"
                            className="rounded-lg bg-slate-900 px-6 py-2 text-sm font-semibold text-white hover:bg-slate-700"
                        >
                            {t('filter_button')}
                        </button>
                    </form>
                </div>

                {/* Fines Table */}
                <div className="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-slate-600">
                            <tr>
                                <th className="p-4 text-left font-semibold">{t('member_col')}</th>
                                <th className="p-4 text-left font-semibold">{t('book_col')}</th>
                                <th className="p-4 text-left font-semibold">{t('type_col')}</th>
                                <th className="p-4 text-left font-semibold">{t('amount_col')}</th>
                                <th className="p-4 text-left font-semibold">{t('paid_col')}</th>
                                <th className="p-4 text-left font-semibold">{t('remaining_col')}</th>
                                <th className="p-4 text-left font-semibold">{t('status_col')}</th>
                                <th className="p-4 text-left font-semibold">{t('actions_col')}</th>
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
                                                {getStatusLabel(fine.status)}
                                            </span>
                                        </td>
                                        <td className="p-4">
                                            {fine.status !== 'paid' && (
                                                <button
                                                    onClick={() => setSelectedFine(fine)}
                                                    className="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700"
                                                >
                                                    {t('process_payment_button')}
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
                        t={t}
                    />
                )}
            </div>
        </AdminLayout>
    );
}
