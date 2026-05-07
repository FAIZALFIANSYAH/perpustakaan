import React, { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

type PenaltyConfig = {
    id: number;
    penalty_enabled: boolean;
    grace_period_penalty_days: number;
    penalty_multiplier: string;
    is_active: boolean;
};

type Props = {
    config: PenaltyConfig;
};

export default function PenaltyConfigIndex({ config }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        penalty_enabled: config?.penalty_enabled ?? true,
        grace_period_penalty_days: config?.grace_period_penalty_days ?? 3,
        penalty_multiplier: config?.penalty_multiplier ?? '2.00',
        is_active: config?.is_active ?? true,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('penalty-config.update'));
    };

    const sampleFine = 10000;
    const multiplier = Number(data.penalty_multiplier || 0);
    const penaltyFine = Number.isFinite(multiplier) ? Math.round(sampleFine * multiplier) : 0;
    const formatRupiah = (amount: number) => new Intl.NumberFormat('id-ID').format(amount);

    return (
        <AdminLayout>
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">Penalty Configuration</h1>
                    <p className="text-sm text-slate-500">
                        Configure extra penalty behavior for overdue fines after grace period.
                    </p>
                </div>

                <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-6 md:grid-cols-2">
                            <label className="flex items-start gap-3 rounded-xl border border-slate-200 p-4">
                                <input
                                    type="checkbox"
                                    checked={data.penalty_enabled}
                                    onChange={(event) => setData('penalty_enabled', event.target.checked)}
                                    className="mt-1 rounded border-slate-300 text-slate-900 focus:ring-slate-500"
                                />
                                <div>
                                    <div className="text-sm font-semibold text-slate-900">Enable Penalty System</div>
                                    <div className="text-xs text-slate-500">Apply multiplier when overdue crosses threshold.</div>
                                </div>
                            </label>

                            <label className="flex items-start gap-3 rounded-xl border border-slate-200 p-4">
                                <input
                                    type="checkbox"
                                    checked={data.is_active}
                                    onChange={(event) => setData('is_active', event.target.checked)}
                                    className="mt-1 rounded border-slate-300 text-slate-900 focus:ring-slate-500"
                                />
                                <div>
                                    <div className="text-sm font-semibold text-slate-900">Configuration Active</div>
                                    <div className="text-xs text-slate-500">System uses this configuration immediately.</div>
                                </div>
                            </label>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-2">
                                    Grace Period Penalty Days
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    max="30"
                                    value={data.grace_period_penalty_days}
                                    onChange={(event) => setData('grace_period_penalty_days', Number(event.target.value) || 0)}
                                    className="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                />
                                <p className="mt-1 text-xs text-slate-500">
                                    Penalty applies starting day {Number(data.grace_period_penalty_days || 0) + 1}.
                                </p>
                                {errors.grace_period_penalty_days && (
                                    <p className="mt-1 text-sm text-red-600">{errors.grace_period_penalty_days}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-2">
                                    Penalty Multiplier
                                </label>
                                <input
                                    type="number"
                                    min="1"
                                    max="10"
                                    step="0.01"
                                    value={data.penalty_multiplier}
                                    onChange={(event) => setData('penalty_multiplier', event.target.value)}
                                    className="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                />
                                <p className="mt-1 text-xs text-slate-500">Allowed range 1.00 - 10.00.</p>
                                {errors.penalty_multiplier && (
                                    <p className="mt-1 text-sm text-red-600">{errors.penalty_multiplier}</p>
                                )}
                            </div>
                        </div>

                        <div className="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                            <div className="text-sm font-semibold text-slate-900 mb-2">Calculation Preview</div>
                            <div className="grid gap-3 md:grid-cols-3 text-sm text-slate-700">
                                <div>Base fine: <span className="font-semibold">Rp {formatRupiah(sampleFine)}</span></div>
                                <div>Multiplier: <span className="font-semibold">{data.penalty_multiplier}x</span></div>
                                <div>Penalty fine: <span className="font-semibold">Rp {formatRupiah(penaltyFine)}</span></div>
                            </div>
                        </div>

                        <div className="flex justify-end">
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-lg bg-slate-900 px-6 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:opacity-60"
                            >
                                {processing ? 'Saving...' : 'Save Penalty Config'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}

