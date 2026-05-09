import React, { FormEventHandler } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import MemberLayout from '@/Layouts/MemberLayout';

type Category = {
    id: number;
    name: string;
};

type Book = {
    id: number;
    title: string;
    author: string;
    cover?: string | null;
    stock: number;
    is_active: boolean;
    category?: {
        name: string;
    } | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedBooks = {
    data: Book[];
    links: PaginationLink[];
};

type Props = {
    books: PaginatedBooks;
    categories: Category[];
    filters: {
        search?: string | null;
        category_id?: number | null;
    };
};

function StockBadge({ book }: { book: Book }) {
    const { t } = useTranslation();
    const available = book.is_active && book.stock > 0;

    return (
        <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${available ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}`}>
            {available ? t('available') : t('out_of_stock')}
        </span>
    );
}

export default function Index({ books, categories, filters }: Props) {
    const { t } = useTranslation();
    const { data, setData } = useForm({
        search: filters.search ?? '',
        category_id: filters.category_id ? String(filters.category_id) : '',
    });

    const submitFilter: FormEventHandler = (event) => {
        event.preventDefault();
        router.get(route('member.catalog.index'), data, { preserveState: true, replace: true });
    };

    const resetFilter = () => {
        setData('search', '');
        setData('category_id', '');
        router.get(route('member.catalog.index'), {}, { preserveState: true, replace: true });
    };

    return (
        <MemberLayout>
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">{t('book_catalog')}</h1>
                    <p className="text-sm text-slate-500">{t('catalog_description')}</p>
                </div>

                <form onSubmit={submitFilter} className="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <div className="flex flex-col gap-3 lg:flex-row">
                        <input
                            value={data.search}
                            onChange={(event) => setData('search', event.target.value)}
                            placeholder={t('search_placeholder')}
                            className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        />
                        <select
                            value={data.category_id}
                            onChange={(event) => setData('category_id', event.target.value)}
                            className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 lg:max-w-xs"
                        >
                            <option value="">{t('all_categories')}</option>
                            {categories.map((category) => (
                                <option key={category.id} value={String(category.id)}>
                                    {category.name}
                                </option>
                            ))}
                        </select>
                        <div className="flex gap-3">
                            <button
                                type="submit"
                                className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
                            >
                                {t('search')}
                            </button>
                            <button
                                type="button"
                                onClick={resetFilter}
                                className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                {t('reset')}
                            </button>
                        </div>
                    </div>
                </form>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {books.data.length > 0 ? (
                        books.data.map((book) => (
                            <div key={book.id} className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                                <div className="flex h-52 items-center justify-center bg-slate-100 text-slate-400">
                                    {book.cover ? (
                                        <img 
                                            src={book.cover.startsWith('http') || book.cover.startsWith('data:') ? book.cover : `/storage/${book.cover}`} 
                                            alt={book.title} 
                                            className="h-full w-full object-cover" 
                                        />
                                    ) : (
                                        <div className="flex flex-col items-center">
                                            <span className="text-4xl mb-2">📚</span>
                                            <span className="text-sm">{t('no_cover')}</span>
                                        </div>
                                    )}
                                </div>
                                <div className="space-y-4 p-5">
                                    <div>
                                        <div className="text-xs font-medium uppercase tracking-wide text-slate-500">
                                            {book.category?.name ?? t('uncategorized')}
                                        </div>
                                        <h2 className="mt-2 text-lg font-semibold text-slate-900">{book.title}</h2>
                                        <p className="mt-1 text-sm text-slate-600">{book.author}</p>
                                    </div>

                                    <div className="flex items-center justify-between">
                                        <StockBadge book={book} />
                                        <span className="text-xs text-slate-500">{t('stock_label')}: {book.stock}</span>
                                    </div>

                                    <Link
                                        href={route('member.catalog.show', book.id)}
                                        className="inline-flex w-full items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
                                    >
                                        {t('view_detail')}
                                    </Link>
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="col-span-full rounded-2xl bg-white p-8 text-center text-slate-500 shadow-sm ring-1 ring-slate-200">
                            {t('no_books_found')}
                        </div>
                    )}
                </div>

                <div className="flex flex-wrap gap-2">
                    {books.links.map((link, index) => (
                        <button
                            key={`${link.label}-${index}`}
                            type="button"
                            disabled={!link.url}
                            onClick={() => link.url && router.visit(link.url)}
                            className={`rounded-lg px-3 py-2 text-sm ${link.active ? 'bg-slate-900 text-white' : 'border border-slate-300 bg-white text-slate-700'} disabled:cursor-not-allowed disabled:opacity-50`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            </div>
        </MemberLayout>
    );
}
