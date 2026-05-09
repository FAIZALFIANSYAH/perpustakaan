import React, { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import BookForm from '@/Components/Books/BookForm';
import LibrarianLayout from '@/Layouts/LibrarianLayout';
import { useTranslation } from 'react-i18next';

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
        post(route('librarian.books.store'), {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <LibrarianLayout>
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
                backRoute="librarian.books.index"
            />
        </LibrarianLayout>
    );
}
