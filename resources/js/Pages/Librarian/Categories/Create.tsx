import React, { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import CategoryForm from '@/Components/Categories/CategoryForm';
import LibrarianLayout from '@/Layouts/LibrarianLayout';
import { useTranslation } from 'react-i18next';

export default function Create() {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        name: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('librarian.categories.store'));
    };

    return (
        <LibrarianLayout>
            <CategoryForm
                title={t('create_category')}
                description={t('create_category_description')}
                submitLabel={t('save_category')}
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                submit={submit}
                backRoute={route('librarian.categories.index')}
            />
        </LibrarianLayout>
    );
}
