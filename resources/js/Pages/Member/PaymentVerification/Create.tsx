import React, { FormEventHandler } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import MemberLayout from '@/Layouts/MemberLayout';

type Fine = {
    id: number;
    amount: number;
    remaining_amount: number;
    type: string;
    due_date: string;
    borrowing_item: {
        book: {
            title: string;
            author: string;
        };
    };
    member: {
        name: string;
        email: string;
    };
};

type PageProps = {
    fine: Fine;
};

export default function Create({ fine }: PageProps) {
    const { data, setData, post, processing, errors } = useForm({
        amount: fine.remaining_amount,
        payment_method: 'cash',
        reference_number: '',
        notes: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('member.payment-verification.store', fine.id));
    };

    const paymentMethods = [
        { value: 'cash', label: 'Cash' },
        { value: 'bank_transfer', label: 'Bank Transfer' },
        { value: 'e_wallet', label: 'E-Wallet' },
        { value: 'check', label: 'Check' },
    ];

    return (
        <MemberLayout>
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">Request Payment</h1>
                        <p className="text-sm text-slate-500">Submit payment request for fine verification.</p>
                    </div>

                    <Link
                        href={route('member.fines.index')}
                        className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        Back to Fines
                    </Link>
                </div>

                <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div className="mb-6 rounded-lg bg-amber-50 p-4 border border-amber-200">
                        <h3 className="font-semibold text-amber-900 mb-2">Fine Details</h3>
                        <div className="space-y-1 text-sm">
                            <p><span className="font-medium">Type:</span> {fine.type}</p>
                            <p><span className="font-medium">Book:</span> {fine.borrowing_item.book.title} by {fine.borrowing_item.book.author}</p>
                            <p><span className="font-medium">Total Amount:</span> Rp {fine.amount.toLocaleString('id-ID')}</p>
                            <p><span className="font-medium">Remaining:</span> Rp {fine.remaining_amount.toLocaleString('id-ID')}</p>
                            <p><span className="font-medium">Due Date:</span> {new Date(fine.due_date).toLocaleDateString('id-ID')}</p>
                        </div>
                    </div>

                    <form onSubmit={submit} className="space-y-6">
                        <div>
                            <label htmlFor="amount" className="block text-sm font-medium text-slate-700">
                                Payment Amount
                            </label>
                            <div className="mt-1">
                                <input
                                    type="number"
                                    id="amount"
                                    step="0.01"
                                    min="0.01"
                                    max={fine.remaining_amount}
                                    value={data.amount}
                                    onChange={(e) => setData('amount', parseFloat(e.target.value))}
                                    className="block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm"
                                    required
                                />
                                {errors.amount && (
                                    <p className="mt-1 text-sm text-rose-600">{errors.amount}</p>
                                )}
                            </div>
                            <p className="mt-1 text-xs text-slate-500">
                                Maximum: Rp {fine.remaining_amount.toLocaleString('id-ID')}
                            </p>
                        </div>

                        <div>
                            <label htmlFor="payment_method" className="block text-sm font-medium text-slate-700">
                                Payment Method
                            </label>
                            <div className="mt-1">
                                <select
                                    id="payment_method"
                                    value={data.payment_method}
                                    onChange={(e) => setData('payment_method', e.target.value)}
                                    className="block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm"
                                    required
                                >
                                    {paymentMethods.map((method) => (
                                        <option key={method.value} value={method.value}>
                                            {method.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        {(data.payment_method === 'bank_transfer' || data.payment_method === 'e_wallet') && (
                            <div>
                                <label htmlFor="reference_number" className="block text-sm font-medium text-slate-700">
                                    Reference Number
                                </label>
                                <div className="mt-1">
                                    <input
                                        type="text"
                                        id="reference_number"
                                        value={data.reference_number}
                                        onChange={(e) => setData('reference_number', e.target.value)}
                                        placeholder="Enter transaction/reference number"
                                        className="block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm"
                                    />
                                    {errors.reference_number && (
                                        <p className="mt-1 text-sm text-rose-600">{errors.reference_number}</p>
                                    )}
                                </div>
                            </div>
                        )}

                        <div>
                            <label htmlFor="notes" className="block text-sm font-medium text-slate-700">
                                Notes (Optional)
                            </label>
                            <div className="mt-1">
                                <textarea
                                    id="notes"
                                    rows={3}
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    placeholder="Add any additional notes about this payment"
                                    className="block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm"
                                />
                                {errors.notes && (
                                    <p className="mt-1 text-sm text-rose-600">{errors.notes}</p>
                                )}
                            </div>
                        </div>

                        <div className="flex items-center justify-end gap-3">
                            <Link
                                href={route('member.fines.index')}
                                className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {processing ? 'Submitting...' : 'Submit Payment Request'}
                            </button>
                        </div>
                    </form>
                </div>

                <div className="rounded-2xl bg-blue-50 p-4 border border-blue-200">
                    <h3 className="font-semibold text-blue-900 mb-2">Payment Verification Process</h3>
                    <ol className="list-decimal list-inside space-y-1 text-sm text-blue-800">
                        <li>You submit this payment request</li>
                        <li>Librarian will review and verify your payment</li>
                        <li>Once verified, a receipt will be generated</li>
                        <li>Your fine status will be updated to "Paid"</li>
                    </ol>
                    <p className="mt-2 text-xs text-blue-600">
                        Payment requests expire after 24 hours if not verified.
                    </p>
                </div>
            </div>
        </MemberLayout>
    );
}
