import React from 'react';
import { Link, router } from '@inertiajs/react';
import LibrarianLayout from '@/Layouts/LibrarianLayout';
import SearchBar from '@/Components/SearchBar';
import { Pencil, Trash2, Tag } from 'lucide-react';
import { useTranslation } from 'react-i18next';

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
    const { t } = useTranslation();

    const handleDelete = (category: Category) => {
        if (!window.confirm(t('confirm_delete_category', { name: category.name }))) {
            return;
        }

        router.delete(route('librarian.categories.destroy', category.id));
    };

    return (
        <LibrarianLayout>
            <div className="space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">{t('categories_title')}</h1>
                        <p className="text-sm text-slate-500">{t('categories_description')}</p>
                    </div>

                    <div className="flex items-center gap-3">
                        <SearchBar
                            routeName="librarian.categories.index"
                            searchValue={filters.search}
                            placeholder={t('search_by_category_name')}
                        />
                        <Link
                            href={route('librarian.categories.create')}
                            className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
                        >
                            {t('add_category')}
                        </Link>
                    </div>
                </div>

                <div className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-slate-600">
                            <tr>
                                <th className="p-4 text-left font-semibold">{t('category_name_col')}</th>
                                <th className="p-4 text-left font-semibold">{t('slug_col')}</th>
                                <th className="p-4 text-left font-semibold">{t('books_count_col')}</th>
                                <th className="p-4 text-right font-semibold">{t('action_col')}</th>
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
                                                {category.books_count} {t('books_suffix')}
                                            </span>
                                        </td>
                                        <td className="p-4">
                                            <div className="flex justify-end gap-2">
                                                <Link
                                                    href={route('librarian.categories.edit', category.id)}
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
                                        {t('no_categories_data')}
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
