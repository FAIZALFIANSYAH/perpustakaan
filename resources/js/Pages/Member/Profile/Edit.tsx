import React, { FormEventHandler, useRef } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import MemberLayout from '@/Layouts/MemberLayout';

type User = {
    name: string;
    email: string;
};

type Props = {
    user: User;
};

function FieldError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="mt-2 text-sm text-rose-600">{message}</p>;
}

export default function Edit({ user }: Props) {
    const profileForm = useForm({
        name: user.name ?? '',
        email: user.email ?? '',
    });

    const passwordInput = useRef<HTMLInputElement | null>(null);
    const currentPasswordInput = useRef<HTMLInputElement | null>(null);

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submitProfile: FormEventHandler = (event) => {
        event.preventDefault();
        profileForm.patch(route('member.profile.update'));
    };

    const submitPassword: FormEventHandler = (event) => {
        event.preventDefault();

        passwordForm.put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => passwordForm.reset(),
            onError: (errors) => {
                if (errors.password) {
                    passwordForm.reset('password', 'password_confirmation');
                    passwordInput.current?.focus();
                }

                if (errors.current_password) {
                    passwordForm.reset('current_password');
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    return (
        <MemberLayout>
            <Head title="Member Profile" />

            <div className="space-y-6">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">Profile</h1>
                        <p className="text-sm text-slate-500">Perbarui informasi akun dan password Anda.</p>
                    </div>

                    <Link
                        href={route('member.dashboard')}
                        className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        Back
                    </Link>
                </div>

                <div className="grid gap-6 xl:grid-cols-2">
                    <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <div>
                            <h2 className="text-lg font-semibold text-slate-900">Profile Information</h2>
                            <p className="mt-1 text-sm text-slate-500">Update your name and email address.</p>
                        </div>

                        <form onSubmit={submitProfile} className="mt-6 space-y-5">
                            <div>
                                <label className="mb-2 block text-sm font-medium text-slate-700">Name</label>
                                <input
                                    value={profileForm.data.name}
                                    onChange={(event) => profileForm.setData('name', event.target.value)}
                                    className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                />
                                <FieldError message={profileForm.errors.name} />
                            </div>

                            <div>
                                <label className="mb-2 block text-sm font-medium text-slate-700">Email</label>
                                <input
                                    type="email"
                                    value={profileForm.data.email}
                                    onChange={(event) => profileForm.setData('email', event.target.value)}
                                    className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                />
                                <FieldError message={profileForm.errors.email} />
                            </div>

                            <div className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                Biodata tambahan belum tersedia karena schema user saat ini hanya mendukung nama dan email.
                            </div>

                            <button
                                type="submit"
                                disabled={profileForm.processing}
                                className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {profileForm.processing ? 'Saving...' : 'Save Profile'}
                            </button>
                        </form>
                    </div>

                    <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <div>
                            <h2 className="text-lg font-semibold text-slate-900">Update Password</h2>
                            <p className="mt-1 text-sm text-slate-500">Gunakan password yang kuat untuk menjaga keamanan akun Anda.</p>
                        </div>

                        <form onSubmit={submitPassword} className="mt-6 space-y-5">
                            <div>
                                <label className="mb-2 block text-sm font-medium text-slate-700">Current Password</label>
                                <input
                                    ref={currentPasswordInput}
                                    type="password"
                                    value={passwordForm.data.current_password}
                                    onChange={(event) => passwordForm.setData('current_password', event.target.value)}
                                    className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                />
                                <FieldError message={passwordForm.errors.current_password} />
                            </div>

                            <div>
                                <label className="mb-2 block text-sm font-medium text-slate-700">New Password</label>
                                <input
                                    ref={passwordInput}
                                    type="password"
                                    value={passwordForm.data.password}
                                    onChange={(event) => passwordForm.setData('password', event.target.value)}
                                    className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                />
                                <FieldError message={passwordForm.errors.password} />
                            </div>

                            <div>
                                <label className="mb-2 block text-sm font-medium text-slate-700">Confirm Password</label>
                                <input
                                    type="password"
                                    value={passwordForm.data.password_confirmation}
                                    onChange={(event) => passwordForm.setData('password_confirmation', event.target.value)}
                                    className="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                />
                                <FieldError message={passwordForm.errors.password_confirmation} />
                            </div>

                            <button
                                type="submit"
                                disabled={passwordForm.processing}
                                className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {passwordForm.processing ? 'Updating...' : 'Update Password'}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </MemberLayout>
    );
}
