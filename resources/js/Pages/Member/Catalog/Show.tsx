import React from 'react';
import { Link, useForm } from '@inertiajs/react';
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
    const { post: postBorrow, processing: processingBorrow, errors: errorsBorrow } = useForm({});
    const { post: postReserve, processing: processingReserve, errors: errorsReserve } = useForm({});
    const available = book.is_active && book.stock > 0;

    return (
        <MemberLayout>
            <div className="space-y-6">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">Book Detail</h1>
                        <p className="text-sm text-slate-500">Informasi lengkap buku untuk anggota perpustakaan.</p>
                    </div>

                    <Link
                        href={route('member.catalog.index')}
                        className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        Back to Catalog
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
                                    <span className="text-sm">No Cover</span>
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="space-y-6">
                        <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <div className="text-xs font-medium uppercase tracking-wide text-slate-500">
                                {book.category?.name ?? 'Uncategorized'}
                            </div>
                            <h2 className="mt-2 text-3xl font-bold text-slate-900">{book.title}</h2>
                            <p className="mt-2 text-lg text-slate-600">{book.author}</p>

                            <div className="mt-6 grid gap-4 md:grid-cols-2">
                                <div>
                                    <div className="text-xs font-medium uppercase tracking-wide text-slate-500">Publisher</div>
                                    <div className="mt-1 text-sm text-slate-700">{book.publisher ?? '-'}</div>
                                </div>
                                <div>
                                    <div className="text-xs font-medium uppercase tracking-wide text-slate-500">ISBN</div>
                                    <div className="mt-1 text-sm text-slate-700">{book.isbn ?? '-'}</div>
                                </div>
                                <div>
                                    <div className="text-xs font-medium uppercase tracking-wide text-slate-500">Publish Year</div>
                                    <div className="mt-1 text-sm text-slate-700">{book.publish_year ?? '-'}</div>
                                </div>
                                <div>
                                    <div className="text-xs font-medium uppercase tracking-wide text-slate-500">Stock</div>
                                    <div className="mt-1 text-sm text-slate-700">{book.stock}</div>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <div className="flex items-center justify-between gap-4">
                                <div>
                                    <h3 className="text-lg font-semibold text-slate-900">Availability</h3>
                                    <p className="mt-1 text-sm text-slate-500">Status ketersediaan buku untuk reservasi anggota.</p>
                                </div>

                                <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${available ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}`}>
                                    {available ? 'Available' : 'Out of Stock'}
                                </span>
                            </div>

                            <div className="mt-6 flex flex-wrap gap-3">
                                <button
                                    type="button"
                                    onClick={() => postBorrow(route('member.catalog.borrow', book.id))}
                                    disabled={!available || hasActiveBorrowing || processingBorrow}
                                    className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {processingBorrow ? 'Processing...' : 'Borrow Now'}
                                </button>

                                <button
                                    type="button"
                                    onClick={() => postReserve(route('member.catalog.reserve', book.id))}
                                    disabled={hasActiveBorrowing || hasPendingReservation || processingReserve}
                                    className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {processingReserve ? 'Processing...' : 'Reserve'}
                                </button>
                            </div>

                            {(errorsBorrow as Record<string, string>).book && <p className="mt-2 text-sm text-rose-600">{(errorsBorrow as Record<string, string>).book}</p>}
                            {(errorsReserve as Record<string, string>).book && <p className="mt-2 text-sm text-rose-600">{(errorsReserve as Record<string, string>).book}</p>}

                            {hasActiveBorrowing && (
                                <p className="mt-2 text-sm text-amber-600">You already have an active borrowing for this book.</p>
                            )}
                            {hasPendingReservation && (
                                <p className="mt-2 text-sm text-blue-600">You have a pending reservation for this book.</p>
                            )}

                            <p className="mt-2 text-xs text-slate-500">
                                Borrow Now creates an immediate borrowing with a 7-day due date. Reserve queues you for when the book is available.
                            </p>
                        </div>

                        <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h3 className="text-lg font-semibold text-slate-900">Description</h3>
                            <p className="mt-4 text-sm leading-6 text-slate-600">{book.description ?? 'Tidak ada deskripsi untuk buku ini.'}</p>
                        </div>
                    </div>
                </div>
            </div>
        </MemberLayout>
    );
}
