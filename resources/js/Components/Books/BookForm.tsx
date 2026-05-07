import React, { FormEventHandler, useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';

type Category = {
    id: number;
    name: string;
};

type BookFormData = {
    category_id: string;
    title: string;
    author: string;
    publisher: string;
    isbn: string;
    publish_year: string;
    stock: number | string;
    cover: File | 'REMOVE' | null | undefined;
    description: string;
    is_active: boolean;
};

type BookFormProps = {
    title: string;
    description: string;
    categories: Category[];
    data: BookFormData;
    setData: <K extends keyof BookFormData>(key: K, value: BookFormData[K]) => void;
    errors: Partial<Record<keyof BookFormData, string>>;
    processing: boolean;
    submit: FormEventHandler;
    submitLabel: string;
    backRoute?: string;
    initialCover?: string | null;
};

function FieldError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="mt-1 text-sm text-red-600">{message}</p>;
}

export default function BookForm({
    title,
    description,
    categories,
    data,
    setData,
    errors,
    processing,
    submit,
    submitLabel,
    backRoute = 'admin.books.index',
    initialCover = null,
}: BookFormProps) {
    const [coverPreview, setCoverPreview] = useState<string | null>(
        initialCover ? (initialCover.startsWith('data:') || initialCover.startsWith('http') ? initialCover : initialCover.startsWith('/storage/') ? initialCover : `/storage/${initialCover}`) : null
    );
    const [coverFileName, setCoverFileName] = useState<string>(
        initialCover ? initialCover.split('/').pop() || '' : ''
    );
    const [hasFileChanged, setHasFileChanged] = useState<boolean>(false);
    const [coverClientError, setCoverClientError] = useState<string | null>(null);

    useEffect(() => {
        if (!hasFileChanged) {
            const previewUrl = initialCover
                ? (initialCover.startsWith('data:') || initialCover.startsWith('http')
                    ? initialCover
                    : initialCover.startsWith('/storage/')
                        ? initialCover
                        : `/storage/${initialCover}`)
                : null;
            setCoverPreview(previewUrl);
            setCoverFileName(initialCover ? initialCover.split('/').pop() || '' : '');
        }
    }, [initialCover, hasFileChanged]);

    const handleCoverChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            const maxSizeBytes = 2 * 1024 * 1024;
            if (file.size > maxSizeBytes) {
                setCoverClientError('Ukuran gambar melebihi 2MB. Pilih file yang lebih kecil.');
                setData('cover', null);
                e.target.value = '';
                return;
            }

            setCoverClientError(null);
            setHasFileChanged(true);
            setData('cover', file);
            setCoverFileName(file.name);
            
            // Create preview
            const reader = new FileReader();
            reader.onloadend = () => {
                setCoverPreview(reader.result as string);
            };
            reader.readAsDataURL(file);
        }
    };

    const removeCover = () => {
        setHasFileChanged(true);
        setCoverClientError(null);
        setData('cover', 'REMOVE');
        setCoverPreview(null);
        setCoverFileName('');
    };
    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">{title}</h1>
                    <p className="text-sm text-slate-500">{description}</p>
                </div>

                <Link
                    href={route(backRoute)}
                    className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Back
                </Link>
            </div>

            <form onSubmit={submit} className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div className="grid gap-5 md:grid-cols-2">
                    <div>
                        <label className="mb-2 block text-sm font-medium text-slate-700">Category</label>
                        <select
                            value={data.category_id}
                            onChange={(event) => setData('category_id', event.target.value)}
                            className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >
                            <option value="">Select category</option>
                            {categories.map((category) => (
                                <option key={category.id} value={String(category.id)}>
                                    {category.name}
                                </option>
                            ))}
                        </select>
                        <FieldError message={errors.category_id} />
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-medium text-slate-700">Title</label>
                        <input
                            value={data.title}
                            onChange={(event) => setData('title', event.target.value)}
                            className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        />
                        <FieldError message={errors.title} />
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-medium text-slate-700">Author</label>
                        <input
                            value={data.author}
                            onChange={(event) => setData('author', event.target.value)}
                            className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        />
                        <FieldError message={errors.author} />
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-medium text-slate-700">Publisher</label>
                        <input
                            value={data.publisher}
                            onChange={(event) => setData('publisher', event.target.value)}
                            className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        />
                        <FieldError message={errors.publisher} />
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-medium text-slate-700">ISBN</label>
                        <input
                            value={data.isbn}
                            onChange={(event) => setData('isbn', event.target.value)}
                            className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        />
                        <FieldError message={errors.isbn} />
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-medium text-slate-700">Publish Year</label>
                        <input
                            type="number"
                            value={data.publish_year}
                            onChange={(event) => setData('publish_year', event.target.value)}
                            className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        />
                        <FieldError message={errors.publish_year} />
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-medium text-slate-700">Stock</label>
                        <input
                            type="number"
                            min="0"
                            step="1"
                            value={data.stock}
                            onChange={(event) => setData('stock', event.target.value === '' ? '' : event.target.value)}
                            onWheel={(event) => (event.currentTarget as HTMLInputElement).blur()}
                            className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        />
                        <FieldError message={errors.stock} />
                    </div>

                    <div className="md:col-span-2">
                        <label className="mb-2 block text-sm font-medium text-slate-700">Cover Image</label>
                        <div className="flex items-start gap-4">
                            <div className="flex-1">
                                <input
                                    type="file"
                                    accept="image/*"
                                    onChange={handleCoverChange}
                                    className="w-full text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white file:hover:bg-slate-700"
                                />
                                <p className="mt-1 text-xs text-slate-500">Accepted formats: JPG, PNG, GIF, WebP (Max 2MB)</p>
                                {coverFileName && (
                                    <p className="mt-1 text-xs text-slate-700 font-medium">Selected: {coverFileName}</p>
                                )}
                                {coverClientError && <p className="mt-1 text-sm text-red-600">{coverClientError}</p>}
                                <FieldError message={errors.cover} />
                            </div>
                            {coverPreview && (
                                <div className="relative">
                                    <img
                                        src={coverPreview.startsWith('data:') ? coverPreview : coverPreview}
                                        alt="Cover preview"
                                        className="h-24 w-16 object-cover rounded-lg border border-slate-200"
                                    />
                                    <button
                                        type="button"
                                        onClick={removeCover}
                                        className="absolute -top-2 -right-2 h-6 w-6 rounded-full bg-red-500 text-white text-xs font-bold flex items-center justify-center hover:bg-red-600 shadow-md"
                                        title="Remove cover"
                                    >
                                        ×
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="md:col-span-2">
                        <label className="mb-2 block text-sm font-medium text-slate-700">Description</label>
                        <textarea
                            value={data.description}
                            onChange={(event) => setData('description', event.target.value)}
                            rows={4}
                            className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        />
                        <FieldError message={errors.description} />
                    </div>

                    <div className="md:col-span-2">
                        <label className="inline-flex items-center gap-3 text-sm font-medium text-slate-700">
                            <input
                                type="checkbox"
                                checked={data.is_active}
                                onChange={(event) => setData('is_active', event.target.checked)}
                                className="rounded border-slate-300 text-slate-900 shadow-sm focus:ring-slate-500"
                            />
                            Active book
                        </label>
                        <FieldError message={errors.is_active} />
                    </div>
                </div>

                <div className="mt-6 flex items-center justify-end gap-3">
                    <Link
                        href={route(backRoute)}
                        className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        Cancel
                    </Link>

                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {processing ? 'Saving...' : submitLabel}
                    </button>
                </div>
            </form>
        </div>
    );
}
