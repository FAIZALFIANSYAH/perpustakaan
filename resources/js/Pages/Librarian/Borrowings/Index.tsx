import React from 'react';
import { Link } from '@inertiajs/react';
import LibrarianLayout from '@/Layouts/LibrarianLayout';
import SearchBar from '@/Components/SearchBar';
import { useTranslation } from 'react-i18next';

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
    const { t } = useTranslation();

    const statusLabel = (status: string) => {
        const key = `status_${status}`;
        const label = t(key);
        return label === key ? status : label;
    };

    return (
        <LibrarianLayout>
            <div className="space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">{t('borrowing_management')}</h1>
                        <p className="text-sm text-slate-500">{t('borrowing_management_description')}</p>
                    </div>

                    <div className="flex items-center gap-3">
                        <SearchBar
                            routeName="librarian.borrowings.index"
                            searchValue={filters.search}
                            placeholder={t('search_by_code_member_book')}
                        />
                        <Link
                            href={route('librarian.borrowings.create')}
                            className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
                        >
                            {t('add_borrowing')}
                        </Link>
                    </div>
                </div>

                <div className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-slate-600">
                            <tr>
                                <th className="p-4 text-left font-semibold">{t('code_col')}</th>
                                <th className="p-4 text-left font-semibold">{t('member_col')}</th>
                                <th className="p-4 text-left font-semibold">{t('books')}</th>
                                <th className="p-4 text-left font-semibold">{t('borrowed_col')}</th>
                                <th className="p-4 text-left font-semibold">{t('due_col')}</th>
                                <th className="p-4 text-left font-semibold">{t('status')}</th>
                                <th className="p-4 text-left font-semibold">{t('processed_by_col')}</th>
                                <th className="p-4 text-right font-semibold">{t('action_col')}</th>
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
                                                {statusLabel(borrowing.status)}
                                            </span>
                                        </td>
                                        <td className="p-4 text-slate-600">{borrowing.processedBy?.name ?? borrowing.processed_by?.name ?? '-'}</td>
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
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={8} className="p-8 text-center text-slate-500">
                                        {t('no_borrowings_data')}
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
