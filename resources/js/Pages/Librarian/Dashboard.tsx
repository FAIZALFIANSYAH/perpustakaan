import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import LibrarianLayout from '@/Layouts/LibrarianLayout';
import { ArrowRight, BookOpen, ClipboardList, AlertTriangle, RotateCcw, Plus } from 'lucide-react';

type Stats = {
    borrowings_today: number;
    returns_today: number;
    active_borrowings: number;
    overdue_count: number;
};

type BorrowingItem = {
    id: number;
    quantity: number;
    returned_quantity: number;
    book?: { title: string } | null;
};

type Borrowing = {
    id: number;
    code: string;
    borrowed_at: string;
    due_at: string;
    status: string;
    member?: { name: string; email: string } | null;
    items: BorrowingItem[];
};

type Props = {
    stats: Stats;
    recentTransactions: Borrowing[];
    dueToday: Borrowing[];
};

function StatCard({ title, value, icon: Icon, color }: { title: string; value: number; icon: React.ElementType; color: string }) {
    const colorMap: Record<string, string> = {
        blue: 'bg-blue-50 text-blue-600',
        green: 'bg-emerald-50 text-emerald-600',
        amber: 'bg-amber-50 text-amber-600',
        red: 'bg-rose-50 text-rose-600',
    };

    return (
        <div className="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-5 flex items-center justify-between">
            <div>
                <p className="text-sm text-slate-500">{title}</p>
                <h3 className="text-2xl font-bold mt-1">{value}</h3>
            </div>
            <div className={`p-3 rounded-xl ${colorMap[color] ?? 'bg-slate-100 text-slate-600'}`}>
                <Icon size={22} />
            </div>
        </div>
    );
}

function StatusBadge({ status }: { status: string }) {
    const styles: Record<string, string> = {
        returned: 'bg-emerald-100 text-emerald-700',
        complete: 'bg-emerald-100 text-emerald-700',
        partial: 'bg-blue-100 text-blue-700',
        borrowed: 'bg-gray-100 text-gray-700',
        lost: 'bg-red-100 text-red-700',
        awaiting_fine_payment: 'bg-amber-100 text-amber-700',
    };

    return (
        <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${styles[status] ?? 'bg-slate-100 text-slate-700'}`}>
            {status}
        </span>
    );
}

export default function Dashboard({ stats, recentTransactions, dueToday }: Props) {
    const { t } = useTranslation();

    return (
        <LibrarianLayout>
            <Head title={t('librarian_dashboard')} />

            <div className="space-y-6">
                <div>
                    <h2 className="text-2xl font-bold text-slate-900">{t('librarian_dashboard')}</h2>
                    <p className="text-slate-500">{t('dashboard_subtitle')}</p>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    <StatCard title={t('borrowings_today')} value={stats.borrowings_today} icon={ClipboardList} color="blue" />
                    <StatCard title={t('returns_today')} value={stats.returns_today} icon={RotateCcw} color="green" />
                    <StatCard title={t('active_borrowings')} value={stats.active_borrowings} icon={BookOpen} color="amber" />
                    <StatCard title={t('overdue_count')} value={stats.overdue_count} icon={AlertTriangle} color="red" />
                </div>

                {/* Quick Actions */}
                <div className="flex flex-wrap gap-3">
                    <Link
                        href={route('librarian.borrowings.create')}
                        className="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700"
                    >
                        <Plus size={16} />
                        {t('new_borrowing')}
                    </Link>
                    <Link
                        href={route('librarian.borrowings.index')}
                        className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        <RotateCcw size={16} />
                        {t('return_book')}
                    </Link>
                    <Link
                        href={route('librarian.books.index')}
                        className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        <BookOpen size={16} />
                        {t('manage_books')}
                    </Link>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Recent Transactions */}
                    <div className="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200">
                        <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                            <h3 className="font-semibold text-slate-900">{t('recent_transactions')}</h3>
                            <Link href={route('librarian.borrowings.index')} className="text-sm text-blue-600 hover:underline inline-flex items-center gap-1">
                                {t('view_all')} <ArrowRight size={14} />
                            </Link>
                        </div>
                        <div className="divide-y divide-slate-100">
                            {recentTransactions.length > 0 ? (
                                recentTransactions.map((tx) => (
                                    <div key={tx.id} className="flex items-center justify-between px-5 py-3">
                                        <div>
                                            <p className="text-sm font-medium text-slate-900">{tx.code}</p>
                                            <p className="text-xs text-slate-500">{tx.member?.name ?? '-'} &middot; {tx.items.map((i) => i.book?.title ?? '-').join(', ')}</p>
                                        </div>
                                        <StatusBadge status={tx.status} />
                                    </div>
                                ))
                            ) : (
                                <div className="px-5 py-8 text-center text-sm text-slate-500">{t('no_recent_transactions')}</div>
                            )}
                        </div>
                    </div>

                    {/* Due Today / Late */}
                    <div className="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200">
                        <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                            <h3 className="font-semibold text-slate-900">{t('due_today')}</h3>
                            <Link href={route('librarian.overdue')} className="text-sm text-blue-600 hover:underline inline-flex items-center gap-1">
                                {t('overdue_list')} <ArrowRight size={14} />
                            </Link>
                        </div>
                        <div className="divide-y divide-slate-100">
                            {dueToday.length > 0 ? (
                                dueToday.map((tx) => (
                                    <div key={tx.id} className="flex items-center justify-between px-5 py-3">
                                        <div>
                                            <p className="text-sm font-medium text-slate-900">{tx.code}</p>
                                            <p className="text-xs text-slate-500">{tx.member?.name ?? '-'} &middot; {t('due_label')}: {tx.due_at}</p>
                                        </div>
                                        <span className="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold bg-amber-100 text-amber-700">{t('due_today')}</span>
                                    </div>
                                ))
                            ) : (
                                <div className="px-5 py-8 text-center text-sm text-slate-500">{t('no_borrowings_due_today')}</div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </LibrarianLayout>
    );
}
