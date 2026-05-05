import React from 'react';
import { Head, router } from '@inertiajs/react';
import LibrarianLayout from '@/Layouts/LibrarianLayout';
import { Search, Users } from 'lucide-react';

type Borrowing = {
    id: number;
    code: string;
    status: string;
};

type Member = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    borrowings: Borrowing[];
};

type PaginatedMembers = {
    data: Member[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type Props = {
    members: PaginatedMembers;
    filters: {
        search: string | null;
    };
};

export default function Index({ members, filters }: Props) {
    const [search, setSearch] = React.useState(filters.search ?? '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('librarian.members.index'), { search }, { preserveState: true, preserveScroll: true });
    };

    return (
        <LibrarianLayout>
            <Head title="Member Lookup" />

            <div className="space-y-6">
                <div>
                    <h2 className="text-2xl font-bold text-slate-900">Member Lookup</h2>
                    <p className="text-slate-500">Cari dan lihat data member perpustakaan.</p>
                </div>

                {/* Search */}
                <form onSubmit={handleSearch} className="flex gap-3">
                    <div className="relative flex-1 max-w-md">
                        <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search by name or email..."
                            className="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-4 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                    </div>
                    <button
                        type="submit"
                        className="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700"
                    >
                        Search
                    </button>
                </form>

                {/* Members Table */}
                <div className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-slate-600">
                            <tr>
                                <th className="p-4 text-left font-semibold">Name</th>
                                <th className="p-4 text-left font-semibold">Email</th>
                                <th className="p-4 text-left font-semibold">Active Borrowings</th>
                                <th className="p-4 text-left font-semibold">Status</th>
                                <th className="p-4 text-left font-semibold">Joined</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-200">
                            {members.data.length > 0 ? (
                                members.data.map((member) => (
                                    <tr key={member.id}>
                                        <td className="p-4 font-medium text-slate-900">{member.name}</td>
                                        <td className="p-4 text-slate-600">{member.email}</td>
                                        <td className="p-4">
                                            <span className="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                                <Users size={12} />
                                                {member.borrowings?.length ?? 0} active
                                            </span>
                                        </td>
                                        <td className="p-4">
                                            {member.email_verified_at ? (
                                                <span className="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                    Verified
                                                </span>
                                            ) : (
                                                <span className="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                                    Unverified
                                                </span>
                                            )}
                                        </td>
                                        <td className="p-4 text-slate-500">{member.created_at?.substring(0, 10) ?? '-'}</td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={5} className="p-8 text-center text-slate-500">
                                        {filters.search ? `No members found for "${filters.search}"` : 'No members registered yet.'}
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {members.last_page > 1 && (
                    <div className="flex items-center justify-center gap-2">
                        {members.prev_page_url && (
                            <button
                                onClick={() => router.get(members.prev_page_url!)}
                                className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                Previous
                            </button>
                        )}
                        <span className="text-sm text-slate-600">
                            Page {members.current_page} of {members.last_page}
                        </span>
                        {members.next_page_url && (
                            <button
                                onClick={() => router.get(members.next_page_url!)}
                                className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                Next
                            </button>
                        )}
                    </div>
                )}
            </div>
        </LibrarianLayout>
    );
}
