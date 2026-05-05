import React from 'react';
import { Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import SearchBar from '@/Components/SearchBar';
import { Pencil, Trash2, Tag } from 'lucide-react';

type Category = {
    id: number;
    name: string;
    slug: string;
    books_count: number;
};

type Filters = {
    search: string;
};

type Props = {
    categories: Category[];
    filters: Filters;
};

export default function Index({ categories, filters }: Props) {
    const handleDelete = (category: Category) => {
        if (!window.confirm(`Delete category "${category.name}"?`)) {
            return;
        }

        router.delete(route('admin.categories.destroy', category.id));
    };

    return (
        <AdminLayout>
            <div className="space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">Categories</h1>
                        <p className="text-sm text-slate-500">Kelola kategori buku perpustakaan.</p>
                    </div>

                    <div className="flex items-center gap-3">
                        <SearchBar
                            routeName="admin.categories.index"
                            searchValue={filters.search}
                            placeholder="Search by category name..."
                        />
                        <Link
                            href={route('admin.categories.create')}
                            className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
                        >
                            Add Category
                        </Link>
                    </div>
                </div>

                <div className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-slate-600">
                            <tr>
                                <th className="p-4 text-left font-semibold">Name</th>
                                <th className="p-4 text-left font-semibold">Slug</th>
                                <th className="p-4 text-left font-semibold">Books</th>
                                <th className="p-4 text-right font-semibold">Action</th>
                            </tr>
                        </thead>

                        <tbody className="divide-y divide-slate-200">
                            {categories.length > 0 ? (
                                categories.map((category) => (
                                    <tr key={category.id}>
                                        <td className="p-4">
                                            <div className="flex items-center gap-2">
                                                <Tag size={16} className="text-slate-400" />
                                                <span className="font-medium text-slate-900">{category.name}</span>
                                            </div>
                                        </td>
                                        <td className="p-4 text-slate-600">{category.slug}</td>
                                        <td className="p-4">
                                            <span className="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                                {category.books_count} books
                                            </span>
                                        </td>
                                        <td className="p-4">
                                            <div className="flex justify-end gap-2">
                                                <Link
                                                    href={route('admin.categories.edit', category.id)}
                                                    className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                                >
                                                    <Pencil size={14} />
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() => handleDelete(category)}
                                                    className="rounded-lg bg-rose-600 px-3 py-2 text-xs font-medium text-white transition hover:bg-rose-500"
                                                >
                                                    <Trash2 size={14} />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={4} className="p-8 text-center text-slate-500">
                                        Belum ada data kategori.
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
