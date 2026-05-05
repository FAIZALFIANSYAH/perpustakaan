import React, { FormEventHandler } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

type User = {
    id: number;
    name: string;
    email: string;
    is_online: boolean;
    borrow_limit: number;
    roles: Array<{
        id: number;
        name: string;
    }>;
};

type AuthUser = {
    id: number;
    name: string;
    email: string;
    roles?: { name: string }[];
};

type PageProps = {
    auth: {
        user?: AuthUser | null;
    };
};

type Props = {
    users: User[];
    filters: {
        search: string;
    };
};

export default function Index({ users, filters }: Props) {
    const { auth } = usePage().props as PageProps;
    const currentUser = auth?.user;
    const isSuperAdmin = currentUser?.roles?.some((r) => r.name === 'Super Admin') ?? false;

    const { data, setData } = useForm({
        search: filters.search ?? '',
    });

    const submitSearch: FormEventHandler = (event) => {
        event.preventDefault();
        router.get(route('admin.users.index'), { search: data.search }, { preserveState: true, replace: true });
    };

    const resetSearch = () => {
        setData('search', '');
        router.get(route('admin.users.index'), {}, { preserveState: true, replace: true });
    };

    const handleDelete = (user: User) => {
        if (!window.confirm(`Delete user "${user.name}"?`)) {
            return;
        }

        router.delete(route('admin.users.destroy', user.id));
    };

    const canEdit = (user: User) => {
        const role = user.roles[0]?.name;
        if (role === 'Super Admin') return false;
        if (isSuperAdmin) return true;
        if (role === 'Member') return true;
        return false;
    };

    const canDelete = (user: User) => {
        if (!isSuperAdmin) return false;
        if (user.roles[0]?.name === 'Super Admin') return false;
        return true;
    };

    return (
        <AdminLayout>
            <div className="space-y-6">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">User Management</h1>
                        <p className="text-sm text-slate-500">Kelola akun pengguna dan role akses sistem.</p>
                    </div>

                    {isSuperAdmin && (
                        <Link
                            href={route('admin.users.create')}
                            className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
                        >
                            Add User
                        </Link>
                    )}
                </div>

                <form onSubmit={submitSearch} className="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <div className="flex flex-col gap-3 md:flex-row">
                        <input
                            value={data.search}
                            onChange={(event) => setData('search', event.target.value)}
                            placeholder="Search by name or email"
                            className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        />
                        <div className="flex gap-3">
                            <button
                                type="submit"
                                className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
                            >
                                Search
                            </button>
                            <button
                                type="button"
                                onClick={resetSearch}
                                className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                Reset
                            </button>
                        </div>
                    </div>
                </form>

                <div className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-slate-600">
                            <tr>
                                <th className="p-4 text-left font-semibold">Name</th>
                                <th className="p-4 text-left font-semibold">Email</th>
                                <th className="p-4 text-left font-semibold">Role</th>
                                <th className="p-4 text-left font-semibold">Borrow Limit</th>
                                <th className="p-4 text-left font-semibold">Condition</th>
                                <th className="p-4 text-right font-semibold">Action</th>
                            </tr>
                        </thead>

                        <tbody className="divide-y divide-slate-200">
                            {users.length > 0 ? (
                                users.map((user) => (
                                    <tr key={user.id}>
                                        <td className="p-4 font-medium text-slate-900">{user.name}</td>
                                        <td className="p-4 text-slate-600">{user.email}</td>
                                        <td className="p-4">
                                            <span className="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                                {user.roles[0]?.name ?? '-'}
                                            </span>
                                        </td>
                                        <td className="p-4">
                                            {user.roles[0]?.name === 'Member' ? (
                                                <span className="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                                    {user.borrow_limit} books
                                                </span>
                                            ) : (
                                                <span className="text-slate-400">-</span>
                                            )}
                                        </td>
                                        <td className="p-4">
                                            <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ${user.is_online ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>
                                                <span className={`inline-block h-1.5 w-1.5 rounded-full ${user.is_online ? 'bg-emerald-500' : 'bg-slate-400'}`} />
                                                {user.is_online ? 'Online' : 'Offline'}
                                            </span>
                                        </td>
                                        <td className="p-4">
                                            <div className="flex justify-end gap-2">
                                                {canEdit(user) && (
                                                    <Link
                                                        href={route('admin.users.edit', user.id)}
                                                        className="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                                    >
                                                        Edit
                                                    </Link>
                                                )}
                                                {canDelete(user) && (
                                                    <button
                                                        type="button"
                                                        onClick={() => handleDelete(user)}
                                                        className="rounded-lg bg-rose-600 px-3 py-2 text-xs font-medium text-white transition hover:bg-rose-500"
                                                    >
                                                        Delete
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={6} className="p-8 text-center text-slate-500">
                                        Belum ada data user
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
