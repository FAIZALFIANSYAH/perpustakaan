import React from 'react';
import { Head, Link } from '@inertiajs/react';
import LibrarianLayout from '@/Layouts/LibrarianLayout';
import { BookOpen, Users, AlertTriangle, TrendingUp, Package, CheckCircle2, ArrowRight } from 'lucide-react';

type Category = {
    id: number;
    name: string;
    books_count: number;
};

type Book = {
    id: number;
    title: string;
    author?: string;
    isbn?: string | null;
    stock: number;
    times_borrowed: number;
};

type Reports = {
    books: {
        total: number;
        available: number;
        low_stock: number;
        out_of_stock: number;
    };
    categories: Category[];
    members: {
        total: number;
        active_this_month: number;
    };
    borrowings: {
        this_month: number;
        returns_this_month: number;
        overdue: number;
    };
    most_borrowed: Book[];
};

type Props = {
    reports: Reports;
};

function StatCard({
    title,
    value,
    icon: Icon,
    color,
}: {
    title: string;
    value: number;
    icon: React.ElementType;
    color: string;
}) {
    const colorMap: Record<string, string> = {
        blue: 'bg-blue-50 text-blue-600',
        green: 'bg-emerald-50 text-emerald-600',
        amber: 'bg-amber-50 text-amber-600',
        red: 'bg-rose-50 text-rose-600',
        purple: 'bg-purple-50 text-purple-600',
    };

    return (
        <div className="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-5 flex items-center justify-between">
            <div>
                <p className="text-sm text-slate-500">{title}</p>
                <h3 className="text-3xl font-bold mt-2">{value}</h3>
            </div>
            <div className={`p-3 rounded-xl ${colorMap[color] ?? 'bg-slate-100 text-slate-600'}`}>
                <Icon size={24} />
            </div>
        </div>
    );
}

export default function Index({ reports }: Props) {
    return (
        <LibrarianLayout>
            <Head title="Laporan Perpustakaan" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">Laporan Perpustakaan</h1>
                    <p className="text-sm text-slate-500">Statistik buku, kategori, member, dan peminjaman.</p>
                </div>

                {/* Book Statistics */}
                <div>
                    <h2 className="text-lg font-semibold text-slate-900 mb-4">Statistik Buku</h2>
                    <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                        <StatCard title="Total Buku" value={reports.books.total} icon={BookOpen} color="blue" />
                        <StatCard title="Tersedia" value={reports.books.available} icon={CheckCircle2} color="green" />
                        <StatCard title="Stok Menipis" value={reports.books.low_stock} icon={AlertTriangle} color="amber" />
                        <StatCard title="Habis" value={reports.books.out_of_stock} icon={Package} color="red" />
                    </div>
                </div>

                {/* Member & Borrowing Statistics */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h2 className="text-lg font-semibold text-slate-900 mb-4">Statistik Member</h2>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <StatCard title="Total Member" value={reports.members.total} icon={Users} color="purple" />
                            <StatCard title="Aktif Bulan Ini" value={reports.members.active_this_month} icon={TrendingUp} color="green" />
                        </div>
                    </div>

                    <div>
                        <h2 className="text-lg font-semibold text-slate-900 mb-4">Statistik Peminjaman</h2>
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <StatCard title="Bulan Ini" value={reports.borrowings.this_month} icon={BookOpen} color="blue" />
                            <StatCard title="Dikembalikan" value={reports.borrowings.returns_this_month} icon={CheckCircle2} color="green" />
                            <StatCard title="Terlambat" value={reports.borrowings.overdue} icon={AlertTriangle} color="red" />
                        </div>
                    </div>
                </div>

                {/* Categories */}
                <div className="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200">
                    <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h3 className="font-semibold text-slate-900">Buku per Kategori</h3>
                    </div>
                    <div className="divide-y divide-slate-100">
                        {reports.categories.length > 0 ? (
                            reports.categories.map((category) => (
                                <div key={category.id} className="flex items-center justify-between px-5 py-3">
                                    <p className="text-sm font-medium text-slate-900">{category.name}</p>
                                    <span className="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                        {category.books_count} buku
                                    </span>
                                </div>
                            ))
                        ) : (
                            <div className="px-5 py-8 text-center text-sm text-slate-500">Belum ada kategori</div>
                        )}
                    </div>
                </div>

                {/* Most Borrowed Books */}
                <div className="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200">
                    <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h3 className="font-semibold text-slate-900">Buku Terpopuler</h3>
                        <Link
                            href={route('librarian.books.index')}
                            className="text-sm text-blue-600 hover:underline inline-flex items-center gap-1"
                        >
                            Kelola Buku <ArrowRight size={14} />
                        </Link>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-slate-600">
                                <tr>
                                    <th className="p-4 text-left font-semibold">#</th>
                                    <th className="p-4 text-left font-semibold">Judul</th>
                                    <th className="p-4 text-left font-semibold">Penulis</th>
                                    <th className="p-4 text-left font-semibold">ISBN</th>
                                    <th className="p-4 text-left font-semibold">Stok</th>
                                    <th className="p-4 text-left font-semibold">Dipinjam</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200">
                                {reports.most_borrowed.length > 0 ? (
                                    reports.most_borrowed.map((book, index) => (
                                        <tr key={book.id}>
                                            <td className="p-4 text-slate-500">{index + 1}</td>
                                            <td className="p-4 font-medium text-slate-900">{book.title}</td>
                                            <td className="p-4 text-slate-600">{book.author ?? '-'}</td>
                                            <td className="p-4 text-slate-600">{book.isbn ?? '-'}</td>
                                            <td className="p-4">
                                                <span
                                                    className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${
                                                        book.stock > 5
                                                            ? 'bg-emerald-100 text-emerald-700'
                                                            : book.stock > 0
                                                            ? 'bg-amber-100 text-amber-700'
                                                            : 'bg-rose-100 text-rose-700'
                                                    }`}
                                                >
                                                    {book.stock}
                                                </span>
                                            </td>
                                            <td className="p-4 text-slate-600">{book.times_borrowed} kali</td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={6} className="p-8 text-center text-slate-500">
                                            Belum ada data peminjaman
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </LibrarianLayout>
    );
}
