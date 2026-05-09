import React, { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import CategoryForm from '@/Components/Categories/CategoryForm';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Create() {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        name: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('admin.categories.store'));
    };

    return (
        <AdminLayout>
            <CategoryForm
                title={t('create_category')}
                description={t('create_category_description')}
                submitLabel={t('save_category')}
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                submit={submit}
                backRoute={route('admin.categories.index')}
            />
        </AdminLayout>
    );
}
