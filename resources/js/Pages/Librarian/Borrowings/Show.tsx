import React, { useState, FormEventHandler } from 'react';
import { Link, useForm } from '@inertiajs/react';
import LibrarianLayout from '@/Layouts/LibrarianLayout';

type BorrowingItem = {
    id: number;
    quantity: number;
    returned_quantity: number;
    notes?: string | null;
    book?: {
        title: string;
        author?: string;
        isbn?: string | null;
    } | null;
};

type Borrowing = {
    id: number;
    code: string;
    borrowed_at: string;
    due_at: string;
    returned_at?: string | null;
    status: string;
    notes?: string | null;
    member?: {
        name: string;
        email?: string;
    } | null;
    processedBy?: {
        name: string;
        email?: string;
    } | null;
    items: BorrowingItem[];
};

type Props = {
    borrowing: Borrowing;
};

function LostBookModal({ 
    item, 
    borrowingId, 
    onClose 
}: { 
    item: BorrowingItem; 
    borrowingId: number; 
    onClose: () => void 
}) {
    const remaining = item.quantity - item.returned_quantity;
    const { data, setData, post, processing, errors, reset } = useForm({
        lost_quantity: 1,
        notes: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('librarian.borrowings.report-lost', { 
            borrowing: borrowingId, 
            borrowingItem: item.id 
        }), {
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 className="text-lg font-semibold text-slate-900 mb-4">Report Lost Book</h3>
                
                <div className="space-y-2 mb-4 text-sm">
                    <div className="flex justify-between">
                        <span className="text-slate-600">Book:</span>
                        <span className="font-medium">{item.book?.title}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-slate-600">Borrowed:</span>
                        <span className="font-medium">{item.quantity}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-slate-600">Remaining:</span>
                        <span className="font-semibold text-orange-600">{remaining}</span>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-2">Lost Quantity</label>
                        <input
                            type="number"
                            min="1"
                            max={remaining}
                            value={data.lost_quantity}
                            onChange={(e) => setData('lost_quantity', parseInt(e.target.value) || 1)}
                            className="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        />
                        {errors.lost_quantity && <p className="mt-1 text-sm text-red-600">{errors.lost_quantity}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-2">Notes (Optional)</label>
                        <textarea
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            rows={3}
                            className="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            placeholder="Add details about the lost book..."
                        />
                    </div>

                    <div className="rounded-lg bg-orange-50 p-3 text-sm text-orange-800">
                        <strong>Important:</strong> Reporting a book as lost will:
                        <ul className="mt-2 ml-4 list-disc space-y-1">
                            <li>Create a fine for the lost book</li>
                            <li>NOT restore stock when marked as returned</li>
                            <li>Block member from borrowing until fine is paid</li>
                        </ul>
                    </div>

                    <div className="flex gap-3 pt-2">
                        <button
                            type="button"
                            onClick={onClose}
                            className="flex-1 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="flex-1 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-60"
                        >
                            {processing ? 'Processing...' : 'Report Lost Book'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

function InfoCard({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-xl border border-slate-200 p-4">
            <div className="text-xs font-medium uppercase tracking-wide text-slate-500">{label}</div>
            <div className="mt-2 text-sm font-medium text-slate-900">{value}</div>
        </div>
    );
}

export default function Show({ borrowing }: Props) {
    const [selectedItemForLost, setSelectedItemForLost] = useState<BorrowingItem | null>(null);
    
    const { data, setData, post, processing, errors } = useForm({
        items: borrowing.items.map((item) => ({
            id: item.id,
            return_quantity: 0,
        })),
    });

    const submitReturn: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('librarian.borrowings.return', borrowing.id));
    };

    const hasPendingItems = borrowing.items.some((item) => item.returned_quantity < item.quantity);

    return (
        <LibrarianLayout>
            <div className="space-y-6">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">Borrowing Detail</h1>
                        <p className="text-sm text-slate-500">Ringkasan transaksi peminjaman dan daftar buku yang dipinjam.</p>
                    </div>

                    <Link
                        href={route('librarian.borrowings.index')}
                        className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        Back
                    </Link>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <InfoCard label="Borrowing Code" value={borrowing.code} />
                    <InfoCard label="Member" value={borrowing.member?.name ?? '-'} />
                    <InfoCard label="Borrowed Date" value={borrowing.borrowed_at} />
                    <InfoCard label="Due Date" value={borrowing.due_at} />
                </div>

                <div className="grid gap-6 xl:grid-cols-3">
                    <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 xl:col-span-2">
                        <div className="mb-4 flex items-center justify-between">
                            <div>
                                <h2 className="text-lg font-semibold text-slate-900">Borrowed Books</h2>
                                <p className="text-sm text-slate-500">Detail item buku pada transaksi ini.</p>
                            </div>

                            <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${
                                borrowing.status === 'returned' || borrowing.status === 'complete' ? 'bg-emerald-100 text-emerald-700' : 
                                borrowing.status === 'partial' ? 'bg-blue-100 text-blue-700' : 
                                borrowing.status === 'lost' ? 'bg-red-100 text-red-700' :
                                borrowing.status === 'awaiting_fine_payment' ? 'bg-amber-100 text-amber-700' :
                                'bg-gray-100 text-gray-700'
                            }`}>
                                {borrowing.status}
                            </span>
                        </div>

                        <div className="overflow-hidden rounded-xl border border-slate-200">
                            <table className="w-full text-sm">
                                <thead className="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th className="p-4 text-left font-semibold">Book</th>
                                        <th className="p-4 text-left font-semibold">Author</th>
                                        <th className="p-4 text-left font-semibold">Qty</th>
                                        <th className="p-4 text-left font-semibold">Returned</th>
                                        <th className="p-4 text-left font-semibold">Remaining</th>
                                        <th className="p-4 text-left font-semibold">Actions</th>
                                        <th className="p-4 text-left font-semibold">Notes</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-200">
                                    {borrowing.items.map((item) => {
                                        const remaining = item.quantity - item.returned_quantity;
                                        return (
                                            <tr key={item.id}>
                                                <td className="p-4">
                                                    <div className="font-medium text-slate-900">{item.book?.title ?? '-'}</div>
                                                    <div className="mt-1 text-xs text-slate-500">{item.book?.isbn ?? '-'}</div>
                                                </td>
                                                <td className="p-4 text-slate-600">{item.book?.author ?? '-'}</td>
                                                <td className="p-4 text-slate-600">{item.quantity}</td>
                                                <td className="p-4 text-slate-600">{item.returned_quantity}</td>
                                                <td className="p-4 text-slate-600">{remaining}</td>
                                                <td className="p-4">
                                                    {remaining > 0 && borrowing.status === 'borrowed' && (
                                                        <button
                                                            onClick={() => setSelectedItemForLost(item)}
                                                            className="rounded-lg bg-orange-100 px-3 py-1.5 text-xs font-semibold text-orange-700 hover:bg-orange-200"
                                                        >
                                                            Report Lost
                                                        </button>
                                                    )}
                                                </td>
                                                <td className="p-4 text-slate-600">{item.notes || '-'}</td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="space-y-6">
                        <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h2 className="text-lg font-semibold text-slate-900">Transaction Info</h2>
                            <div className="mt-4 space-y-4 text-sm text-slate-600">
                                <div>
                                    <div className="font-medium text-slate-900">Processed By</div>
                                    <div>{borrowing.processedBy?.name ?? '-'}</div>
                                </div>
                                <div>
                                    <div className="font-medium text-slate-900">Member Email</div>
                                    <div>{borrowing.member?.email ?? '-'}</div>
                                </div>
                                <div>
                                    <div className="font-medium text-slate-900">Returned At</div>
                                    <div>{borrowing.returned_at ?? '-'}</div>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <h2 className="text-lg font-semibold text-slate-900">Notes</h2>
                            <p className="mt-4 text-sm leading-6 text-slate-600">{borrowing.notes || 'Tidak ada catatan untuk transaksi ini.'}</p>
                        </div>

                        <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <div className="flex items-center justify-between gap-3">
                                <div>
                                    <h2 className="text-lg font-semibold text-slate-900">Return Books</h2>
                                    <p className="mt-1 text-sm text-slate-500">Masukkan jumlah buku yang dikembalikan per item.</p>
                                </div>
                            </div>

                            {hasPendingItems ? (
                                <form onSubmit={submitReturn} className="mt-4 space-y-4">
                                    {borrowing.items.map((item, index) => {
                                        const remainingQuantity = item.quantity - item.returned_quantity;

                                        return (
                                            <div key={item.id} className="rounded-xl border border-slate-200 p-4">
                                                <div className="text-sm font-medium text-slate-900">{item.book?.title ?? '-'}</div>
                                                <div className="mt-1 text-xs text-slate-500">Remaining: {remainingQuantity}</div>
                                                <input type="hidden" value={data.items[index].id} />
                                                <div className="mt-3">
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        max={remainingQuantity}
                                                        value={data.items[index].return_quantity}
                                                        onChange={(event) => {
                                                            const items = [...data.items];
                                                            items[index] = {
                                                                ...items[index],
                                                                return_quantity: event.target.value === '' ? 0 : Number(event.target.value),
                                                            };
                                                            setData('items', items);
                                                        }}
                                                        className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                                    />
                                                    {(errors as Record<string, string>)[`items.${index}.return_quantity`] && (
                                                        <p className="mt-1 text-sm text-red-600">{(errors as Record<string, string>)[`items.${index}.return_quantity`]}</p>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}

                                    {(errors as Record<string, string>).items && <p className="text-sm text-red-600">{(errors as Record<string, string>).items}</p>}
                                    {(errors as Record<string, string>).borrowing && <p className="text-sm text-red-600">{(errors as Record<string, string>).borrowing}</p>}

                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        {processing ? 'Processing...' : 'Process Return'}
                                    </button>
                                </form>
                            ) : (
                                <p className="mt-4 text-sm text-slate-500">Semua item pada transaksi ini sudah dikembalikan.</p>
                            )}
                        </div>
                    </div>
                </div>

                {selectedItemForLost && (
                    <LostBookModal
                        item={selectedItemForLost}
                        borrowingId={borrowing.id}
                        onClose={() => setSelectedItemForLost(null)}
                    />
                )}
            </div>
        </LibrarianLayout>
    );
}
