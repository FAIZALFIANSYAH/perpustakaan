import React from 'react';
import MemberLayout from '@/Layouts/MemberLayout';
import { useTranslation } from 'react-i18next';

type BorrowingItem = {
    id: number;
    quantity: number;
    returned_quantity: number;

    book?: {
        title: string;
        author?: string;
        cover?: string | null;
    } | null;
};

type Borrowing = {
    id: number;
    code: string;
    borrowed_at: string;
    due_at: string;
    returned_at?: string | null;
    status: string;
    items: BorrowingItem[];
};

type Props = {
    borrowings: Borrowing[];
};

export default function History({ borrowings }: Props) {
    const { t } = useTranslation();

    return (
        <MemberLayout>
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">
                        {t('borrowing_history')}
                    </h1>

                    <p className="text-sm text-slate-500">
                        {t('borrowing_history_description')}
                    </p>
                </div>

                <div className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-slate-600">
                            <tr>
                                <th className="p-4 text-left font-semibold">
                                    {t('code')}
                                </th>

                                <th className="p-4 text-left font-semibold">
                                    {t('books')}
                                </th>

                                <th className="p-4 text-left font-semibold">
                                    {t('borrowed')}
                                </th>

                                <th className="p-4 text-left font-semibold">
                                    {t('returned')}
                                </th>

                                <th className="p-4 text-left font-semibold">
                                    {t('due_date')}
                                </th>

                                <th className="p-4 text-left font-semibold">
                                    {t('status')}
                                </th>
                            </tr>
                        </thead>

                        <tbody className="divide-y divide-slate-200">
                            {borrowings.length > 0 ? (
                                borrowings.map((borrowing) => (
                                    <tr
                                        key={borrowing.id}
                                        className="align-top"
                                    >
                                        <td className="p-4 font-medium text-slate-900">
                                            {borrowing.code}
                                        </td>

                                        <td className="p-4 text-slate-600">
                                            <div className="space-y-2">
                                                {borrowing.items.map((item) => (
                                                    <div
                                                        key={item.id}
                                                        className="flex items-start gap-2"
                                                    >
                                                        {item.book?.cover ? (
                                                            <img
                                                                src={
                                                                    item.book.cover.startsWith(
                                                                        'http',
                                                                    ) ||
                                                                        item.book.cover.startsWith(
                                                                            'data:',
                                                                        )
                                                                        ? item
                                                                            .book
                                                                            .cover
                                                                        : `/storage/${item.book.cover}`
                                                                }
                                                                alt={
                                                                    item.book
                                                                        .title
                                                                }
                                                                className="h-10 w-7 rounded border border-slate-200 object-cover flex-shrink-0"
                                                            />
                                                        ) : (
                                                            <div className="flex h-10 w-7 flex-shrink-0 items-center justify-center rounded border border-slate-200 bg-slate-200">
                                                                <span className="text-[8px]">
                                                                    📚
                                                                </span>
                                                            </div>
                                                        )}

                                                        <div className="text-xs">
                                                            <div className="font-medium text-slate-900">
                                                                {item.book
                                                                    ?.title ??
                                                                    '-'}
                                                            </div>

                                                            <div className="text-slate-500">
                                                                x{' '}
                                                                {item.quantity}
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </td>

                                        <td className="p-4 text-slate-600">
                                            {borrowing.borrowed_at}
                                        </td>

                                        <td className="p-4 text-slate-600">
                                            {borrowing.returned_at ?? '-'}
                                        </td>

                                        <td className="p-4 text-slate-600">
                                            {borrowing.due_at}
                                        </td>

                                        <td className="p-4">
                                            <span
                                                className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${borrowing.status ===
                                                        'returned' ||
                                                        borrowing.status ===
                                                        'complete'
                                                        ? 'bg-emerald-100 text-emerald-700'
                                                        : borrowing.status ===
                                                            'partial'
                                                            ? 'bg-blue-100 text-blue-700'
                                                            : borrowing.status ===
                                                                'lost'
                                                                ? 'bg-red-100 text-red-700'
                                                                : borrowing.status ===
                                                                    'awaiting_fine_payment'
                                                                    ? 'bg-amber-100 text-amber-700'
                                                                    : 'bg-gray-100 text-gray-700'
                                                    }`}
                                            >
                                                {borrowing.status ===
                                                    'awaiting_fine_payment'
                                                    ? t(
                                                        'awaiting_fine_payment',
                                                    )
                                                    : borrowing.status}
                                            </span>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="p-8 text-center text-slate-500"
                                    >
                                        {t('no_borrowing_history')}
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </MemberLayout>
    );
}