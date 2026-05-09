import React from 'react';
import { Link, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import MemberLayout from '@/Layouts/MemberLayout';

type Book = {
    id: number;
    title: string;
    author: string;
    publisher?: string | null;
    isbn?: string | null;
    publish_year?: number | null;
    cover?: string | null;
    description?: string | null;
    stock: number;
    is_active: boolean;
    category?: {
        name: string;
    } | null;
};

type Props = {
    book: Book;
    hasActiveBorrowing: boolean;
    hasPendingReservation: boolean;
};

export default function Show({ book, hasActiveBorrowing, hasPendingReservation }: Props) {
    const { t } = useTranslation();
    const { post: postBorrow, processing: processingBorrow, errors: errorsBorrow } = useForm({});
    const { post: postReserve, processing: processingReserve, errors: errorsReserve } = useForm({});
    const available = book.is_active && book.stock > 0;

    return (
        <MemberLayout>
            <div className="space-y-6">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">{t('book_detail')}</h1>
                        <p className="text-sm text-slate-500">{t('book_detail_description')}</p>
                    </div>

                    <Link
                        href={route('member.catalog.index')}
                        className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        {t('back_to_catalog')}
                    </Link>
                </div>

                <div className="grid gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">
                    <div className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                        <div className="flex h-[420px] items-center justify-center bg-slate-100 text-slate-400">
                            {book.cover ? (
                                <img 
                                    src={book.cover.startsWith('http') || book.cover.startsWith('data:') ? book.cover : `/storage/${book.cover}`} 
                                    alt={book.title} 
                                    className="h-full w-full object-cover" 
                                />
                            ) : (
                                <div className="flex flex-col items-center">
                                    <span className="text-6xl mb-3">📚</span>
                                    <span className="text-sm">{t('no_cover')}</span>
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="space-y-6">
                        <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <div className="text-xs font-medium uppercase tracking-wide text-slate-500">
                                {book.category?.name ?? t('uncategorized')}
                            </div>
                            <h2 className="mt-2 text-3xl font-bold text-slate-900">{book.title}</h2>
                            <p className="mt-2 text-lg text-slate-600">{book.author}</p>

                            <div className="mt-6 grid gap-4 md:grid-cols-2">
                                <div>
                                    <div className="text-xs font-medium uppercase tracking-wide text-slate-500">{t('publisher')}</div>
                                    <div className="mt-1 text-sm text-slate-700">{book.publisher ?? '-'}</div>
                                </div>
                                <div>
                                    <div className="text-xs font-medium uppercase tracking-wide text-slate-500">{t('isbn')}</div>
                                    <div className="mt-1 text-sm text-slate-700">{book.isbn ?? '-'}</div>
                                </div>
                                <div>
                                    <div className="text-xs font-medium uppercase tracking-wide text-slate-500">{t('publish_year')}</div>
                                    <div className="mt-1 text-sm text-slate-700">{book.publish_year ?? '-'}</div>
                                </div>
                                <div>
                                    <div className="text-xs font-medium uppercase tracking-wide text-slate-500">{t('stock')}</div>
                                    <div className="mt-1 text-sm text-slate-700">{book.stock}</div>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <div className="flex items-center justify-between gap-4">
                                <div>
                                    <h3 className="text-lg font-semibold text-slate-900">{t('availability')}</h3>
                                    <p className="mt-1 text-sm text-slate-500">{t('availability_description')}</p>
                                </div>

                                <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${available ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}`}>
                                    {available ? t('available') : t('out_of_stock')}
                                </span>
                            </div>

                            <div className="mt-6 flex flex-wrap gap-3">
                                <button
                                    type="button"
                                    onClick={() => postBorrow(route('member.catalog.borrow', book.id))}
                                    disabled={!available || hasActiveBorrowing || processingBorrow}
                                    className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {processingBorrow ? t('processing') : t('borrow_now')}
                                </button>

                                <button
                                    type="button"
                                    onClick={() => postReserve(route('member.catalog.reserve', book.id))}
                                    disabled={hasActiveBorrowing || hasPendingReservation || processingReserve}
                                    className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {processingReserve ? t('processing') : t('reserve')}
                                </button>
                            </div>

                            {(errorsBorrow as Record<string, string>).book && <p className="mt-2 text-sm text-rose-600">{(errorsBorrow as Record<string, string>).book}</p>}
                            {(errorsReserve as Record<string, string>).book && <p className="mt-2 text-sm text-rose-600">{(errorsReserve as Record<string, string>).book}</p>}

                            {hasActiveBorrowing && (
                                <p className="mt-2 text-sm text-amber-600">{t('active_borrowing_warning')}</p>
                            )}
                            {hasPendingReservation && (
                                <p className="mt-2 text-sm text-blue-600">{t('pending_reservation_warning')}</p>
                            )}

                            <p className="mt-2 text-xs text-slate-500">
                                {t('borrow_reserve_note')}
                            </p>
                        </div>

                        <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h3 className="text-lg font-semibold text-slate-900">{t('description')}</h3>
                            <p className="mt-4 text-sm leading-6 text-slate-600">{book.description ?? t('no_description')}</p>
                        </div>
                    </div>
                </div>
            </div>
        </MemberLayout>
    );
}
