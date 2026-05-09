import React from 'react';
import {
    Home,
    BookOpen,
    Users,
    FileBarChart2,
    ClipboardList,
    Tag,
    DollarSign,
    Settings,
} from 'lucide-react';

import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

type AuthUser = {
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
    collapsed: boolean;
};

export default function Sidebar({ collapsed }: Props) {
    const { t } = useTranslation();

    const { auth } = usePage().props as PageProps;

    const user = auth?.user;

    const isSuperAdmin =
        user?.roles?.some((r) => r.name === 'Super Admin') ?? false;

    const isLibrarian =
        user?.roles?.some((r) => r.name === 'Librarian') ?? false;

    const menus = [
        ...(isSuperAdmin
            ? [
                  {
                      label: t('dashboard_menu'),
                      icon: Home,
                      href: '/admin/dashboard',
                  },
              ]
            : []),

        ...(isSuperAdmin
            ? [
                  {
                      label: t('categories'),
                      icon: Tag,
                      href: '/admin/categories',
                  },
              ]
            : []),

        ...(isSuperAdmin
            ? [
                  {
                      label: t('books'),
                      icon: BookOpen,
                      href: '/admin/books',
                  },
              ]
            : []),

        ...(isSuperAdmin
            ? [
                  {
                      label: t('borrowings'),
                      icon: ClipboardList,
                      href: '/admin/borrowings',
                  },
              ]
            : []),

        ...(isSuperAdmin
            ? [
                  {
                      label: t('fines'),
                      icon: DollarSign,
                      href: '/admin/fines',
                  },
              ]
            : []),

        ...(isSuperAdmin
            ? [
                  {
                      label: t('fine_config'),
                      icon: Settings,
                      href: '/admin/fine-config',
                  },
              ]
            : []),

        ...(isSuperAdmin
            ? [
                  {
                      label: t('penalty_config'),
                      icon: Settings,
                      href: '/penalty-config',
                  },
              ]
            : []),

        ...(isSuperAdmin
            ? [
                  {
                      label: t('users'),
                      icon: Users,
                      href: '/admin/users',
                  },
              ]
            : []),

        ...(isSuperAdmin
            ? [
                  {
                      label: t('reports'),
                      icon: FileBarChart2,
                      href: '/admin/reports',
                  },
              ]
            : []),

        ...(isLibrarian
            ? [
                  {
                      label: t('dashboard_menu'),
                      icon: Home,
                      href: '/librarian/dashboard',
                  },
              ]
            : []),

        ...(isLibrarian
            ? [
                  {
                      label: t('books'),
                      icon: BookOpen,
                      href: '/librarian/books',
                  },
              ]
            : []),

        ...(isLibrarian
            ? [
                  {
                      label: t('borrowings'),
                      icon: ClipboardList,
                      href: '/librarian/borrowings',
                  },
              ]
            : []),

        ...(isLibrarian
            ? [
                  {
                      label: t('fines'),
                      icon: DollarSign,
                      href: '/librarian/fines',
                  },
              ]
            : []),

        ...(isLibrarian
            ? [
                  {
                      label: t('categories'),
                      icon: Tag,
                      href: '/librarian/categories',
                  },
              ]
            : []),

        ...(isLibrarian
            ? [
                  {
                      label: t('reports'),
                      icon: FileBarChart2,
                      href: '/librarian/reports',
                  },
              ]
            : []),
    ];

    return (
        <aside
            className={`${
                collapsed ? 'w-20' : 'w-64'
            } min-h-screen bg-slate-900 text-white transition-all duration-300 flex flex-col`}
        >
            <div className="border-b border-slate-800 p-4 text-lg font-bold">
                {collapsed ? t('app_name_short') : t('app_name')}
            </div>

            <nav className="flex-1 space-y-5 p-3">
                {menus.map((item) => {
                    const Icon = item.icon;

                    return (
                        <Link
                            key={item.href}
                            href={item.href}
                            className="flex items-center gap-3 rounded-xl px-3 py-2 transition-colors hover:bg-slate-800"
                        >
                            <Icon size={18} />

                            {!collapsed && <span>{item.label}</span>}
                        </Link>
                    );
                })}
            </nav>

            <div className="border-t border-slate-800 p-3">
                <div
                    className={`flex items-center gap-3 rounded-xl bg-slate-800/50 px-3 py-2.5 ${
                        collapsed ? 'justify-center' : ''
                    }`}
                >
                    <div
                        className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold ${
                            isSuperAdmin
                                ? 'bg-emerald-500 text-white'
                                : isLibrarian
                                  ? 'bg-blue-500 text-white'
                                  : 'bg-slate-600 text-white'
                        }`}
                    >
                        {user?.name?.charAt(0).toUpperCase() ?? 'U'}
                    </div>

                    {!collapsed && (
                        <div className="min-w-0">
                            <div className="truncate text-sm font-medium">
                                {user?.name ?? t('user')}
                            </div>

                            <div
                                className={`text-[10px] font-semibold uppercase tracking-wider ${
                                    isSuperAdmin
                                        ? 'text-emerald-400'
                                        : isLibrarian
                                          ? 'text-blue-400'
                                          : 'text-slate-400'
                                }`}
                            >
                                {isSuperAdmin
                                    ? t('role_super_admin')
                                    : isLibrarian
                                      ? t('role_librarian')
                                      : t('role_member')}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </aside>
    );
}