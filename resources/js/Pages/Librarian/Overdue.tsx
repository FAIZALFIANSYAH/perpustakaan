import React, { useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import LibrarianLayout from '@/Layouts/LibrarianLayout';
import { AlertTriangle } from 'lucide-react';
import { useTranslation } from 'react-i18next';

// Status mapping for display
const getStatusInfo = (status: string, t: (key: string) => string) => {
    const statusMap: Record<string, { label: string; color: string; bgColor: string }> = {
        borrowed: {
            label: t('status_borrowed'),
            color: "text-blue-700",
            bgColor: "bg-blue-100"
        },
        overdue: {
            label: t('status_overdue'),
            color: "text-red-700",
            bgColor: "bg-red-100"
        },
        returned: {
            label: t('status_returned'),
            color: "text-green-700",
            bgColor: "bg-green-100"
        },
        late_payment: {
            label: t('status_late_payment'),
            color: "text-orange-700",
            bgColor: "bg-orange-100"
        },
        complete: {
            label: t('status_complete'),
            color: "text-emerald-700",
            bgColor: "bg-emerald-100"
        },
        lost: {
            label: t('status_lost'),
            color: "text-purple-700",
            bgColor: "bg-purple-100"
        },
        partial: {
            label: t('status_partial'),
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
    const { t } = useTranslation();

    return (
        <LibrarianLayout>
            <Head title={t('overdue_borrowings_title')} />

            <div className="space-y-6">
                <div>
                    <h2 className="text-2xl font-bold text-slate-900">{t('overdue_borrowings_title')}</h2>
                    <p className="text-slate-500">{t('overdue_borrowings_description')}</p>
                </div>

                <div className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-slate-600">
                            <tr>
                                <th className="p-4 text-left font-semibold">{t('borrow_code_col')}</th>
                                <th className="p-4 text-left font-semibold">{t('member_col')}</th>
                                <th className="p-4 text-left font-semibold">{t('books')}</th>
                                <th className="p-4 text-left font-semibold">{t('due_date_col')}</th>
                                <th className="p-4 text-left font-semibold">{t('days_late_col')}</th>
                                <th className="p-4 text-left font-semibold">{t('status')}</th>
                                <th className="p-4 text-right font-semibold">{t('action_col')}</th>
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
                                                    {t('day_late_badge', { count: daysLate })}
                                                </span>
                                            </td>
                                            <td className="p-4">
                                                <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${getStatusInfo(borrowing.status, t).bgColor} ${getStatusInfo(borrowing.status, t).color}`}>
                                                    {getStatusInfo(borrowing.status, t).label}
                                                </span>
                                            </td>
                                            <td className="p-4">
                                                <div className="flex justify-end">
                                                    <Link
                                                        href={route('librarian.borrowings.show', borrowing.id)}
                                                        className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                                    >
                                                        {t('detail_button')}
                                                    </Link>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })
                            ) : (
                                <tr>
                                    <td colSpan={7} className="p-8 text-center text-slate-500">
                                        <div className="space-y-1">
                                            <div className="font-medium">{t('overdue_none_title')}</div>
                                            <div className="text-sm">{t('overdue_none_subtitle')}</div>
                                        </div>
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
