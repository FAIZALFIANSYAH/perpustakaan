import React, { FormEventHandler } from 'react';
import { Link } from '@inertiajs/react';

type Role = {
    id: number;
    name: string;
};

type UserFormData = {
    name: string;
    email: string;
    password: string;
    borrow_limit: number;
    role: string;
};

type UserFormProps = {
    title: string;
    description: string;
    submitLabel: string;
    roles: Role[];
    data: UserFormData;
    setData: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void;
    errors: Partial<Record<keyof UserFormData, string>>;
    processing: boolean;
    submit: FormEventHandler;
    isEdit?: boolean;
    isSuperAdminUser?: boolean;
};

function FieldError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="mt-1 text-sm text-red-600">{message}</p>;
}

export default function UserForm({
    title,
    description,
    submitLabel,
    roles,
    data,
    setData,
    errors,
    processing,
    submit,
    isEdit = false,
    isSuperAdminUser = false,
}: UserFormProps) {
    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">{title}</h1>
                    <p className="text-sm text-slate-500">{description}</p>
                </div>

                <Link
                    href={route('admin.users.index')}
                    className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Back
                </Link>
            </div>

            <form onSubmit={submit} className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div className="grid gap-5 md:grid-cols-2">
                    <div>
                        <label className="mb-2 block text-sm font-medium text-slate-700">Name</label>
                        <input
                            value={data.name}
                            onChange={(event) => setData('name', event.target.value)}
                            className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        />
                        <FieldError message={errors.name} />
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-medium text-slate-700">Email</label>
                        <input
                            type="email"
                            value={data.email}
                            onChange={(event) => setData('email', event.target.value)}
                            className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        />
                        <FieldError message={errors.email} />
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-medium text-slate-700">
                            {isEdit ? 'New Password' : 'Password'}
                        </label>
                        <input
                            type="password"
                            value={data.password}
                            onChange={(event) => setData('password', event.target.value)}
                            className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        />
                        <p className="mt-1 text-xs text-slate-500">
                            {isEdit ? 'Leave blank if you do not want to change the password.' : 'Minimum 8 characters.'}
                        </p>
                        <FieldError message={errors.password} />
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-medium text-slate-700">Borrow Limit</label>
                        <input
                            type="number"
                            min={1}
                            max={50}
                            value={data.borrow_limit}
                            onChange={(event) => setData('borrow_limit', Number(event.target.value))}
                            className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        />
                        <p className="mt-1 text-xs text-slate-500">Maximum number of active borrowings allowed.</p>
                        <FieldError message={errors.borrow_limit} />
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-medium text-slate-700">Role</label>
                        {isSuperAdminUser ? (
                            <div className="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                <span className="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">S</span>
                                Super Admin
                            </div>
                        ) : (
                            <select
                                value={data.role}
                                onChange={(event) => setData('role', event.target.value)}
                                className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >
                                <option value="">Select role</option>
                                {roles.map((role) => (
                                    <option key={role.id} value={role.name}>
                                        {role.name}
                                    </option>
                                ))}
                            </select>
                        )}
                        <FieldError message={errors.role} />
                    </div>
                </div>

                <div className="mt-6 flex items-center justify-end gap-3">
                    <Link
                        href={route('admin.users.index')}
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
