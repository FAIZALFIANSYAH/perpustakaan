import React, { FormEventHandler } from 'react';
import { Link } from '@inertiajs/react';

type Member = {
    id: number;
    name: string;
    email: string;
};

type Book = {
    id: number;
    title: string;
    author: string;
    stock: number;
    category?: {
        name: string;
    } | null;
};

type BorrowingItem = {
    book_id: string;
    quantity: number | string;
    notes: string;
};

type BorrowingFormData = {
    member_id: string;
    borrowed_at: string;
    due_at: string;
    notes: string;
    items: BorrowingItem[];
};

type BorrowingFormProps = {
    members: Member[];
    books: Book[];
    data: BorrowingFormData;
    setData: <K extends keyof BorrowingFormData>(key: K, value: BorrowingFormData[K]) => void;
    errors: Record<string, string>;
    processing: boolean;
    submit: FormEventHandler;
    backRoute?: string;
};

function ErrorText({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="mt-1 text-sm text-red-600">{message}</p>;
}

export default function BorrowingForm({
    members,
    books,
    data,
    setData,
    errors,
    processing,
    submit,
    backRoute = 'admin.borrowings.index',
}: BorrowingFormProps) {
    const addItem = () => {
        setData('items', [...data.items, { book_id: '', quantity: 1, notes: '' }]);
    };

    const removeItem = (index: number) => {
        setData(
            'items',
            data.items.filter((_, itemIndex) => itemIndex !== index),
        );
    };

    const updateItem = (index: number, key: keyof BorrowingItem, value: BorrowingItem[keyof BorrowingItem]) => {
        const items = [...data.items];
        items[index] = {
            ...items[index],
            [key]: value,
        };
        setData('items', items);
    };

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">Create Borrowing</h1>
                    <p className="text-sm text-slate-500">Catat transaksi peminjaman buku untuk anggota perpustakaan.</p>
                </div>

                <Link
                    href={route(backRoute)}
                    className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Back
                </Link>
            </div>

            <form onSubmit={submit} className="space-y-6">
                <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div className="grid gap-5 md:grid-cols-2">
                        <div>
                            <label className="mb-2 block text-sm font-medium text-slate-700">Member</label>
                            <select
                                value={data.member_id}
                                onChange={(event) => setData('member_id', event.target.value)}
                                className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                                <option value="">Select member</option>
                                {members.map((member) => (
                                    <option key={member.id} value={String(member.id)}>
                                        {member.name} - {member.email}
                                    </option>
                                ))}
                            </select>
                            <ErrorText message={errors.member_id} />
                        </div>

                        <div>
                            <label className="mb-2 block text-sm font-medium text-slate-700">Borrowed Date</label>
                            <input
                                type="date"
                                value={data.borrowed_at}
                                onChange={(event) => setData('borrowed_at', event.target.value)}
                                className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            />
                            <ErrorText message={errors.borrowed_at} />
                        </div>

                        <div>
                            <label className="mb-2 block text-sm font-medium text-slate-700">Due Date</label>
                            <input
                                type="date"
                                value={data.due_at}
                                onChange={(event) => setData('due_at', event.target.value)}
                                className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            />
                            <ErrorText message={errors.due_at} />
                        </div>

                        <div className="md:col-span-2">
                            <label className="mb-2 block text-sm font-medium text-slate-700">Notes</label>
                            <textarea
                                value={data.notes}
                                onChange={(event) => setData('notes', event.target.value)}
                                rows={3}
                                className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            />
                            <ErrorText message={errors.notes} />
                        </div>
                    </div>
                </div>

                <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div className="mb-4 flex items-center justify-between">
                        <div>
                            <h2 className="text-lg font-semibold text-slate-900">Borrowing Items</h2>
                            <p className="text-sm text-slate-500">Pilih buku dan jumlah yang dipinjam.</p>
                        </div>

                        <button
                            type="button"
                            onClick={addItem}
                            className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
                        >
                            Add Item
                        </button>
                    </div>

                    <ErrorText message={errors.items} />

                    <div className="space-y-4">
                        {data.items.map((item, index) => (
                            <div key={index} className="grid gap-4 rounded-xl border border-slate-200 p-4 md:grid-cols-12">
                                <div className="md:col-span-5">
                                    <label className="mb-2 block text-sm font-medium text-slate-700">Book</label>
                                    <select
                                        value={item.book_id}
                                        onChange={(event) => updateItem(index, 'book_id', event.target.value)}
                                        className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                    >
                                        <option value="">Select book</option>
                                        {books.map((book) => (
                                            <option key={book.id} value={String(book.id)}>
                                                {book.title} - {book.author} ({book.stock})
                                            </option>
                                        ))}
                                    </select>
                                    <ErrorText message={errors[`items.${index}.book_id`]} />
                                </div>

                                <div className="md:col-span-2">
                                    <label className="mb-2 block text-sm font-medium text-slate-700">Qty</label>
                                    <input
                                        type="number"
                                        min="1"
                                        value={item.quantity}
                                        onChange={(event) => updateItem(index, 'quantity', event.target.value === '' ? '' : Number(event.target.value))}
                                        className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                    />
                                    <ErrorText message={errors[`items.${index}.quantity`]} />
                                </div>

                                <div className="md:col-span-4">
                                    <label className="mb-2 block text-sm font-medium text-slate-700">Item Notes</label>
                                    <input
                                        value={item.notes}
                                        onChange={(event) => updateItem(index, 'notes', event.target.value)}
                                        className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                    />
                                    <ErrorText message={errors[`items.${index}.notes`]} />
                                </div>

                                <div className="flex items-end md:col-span-1">
                                    <button
                                        type="button"
                                        onClick={() => removeItem(index)}
                                        className="w-full rounded-lg border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 transition hover:bg-rose-50"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="flex items-center justify-end gap-3">
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
                        {processing ? 'Saving...' : 'Save Borrowing'}
                    </button>
                </div>
            </form>
        </div>
    );
}
