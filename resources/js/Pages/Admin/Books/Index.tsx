import React from 'react';
import { Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import SearchBar from '@/Components/SearchBar';

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
    const handleDelete = (book: Book) => {
        if (!window.confirm(`Delete "${book.title}"?`)) {
            return;
        }

        router.delete(route('admin.books.destroy', book.id));
    };

    return (
        <AdminLayout>
            <div className="space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">Book Management</h1>
                        <p className="text-sm text-slate-500">Kelola koleksi buku perpustakaan.</p>
                    </div>

                    <div className="flex items-center gap-3">
                        <SearchBar
                            routeName="admin.books.index"
                            searchValue={filters.search}
                            placeholder="Search by title, author, ISBN..."
                        />
                        <Link
                            href={route('admin.books.create')}
                            className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
                        >
                            Add Book
                        </Link>
                    </div>
                </div>

                <div className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-slate-600">
                            <tr>
                                <th className="p-4 text-left font-semibold">Cover</th>
                                <th className="p-4 text-left font-semibold">Title</th>
                                <th className="p-4 text-left font-semibold">Category</th>
                                <th className="p-4 text-left font-semibold">Author</th>
                                <th className="p-4 text-left font-semibold">Year</th>
                                <th className="p-4 text-left font-semibold">Stock</th>
                                <th className="p-4 text-left font-semibold">Status</th>
                                <th className="p-4 text-right font-semibold">Action</th>
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
                                                    <span className="text-xs text-slate-500">No Cover</span>
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
                                                {book.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>
                                        <td className="p-4">
                                            <div className="flex justify-end gap-2">
                                                <Link
                                                    href={route('admin.books.edit', book.id)}
                                                    className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() => handleDelete(book)}
                                                    className="rounded-lg bg-rose-600 px-3 py-2 text-xs font-medium text-white transition hover:bg-rose-500"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={8} className="p-8 text-center text-slate-500">
                                        Belum ada data buku
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminLayout>
    );
}
