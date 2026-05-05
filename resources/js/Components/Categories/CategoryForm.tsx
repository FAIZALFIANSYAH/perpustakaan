import React, { FormEventHandler } from 'react';
import { Link } from '@inertiajs/react';

type CategoryFormData = {
    name: string;
};

type CategoryFormProps = {
    title: string;
    description: string;
    submitLabel: string;
    data: CategoryFormData;
    setData: <K extends keyof CategoryFormData>(key: K, value: CategoryFormData[K]) => void;
    errors: Partial<Record<keyof CategoryFormData, string>>;
    processing: boolean;
    submit: FormEventHandler;
    backRoute: string;
};

function FieldError({ message }: { message?: string }) {
    if (!message) return null;
    return <p className="mt-1 text-sm text-red-600">{message}</p>;
}

export default function CategoryForm({
    title,
    description,
    submitLabel,
    data,
    setData,
    errors,
    processing,
    submit,
    backRoute,
}: CategoryFormProps) {
    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">{title}</h1>
                    <p className="text-sm text-slate-500">{description}</p>
                </div>

                <Link
                    href={backRoute}
                    className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Back
                </Link>
            </div>

            <form onSubmit={submit} className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div className="max-w-md">
                    <label className="mb-2 block text-sm font-medium text-slate-700">Category Name</label>
                    <input
                        value={data.name}
                        onChange={(event) => setData('name', event.target.value)}
                        placeholder="e.g. Teknologi, Novel, Sejarah"
                        className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                    />
                    <FieldError message={errors.name} />
                </div>

                <div className="mt-6 flex items-center justify-end gap-3">
                    <Link
                        href={backRoute}
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
