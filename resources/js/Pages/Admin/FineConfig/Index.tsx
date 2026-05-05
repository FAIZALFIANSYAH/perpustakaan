import React, { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

type FineConfig = {
    id: number;
    grace_period_days: number;
    max_borrowing_days: number;
    fine_per_day: string;
    max_billable_days: number;
    lost_book_fine: string;
    max_fine_per_item: number;
    max_fine_per_borrowing: number;
    lost_book_payment_deadline: number;
    max_fine_cap: number | null;
    is_active: boolean;
};

type Props = {
    config: FineConfig;
};

export default function FineConfigIndex({ config }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        grace_period_days: config?.grace_period_days ?? 5,
        max_borrowing_days: config?.max_borrowing_days ?? 7,
        fine_per_day: config?.fine_per_day ?? '2000',
        max_billable_days: config?.max_billable_days ?? 5,
        lost_book_fine: config?.lost_book_fine ?? '50000',
        max_fine_per_item: config?.max_fine_per_item ?? 10000,
        max_fine_per_borrowing: config?.max_fine_per_borrowing ?? 50000,
        lost_book_payment_deadline: config?.lost_book_payment_deadline ?? 14,
        max_fine_cap: config?.max_fine_cap ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('admin.fine-config.update'));
    };

    const formatCurrency = (value: string) => {
        const number = parseInt(value) || 0;
        return new Intl.NumberFormat('id-ID').format(number);
    };

    return (
        <AdminLayout>
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">Fine Configuration</h1>
                    <p className="text-sm text-slate-500">Configure fine rules and penalties for the library system.</p>
                </div>

                <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 className="text-lg font-semibold text-slate-900 mb-4">Fine Rules</h2>
                    
                    <form onSubmit={submit} className="space-y-6">
                        <div className="space-y-8">
                        {/* Borrowing Configuration */}
                        <div>
                            <h3 className="text-md font-semibold text-slate-900 mb-4">Borrowing Configuration</h3>
                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-2">
                                        Standard Borrowing Period (Days)
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        value={data.max_borrowing_days}
                                        onChange={(e) => setData('max_borrowing_days', parseInt(e.target.value) || 7)}
                                        className="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                    />
                                    <p className="mt-1 text-xs text-slate-500">
                                        Default borrowing duration for all books
                                    </p>
                                    {errors.max_borrowing_days && (
                                        <p className="mt-1 text-sm text-red-600">{errors.max_borrowing_days}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-2">
                                        Grace Period (Days)
                                    </label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.grace_period_days}
                                        onChange={(e) => setData('grace_period_days', parseInt(e.target.value) || 0)}
                                        className="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                    />
                                    <p className="mt-1 text-xs text-slate-500">
                                        Number of days after due date before fines start applying
                                    </p>
                                    {errors.grace_period_days && (
                                        <p className="mt-1 text-sm text-red-600">{errors.grace_period_days}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-2">
                                        Maximum Billable Days
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        value={data.max_billable_days}
                                        onChange={(e) => setData('max_billable_days', parseInt(e.target.value) || 5)}
                                        className="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                    />
                                    <p className="mt-1 text-xs text-slate-500">
                                        Maximum days for fine calculation (capped system)
                                    </p>
                                    {errors.max_billable_days && (
                                        <p className="mt-1 text-sm text-red-600">{errors.max_billable_days}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-2">
                                        Fine Per Day (Rp)
                                    </label>
                                    <input
                                        type="number"
                                        min="0"
                                        step="100"
                                        value={data.fine_per_day}
                                        onChange={(e) => setData('fine_per_day', e.target.value)}
                                        className="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                    />
                                    <p className="mt-1 text-xs text-slate-500">
                                        Current: Rp {formatCurrency(data.fine_per_day)} per day per book
                                    </p>
                                    {errors.fine_per_day && (
                                        <p className="mt-1 text-sm text-red-600">{errors.fine_per_day}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Fine Limits */}
                        <div>
                            <h3 className="text-md font-semibold text-slate-900 mb-4">Fine Limits</h3>
                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-2">
                                        Lost Book Fine (Rp)
                                    </label>
                                    <input
                                        type="number"
                                        min="0"
                                        step="1000"
                                        value={data.lost_book_fine}
                                        onChange={(e) => setData('lost_book_fine', e.target.value)}
                                        className="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                    />
                                    <p className="mt-1 text-xs text-slate-500">
                                        Current: Rp {formatCurrency(data.lost_book_fine)} per lost book
                                    </p>
                                    {errors.lost_book_fine && (
                                        <p className="mt-1 text-sm text-red-600">{errors.lost_book_fine}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-2">
                                        Maximum Fine per Item (Rp)
                                    </label>
                                    <input
                                        type="number"
                                        min="0"
                                        step="1000"
                                        value={data.max_fine_per_item}
                                        onChange={(e) => setData('max_fine_per_item', parseInt(e.target.value) || 10000)}
                                        className="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                    />
                                    <p className="mt-1 text-xs text-slate-500">
                                        Maximum fine amount per individual item
                                    </p>
                                    {errors.max_fine_per_item && (
                                        <p className="mt-1 text-sm text-red-600">{errors.max_fine_per_item}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-2">
                                        Maximum Fine per Borrowing (Rp)
                                    </label>
                                    <input
                                        type="number"
                                        min="0"
                                        step="1000"
                                        value={data.max_fine_per_borrowing}
                                        onChange={(e) => setData('max_fine_per_borrowing', parseInt(e.target.value) || 50000)}
                                        className="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                    />
                                    <p className="mt-1 text-xs text-slate-500">
                                        Maximum total fine per borrowing transaction
                                    </p>
                                    {errors.max_fine_per_borrowing && (
                                        <p className="mt-1 text-sm text-red-600">{errors.max_fine_per_borrowing}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-2">
                                        Lost Book Payment Deadline (Days)
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        value={data.lost_book_payment_deadline}
                                        onChange={(e) => setData('lost_book_payment_deadline', parseInt(e.target.value) || 14)}
                                        className="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                    />
                                    <p className="mt-1 text-xs text-slate-500">
                                        Days allowed to pay lost book fine
                                    </p>
                                    {errors.lost_book_payment_deadline && (
                                        <p className="mt-1 text-sm text-red-600">{errors.lost_book_payment_deadline}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-2">
                                        Maximum Fine Cap (Rp)
                                    </label>
                                    <input
                                        type="number"
                                        min="0"
                                        step="1000"
                                        value={data.max_fine_cap}
                                        onChange={(e) => setData('max_fine_cap', e.target.value === '' ? '' : parseInt(e.target.value))}
                                        className="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                        placeholder="Leave empty for no cap"
                                    />
                                    <p className="mt-1 text-xs text-slate-500">
                                        Maximum fine amount (optional, leave empty for unlimited)
                                    </p>
                                    {errors.max_fine_cap && (
                                        <p className="mt-1 text-sm text-red-600">{errors.max_fine_cap}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="flex justify-end gap-3 pt-4 border-t border-slate-200">
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-lg bg-slate-900 px-6 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {processing ? 'Saving...' : 'Save Configuration'}
                            </button>
                        </div>
                        </div>
                    </form>
                </div>

                <div className="rounded-2xl bg-blue-50 p-6 ring-1 ring-blue-200">
                    <h3 className="text-sm font-semibold text-blue-900 mb-2">How Fines Work</h3>
                    <ul className="space-y-2 text-sm text-blue-800">
                        <li className="flex items-start">
                            <span className="mr-2">•</span>
                            <span><strong>Late Return Fine:</strong> Calculated based on days overdue × fine per day × quantity. Grace period is applied first.</span>
                        </li>
                        <li className="flex items-start">
                            <span className="mr-2">•</span>
                            <span><strong>Lost Book Fine:</strong> Fixed amount per lost book as configured above. Stock will NOT be restored for lost books.</span>
                        </li>
                        <li className="flex items-start">
                            <span className="mr-2">•</span>
                            <span><strong>Borrowing Status:</strong> If there are unpaid fines, borrowing status will be "awaiting_fine_payment" until all fines are settled.</span>
                        </li>
                        <li className="flex items-start">
                            <span className="mr-2">•</span>
                            <span><strong>Member Blocking:</strong> Members with unpaid fines cannot create new borrowings.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </AdminLayout>
    );
}
