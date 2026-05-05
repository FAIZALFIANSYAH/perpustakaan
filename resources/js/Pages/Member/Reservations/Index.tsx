import React from 'react';
import { Head, Link } from '@inertiajs/react';
import MemberLayout from '@/Layouts/MemberLayout';
import { Bookmark } from 'lucide-react';

type Book = {
    id: number;
    title: string;
    author?: string;
};

type Reservation = {
    id: number;
    status: string;
    notes: string | null;
    created_at: string;
    fulfilled_at: string | null;
    book: Book | null;
};

type Props = {
    reservations: Reservation[];
};

function StatusBadge({ status }: { status: string }) {
    const styles: Record<string, string> = {
        pending: 'bg-amber-100 text-amber-700',
        fulfilled: 'bg-emerald-100 text-emerald-700',
        cancelled: 'bg-slate-100 text-slate-600',
    };

    return (
        <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${styles[status] ?? 'bg-slate-100 text-slate-600'}`}>
            {status}
        </span>
    );
}

export default function Index({ reservations }: Props) {
    return (
        <MemberLayout>
            <Head title="My Reservations" />

            <div className="space-y-6">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">My Reservations</h1>
                        <p className="text-sm text-slate-500">Daftar reservasi buku yang telah Anda buat.</p>
                    </div>

                    <Link
                        href={route('member.catalog.index')}
                        className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
                    >
                        Browse Catalog
                    </Link>
                </div>

                <div className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-slate-600">
                            <tr>
                                <th className="p-4 text-left font-semibold">Book</th>
                                <th className="p-4 text-left font-semibold">Status</th>
                                <th className="p-4 text-left font-semibold">Reserved At</th>
                                <th className="p-4 text-left font-semibold">Fulfilled At</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-200">
                            {reservations.length > 0 ? (
                                reservations.map((reservation) => (
                                    <tr key={reservation.id} className="align-top">
                                        <td className="p-4">
                                            <div className="font-medium text-slate-900">{reservation.book?.title ?? '-'}</div>
                                            <div className="text-xs text-slate-500">{reservation.book?.author ?? ''}</div>
                                        </td>
                                        <td className="p-4">
                                            <StatusBadge status={reservation.status} />
                                        </td>
                                        <td className="p-4 text-slate-600">{reservation.created_at?.substring(0, 10) ?? '-'}</td>
                                        <td className="p-4 text-slate-600">{reservation.fulfilled_at?.substring(0, 10) ?? '-'}</td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={4} className="p-8 text-center text-slate-500">
                                        <div className="flex flex-col items-center gap-2">
                                            <Bookmark size={24} className="text-slate-300" />
                                            <p>Belum ada reservasi buku.</p>
                                            <Link
                                                href={route('member.catalog.index')}
                                                className="text-sm text-blue-600 hover:underline"
                                            >
                                                Jelajahi katalog untuk memulai
                                            </Link>
                                        </div>
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
