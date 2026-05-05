import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { User, LogOut, ChevronDown } from 'lucide-react';

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
    profileRoute: string;
};

export default function UserDropdown({ profileRoute }: Props) {
    const [open, setOpen] = React.useState(false);
    const ref = React.useRef<HTMLDivElement>(null);
    const { auth } = usePage().props as PageProps;
    const user = auth?.user;
    const isSuperAdmin = user?.roles?.some((r) => r.name === 'Super Admin') ?? false;
    const isLibrarian = user?.roles?.some((r) => r.name === 'Librarian') ?? false;

    const roleLabel = isSuperAdmin ? 'Super Admin' : isLibrarian ? 'Librarian' : 'Member';
    const roleColor = isSuperAdmin ? 'bg-emerald-100 text-emerald-700' : isLibrarian ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-700';
    const avatarColor = isSuperAdmin ? 'bg-emerald-500' : isLibrarian ? 'bg-blue-500' : 'bg-slate-500';

    React.useEffect(() => {
        function handleClickOutside(event: MouseEvent) {
            if (ref.current && !ref.current.contains(event.target as Node)) {
                setOpen(false);
            }
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    return (
        <div className="relative" ref={ref}>
            <button
                onClick={() => setOpen(!open)}
                className="flex items-center gap-2 rounded-xl bg-slate-100 px-3 py-2 transition hover:bg-slate-200"
            >
                <div className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white ${avatarColor}`}>
                    {user?.name?.charAt(0).toUpperCase() ?? 'U'}
                </div>
                <div className="hidden text-left sm:block">
                    <div className="text-sm font-medium text-slate-700">{user?.name ?? 'User'}</div>
                    <div className={`text-[10px] font-semibold uppercase tracking-wider ${roleColor} inline-block rounded px-1.5 py-0.5`}>
                        {roleLabel}
                    </div>
                </div>
                <ChevronDown size={14} className={`text-slate-400 transition-transform ${open ? 'rotate-180' : ''}`} />
            </button>

            {open && (
                <div className="absolute right-0 mt-2 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg z-50">
                    <div className="border-b border-slate-100 px-4 py-3">
                        <div className="text-sm font-semibold text-slate-900">{user?.name ?? 'User'}</div>
                        <div className="text-xs text-slate-500">{user?.email ?? '-'}</div>
                    </div>
                    <div className="py-1">
                        <Link
                            href={profileRoute}
                            className="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 transition hover:bg-slate-50"
                            onClick={() => setOpen(false)}
                        >
                            <User size={16} />
                            Profile
                        </Link>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
                            onClick={() => setOpen(false)}
                        >
                            <LogOut size={16} />
                            Logout
                        </Link>
                    </div>
                </div>
            )}
        </div>
    );
}
