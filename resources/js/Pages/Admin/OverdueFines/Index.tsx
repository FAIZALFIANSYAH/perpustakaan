import React, { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AdminLayout from '@/Layouts/AdminLayout';

type Statistics = {
    total_overdue: number;
    need_processing: number;
    already_processed: number;
};

type Props = {
    statistics: Statistics;
};

export default function OverdueFinesIndex({ statistics }: Props) {
    const { t } = useTranslation();
    const { post, processing } = useForm();

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('admin.overdue-fines.process'));
    };

    return (
        <AdminLayout>
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">{t('overdue_fines_processing')}</h1>
                    <p className="text-sm text-slate-500">
                        {t('overdue_fines_description')}
                    </p>
                </div>

                {/* Statistics Cards */}
                <div className="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100">
                                    <svg className="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-slate-600">{t('total_overdue')}</p>
                                <p className="text-2xl font-bold text-slate-900">{statistics.total_overdue}</p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-red-100">
                                    <svg className="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                </div>
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-slate-600">{t('need_processing')}</p>
                                <p className="text-2xl font-bold text-slate-900">{statistics.need_processing}</p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100">
                                    <svg className="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-slate-600">{t('already_processed')}</p>
                                <p className="text-2xl font-bold text-slate-900">{statistics.already_processed}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Processing Form */}
                <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 className="text-lg font-semibold text-slate-900 mb-4">{t('process_overdue_fines')}</h2>

                    {statistics.need_processing > 0 ? (
                        <div>
                            <div className="mb-4 p-4 rounded-lg bg-amber-50 border border-amber-200">
                                <h3 className="text-sm font-semibold text-amber-900 mb-2">
                                    {t('overdue_need_processing_title', { count: statistics.need_processing })}
                                </h3>
                                <p className="text-sm text-amber-700">
                                    {t('overdue_need_processing_desc')}
                                </p>
                            </div>

                            <form onSubmit={submit} className="space-y-4">
                                <div className="flex justify-end">
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="rounded-lg bg-slate-900 px-6 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        {processing ? t('processing_button') : t('process_all_overdue')}
                                    </button>
                                </div>
                            </form>
                        </div>
                    ) : (
                        <div className="text-center py-8">
                            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100">
                                <svg className="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 className="mt-4 text-lg font-medium text-slate-900">{t('all_caught_up')}</h3>
                            <p className="mt-2 text-sm text-slate-500">
                                {t('no_overdue_processing_needed')}
                            </p>
                        </div>
                    )}
                </div>

                {/* Information */}
                <div className="rounded-2xl bg-blue-50 p-6 ring-1 ring-blue-200">
                    <h3 className="text-sm font-semibold text-blue-900 mb-2">{t('how_overdue_processing_works')}</h3>
                    <ul className="space-y-2 text-sm text-blue-800">
                        <li className="flex items-start">
                            <span className="mr-2">•</span>
                            <span><strong>{t('automatic_detection')}:</strong> {t('automatic_detection_desc')}</span>
                        </li>
                        <li className="flex items-start">
                            <span className="mr-2">•</span>
                            <span><strong>{t('fine_calculation')}:</strong> {t('fine_calculation_desc')}</span>
                        </li>
                        <li className="flex items-start">
                            <span className="mr-2">•</span>
                            <span><strong>{t('member_notification')}:</strong> {t('member_notification_desc')}</span>
                        </li>
                        <li className="flex items-start">
                            <span className="mr-2">•</span>
                            <span><strong>{t('status_updates')}:</strong> {t('status_updates_desc')}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </AdminLayout>
    );
}
