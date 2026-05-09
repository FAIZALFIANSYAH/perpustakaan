import React, { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import BookForm from '@/Components/Books/BookForm';
import AdminLayout from '@/Layouts/AdminLayout';

type Category = {
    id: number;
    name: string;
};

type Props = {
    categories: Category[];
};

export default function Create({ categories }: Props) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        category_id: '',
        title: '',
        author: '',
        publisher: '',
        isbn: '',
        publish_year: '',
        stock: 0,
        cover: null,
        description: '',
        is_active: true,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('admin.books.store'), {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout>
            <BookForm
                title={t('create_book')}
                description={t('create_book_description')}
                categories={categories}
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                submit={submit}
                submitLabel={t('save_book')}
            />
        </AdminLayout>
    );
}
