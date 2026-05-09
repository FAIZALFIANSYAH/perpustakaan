import React from 'react';
import { Link, router } from '@inertiajs/react';
import LibrarianLayout from '@/Layouts/LibrarianLayout';
import SearchBar from '@/Components/SearchBar';
import { useTranslation } from 'react-i18next';

type Book = {
    id: number;
    title: string;
    author: string;
    isbn?: string | null;
    publish_year?: number | null;
    stock: number;
    cover?: string | null;
    is_active: boolean;
    category?: {
        name: string;
    } | null;
};

type Filters = {
    search: string;
};

type Props = {
    books: Book[];
    filters: Filters;
};

export default function Index({ books, filters }: Props) {
    const { t } = useTranslation();

    const handleDelete = (book: Book) => {
        if (!window.confirm(t('confirm_delete_book', { title: book.title }))) {
            return;
        }

        router.delete(route('librarian.books.destroy', book.id));
    };

    return (
        <LibrarianLayout>
            <div className="space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">{t('book_management')}</h1>
                        <p className="text-sm text-slate-500">{t('book_management_description')}</p>
                    </div>

                    <div className="flex items-center gap-3">
                        <SearchBar
                            routeName="librarian.books.index"
                            searchValue={filters.search}
                            placeholder={t('search_by_title_author_isbn')}
                        />
                        <Link
                            href={route('librarian.books.create')}
                            className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
                        >
                            {t('add_book')}
                        </Link>
                    </div>
                </div>

                <div className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-slate-600">
                            <tr>
                                <th className="p-4 text-left font-semibold">{t('cover_col')}</th>
                                <th className="p-4 text-left font-semibold">{t('title_col')}</th>
                                <th className="p-4 text-left font-semibold">{t('category_col')}</th>
                                <th className="p-4 text-left font-semibold">{t('author_col')}</th>
                                <th className="p-4 text-left font-semibold">{t('year_col')}</th>
                                <th className="p-4 text-left font-semibold">{t('stock_col')}</th>
                                <th className="p-4 text-left font-semibold">{t('status_col')}</th>
                                <th className="p-4 text-right font-semibold">{t('action_col')}</th>
                            </tr>
                        </thead>

                        <tbody className="divide-y divide-slate-200">
                            {books.length > 0 ? (
                                books.map((book) => (
                                    <tr key={book.id} className="align-top">
                                        <td className="p-4">
                                            {book.cover ? (
                                                <img
                                                    src={`/storage/${book.cover}`}
                                                    alt={book.title}
                                                    className="h-16 w-12 object-cover rounded-lg border border-slate-200"
                                                />
                                            ) : (
                                                <div className="h-16 w-12 bg-slate-200 rounded-lg flex items-center justify-center">
                                                    <span className="text-xs text-slate-500">{t('no_cover')}</span>
                                                </div>
                                            )}
                                        </td>
                                        <td className="p-4">
                                            <div className="font-medium text-slate-900">{book.title}</div>
                                            <div className="mt-1 text-xs text-slate-500">{book.isbn || '-'}</div>
                                        </td>
                                        <td className="p-4 text-slate-600">{book.category?.name ?? '-'}</td>
                                        <td className="p-4 text-slate-600">{book.author}</td>
                                        <td className="p-4 text-slate-600">{book.publish_year ?? '-'}</td>
                                        <td className="p-4 text-slate-600">{book.stock}</td>
                                        <td className="p-4">
                                            <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${book.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}`}>
                                                {book.is_active ? t('active_status') : t('inactive_status')}
                                            </span>
                                        </td>
                                        <td className="p-4">
                                            <div className="flex justify-end gap-2">
                                                <Link
                                                    href={route('librarian.books.edit', book.id)}
                                                    className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                                >
                                                    {t('edit_button')}
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() => handleDelete(book)}
                                                    className="rounded-lg bg-rose-600 px-3 py-2 text-xs font-medium text-white transition hover:bg-rose-500"
                                                >
                                                    {t('delete_button')}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={8} className="p-8 text-center text-slate-500">
                                        {t('no_books_data')}
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
