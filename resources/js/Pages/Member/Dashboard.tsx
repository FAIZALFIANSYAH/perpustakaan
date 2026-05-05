import React from 'react';
import { Link } from '@inertiajs/react';
import MemberLayout from '@/Layouts/MemberLayout';
import { AlertTriangle, BookMarked, CheckCircle2, Clock3, Bookmark } from 'lucide-react';

type BorrowingItem = {
    id: number;
    quantity: number;
    returned_quantity: number;
    book?: {
        title: string;
        author?: string;
        cover?: string | null;
    } | null;
};

type Borrowing = {
    id: number;
    code: string;
    due_at: string;
    borrowed_at: string;
    status: string;
    items: BorrowingItem[];
};

type Notification = {
    type: 'late' | 'warning';
    message: string;
};

type Props = {
    summary: {
        active: number;
        history: number;
        returned: number;
        late: number;
        reservations: number;
    };
    activeBorrowings: Borrowing[];
    notifications: Notification[];
};

function StatCard({
    title,
    value,
    icon: Icon,
    tone,
}: {
    title: string;
    value: number;
    icon: React.ElementType;
    tone: string;
}) {
    return (
        <div className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-sm text-slate-500">{title}</p>
                    <h3 className="mt-2 text-3xl font-bold text-slate-900">{value}</h3>
                </div>
                <div className={`rounded-xl p-3 ${tone}`}>
                    <Icon size={22} />
                </div>
            </div>
        </div>
    );
}

export default function Dashboard({ summary, activeBorrowings, notifications }: Props) {
    return (
        <MemberLayout>
            <div className="space-y-6">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">Member Dashboard</h1>
                        <p className="text-sm text-slate-500">Pantau pinjaman aktif dan status pengembalian Anda.</p>
                    </div>

                    <Link
                        href={route('member.borrowings.history')}
                        className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
                    >
                        View History
                    </Link>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <StatCard title="Active Borrowings" value={summary.active} icon={BookMarked} tone="bg-blue-100 text-blue-700" />
                    <StatCard title="Borrowing History" value={summary.history} icon={Clock3} tone="bg-slate-100 text-slate-700" />
                    <StatCard title="Returned" value={summary.returned} icon={CheckCircle2} tone="bg-emerald-100 text-emerald-700" />
                    <StatCard title="Late Borrowings" value={summary.late} icon={AlertTriangle} tone="bg-rose-100 text-rose-700" />
                    <StatCard title="Reservations" value={summary.reservations} icon={Bookmark} tone="bg-amber-100 text-amber-700" />
                </div>

                <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 className="text-lg font-semibold text-slate-900">Notifications</h2>
                    <div className="mt-4 space-y-3">
                        {notifications.length > 0 ? (
                            notifications.map((notification, index) => (
                                <div
                                    key={`${notification.type}-${index}`}
                                    className={`rounded-xl border px-4 py-3 text-sm ${notification.type === 'late' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-amber-200 bg-amber-50 text-amber-700'}`}
                                >
                                    {notification.message}
                                </div>
                            ))
                        ) : (
                            <p className="text-sm text-slate-500">Tidak ada notifikasi pinjaman saat ini.</p>
                        )}
                    </div>
                </div>

                <div className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div className="border-b border-slate-200 p-6">
                        <h2 className="text-lg font-semibold text-slate-900">Active Borrowings</h2>
                        <p className="mt-1 text-sm text-slate-500">Daftar pinjaman yang masih aktif untuk akun Anda.</p>
                    </div>

                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-slate-600">
                            <tr>
                                <th className="p-4 text-left font-semibold">Code</th>
                                <th className="p-4 text-left font-semibold">Books</th>
                                <th className="p-4 text-left font-semibold">Borrowed</th>
                                <th className="p-4 text-left font-semibold">Due Date</th>
                                <th className="p-4 text-left font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-200">
                            {activeBorrowings.length > 0 ? (
                                activeBorrowings.map((borrowing) => (
                                    <tr key={borrowing.id} className="align-top">
                                        <td className="p-4 font-medium text-slate-900">{borrowing.code}</td>
                                        <td className="p-4 text-slate-600">
                                            <div className="space-y-2">
                                                {borrowing.items.map((item) => (
                                                    <div key={item.id} className="flex items-start gap-2">
                                                        {item.book?.cover ? (
                                                            <img
                                                                src={item.book.cover.startsWith('http') || item.book.cover.startsWith('data:') ? item.book.cover : `/storage/${item.book.cover}`}
                                                                alt={item.book.title}
                                                                className="h-10 w-7 object-cover rounded border border-slate-200 flex-shrink-0"
                                                            />
                                                        ) : (
                                                            <div className="h-10 w-7 bg-slate-200 rounded border border-slate-200 flex items-center justify-center flex-shrink-0">
                                                                <span className="text-[8px]">📚</span>
                                                            </div>
                                                        )}
                                                        <div className="text-xs">
                                                            <div className="font-medium text-slate-900">{item.book?.title ?? '-'}</div>
                                                            <div className="text-slate-500">x {item.quantity}</div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </td>
                                        <td className="p-4 text-slate-600">{borrowing.borrowed_at}</td>
                                        <td className="p-4 text-slate-600">{borrowing.due_at}</td>
                                        <td className="p-4">
                                            <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${
                                                borrowing.status === 'returned' || borrowing.status === 'complete' ? 'bg-emerald-100 text-emerald-700' : 
                                                borrowing.status === 'partial' ? 'bg-blue-100 text-blue-700' : 
                                                borrowing.status === 'lost' ? 'bg-red-100 text-red-700' :
                                                borrowing.status === 'awaiting_fine_payment' ? 'bg-amber-100 text-amber-700' :
                                                'bg-gray-100 text-gray-700'
                                            }`}>
                                                {borrowing.status === 'awaiting_fine_payment' ? 'Awaiting Fine Payment' : borrowing.status}
                                            </span>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={5} className="p-8 text-center text-slate-500">
                                        Tidak ada pinjaman aktif saat ini.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </MemberLayout>
    );
}
