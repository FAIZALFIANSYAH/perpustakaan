import React from 'react';
import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';

type Props = {
    routeName: string;
    searchValue: string;
    placeholder?: string;
};

export default function SearchBar({ routeName, searchValue, placeholder = 'Search...' }: Props) {
    const [value, setValue] = React.useState(searchValue);

    React.useEffect(() => {
        setValue(searchValue);
    }, [searchValue]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            route(routeName),
            value ? { search: value } : {},
            { preserveState: true, preserveScroll: true, replace: true }
        );
    };

    const handleClear = () => {
        setValue('');
        router.get(
            route(routeName),
            {},
            { preserveState: true, preserveScroll: true, replace: true }
        );
    };

    return (
        <form onSubmit={handleSubmit} className="flex items-center gap-2">
            <div className="relative">
                <Search
                    size={16}
                    className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                />
                <input
                    type="text"
                    value={value}
                    onChange={(e) => setValue(e.target.value)}
                    placeholder={placeholder}
                    className="h-10 w-64 rounded-lg border border-slate-300 bg-white pl-9 pr-8 text-sm text-slate-700 placeholder:text-slate-400 focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                />
                {value && (
                    <button
                        type="button"
                        onClick={handleClear}
                        className="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                    >
                        <X size={14} />
                    </button>
                )}
            </div>
            <button
                type="submit"
                className="h-10 rounded-lg bg-slate-900 px-4 text-sm font-medium text-white transition hover:bg-slate-700"
            >
                Search
            </button>
        </form>
    );
}
