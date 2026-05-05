import React, { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import BookForm from '@/Components/Books/BookForm';
import LibrarianLayout from '@/Layouts/LibrarianLayout';

type Category = {
    id: number;
    name: string;
};

type Props = {
    categories: Category[];
};

export default function Create({ categories }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        category_id: '',
        title: '',
        author: '',
        publisher: '',
        isbn: '',
        publish_year: '',
        stock: 0,
        cover: '',
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
                title="Create Book"
                description="Tambah buku baru ke katalog perpustakaan."
                categories={categories}
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                submit={submit}
                submitLabel="Save Book"
                backRoute="librarian.books.index"
            />
        </LibrarianLayout>
    );
}
